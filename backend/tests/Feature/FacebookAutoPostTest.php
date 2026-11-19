<?php

namespace Tests\Feature;

use App\Jobs\PublishToFacebook;
use App\Models\Blog;
use App\Models\News;
use App\Services\FacebookPublisher;
use App\Services\SettingService;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FacebookAutoPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(ContentSeeder::class);
        config(['app.url' => 'https://pngiep.test', 'app.frontend_url' => 'https://pngiep.test', 'services.facebook.graph_version' => 'v21.0']);
        $this->configure(true);
    }

    private function configure(bool $enabled): void
    {
        app(SettingService::class)->update([
            'facebook_autopost_enabled' => $enabled, 'facebook_page_id' => '1234567890', 'facebook_page_token' => 'PAGE-TOKEN',
        ]);
    }

    private function runJob(string $model, int $id): void
    {
        app()->call([new PublishToFacebook($model, $id), 'handle']);
    }

    public function test_publishing_queues_a_post_but_drafts_scheduled_and_opted_out_entries_do_not(): void
    {
        Queue::fake();
        $admin = $this->userWithRole('content-manager');

        $this->actingAs($admin)->postJson('/api/v1/admin/news', ['title' => 'Open Day', 'status' => 'published', 'facebook_post' => true])->assertCreated();
        $this->actingAs($admin)->postJson('/api/v1/admin/news', ['title' => 'Draft News', 'status' => 'draft', 'facebook_post' => true])->assertCreated();
        $this->actingAs($admin)->postJson('/api/v1/admin/news', ['title' => 'No FB', 'status' => 'published', 'facebook_post' => false])->assertCreated();
        $this->actingAs($admin)->postJson('/api/v1/admin/blogs', ['title' => 'Later Post', 'type' => 'blog', 'status' => 'published', 'facebook_post' => true,
            'published_at' => now()->addHour()->toDateTimeString()])->assertCreated();

        Queue::assertPushed(PublishToFacebook::class, 1);
        Queue::assertPushed(PublishToFacebook::class, fn ($job) => $job->model === News::class && $job->id === News::query()->where('slug', 'open-day')->value('id'));

        // The scheduler picks up the scheduled post once its time has come (Open Day is already posted).
        News::query()->where('slug', 'open-day')->update(['facebook_post_id' => '1_1']);
        $this->assertSame(0, app(FacebookPublisher::class)->queueDue());
        $this->travel(61)->minutes();
        $this->assertSame(1, app(FacebookPublisher::class)->queueDue());
        Queue::assertPushed(PublishToFacebook::class, fn ($job) => $job->model === Blog::class);
    }

    public function test_nothing_is_queued_while_auto_post_is_off(): void
    {
        Queue::fake();
        $this->configure(false);

        $this->actingAs($this->userWithRole('content-manager'))
            ->postJson('/api/v1/admin/news', ['title' => 'Quiet', 'status' => 'published', 'facebook_post' => true])->assertCreated();

        Queue::assertNothingPushed();
        $this->assertSame(0, app(FacebookPublisher::class)->queueDue());
    }

    public function test_photo_post_with_caption_and_link_then_never_twice(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'photo1', 'post_id' => '1234567890_999'])]);
        $news = News::query()->create([
            'title' => 'Visa Rules Change', 'excerpt' => 'New rules from January.', 'status' => 'published', 'published_at' => now()->subMinute(),
            'featured_image' => 'https://images.example.com/visa.jpg', 'facebook_post' => true,
        ]);

        $this->runJob(News::class, $news->id);
        $this->runJob(News::class, $news->id);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $r) {
            return $r->url() === 'https://graph.facebook.com/v21.0/1234567890/photos'
                && $r['url'] === 'https://images.example.com/visa.jpg'
                && $r['access_token'] === 'PAGE-TOKEN'
                && str_contains($r['caption'], 'Visa Rules Change')
                && str_contains($r['caption'], 'New rules from January.')
                && str_contains($r['caption'], 'https://pngiep.test/news/visa-rules-change');
        });
        $this->assertSame('1234567890_999', $news->fresh()->facebook_post_id);
        $this->assertNotNull($news->fresh()->facebook_posted_at);
    }

    public function test_link_post_when_there_is_no_image(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '1234567890_555'])]);
        $blog = Blog::query()->create(['title' => 'Study Tips', 'type' => 'blog', 'status' => 'published', 'published_at' => now()->subMinute(), 'facebook_post' => true]);

        $this->runJob(Blog::class, $blog->id);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://graph.facebook.com/v21.0/1234567890/feed'
            && $r['link'] === 'https://pngiep.test/blog/study-tips' && str_contains($r['message'], 'Study Tips'));
        $this->assertSame('1234567890_555', $blog->fresh()->facebook_post_id);
    }

    public function test_failure_keeps_the_reason_notifies_admins_and_is_not_retried_automatically(): void
    {
        Queue::fake();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Error validating access token', 'code' => 190]], 400)]);
        $admin = $this->userWithRole('admin');
        $news = News::query()->create(['title' => 'Broken', 'status' => 'published', 'published_at' => now()->subMinute(), 'facebook_post' => true]);

        $job = new PublishToFacebook(News::class, $news->id);
        try {
            app()->call([$job, 'handle']);
            $this->fail('Expected the Graph API error to be thrown.');
        } catch (\RuntimeException $e) {
            $job->failed($e);
        }

        $news->refresh();
        $this->assertStringContainsString('token is invalid or expired', $news->facebook_error);
        $this->assertNull($news->facebook_post_id);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);
        $this->assertSame(0, app(FacebookPublisher::class)->queueDue());

        // "Post now / retry" from the admin list clears the error and queues it again.
        $this->actingAs($admin)->postJson("/api/v1/admin/news/{$news->id}/facebook")->assertOk();
        $this->assertNull($news->fresh()->facebook_error);
        Queue::assertPushed(PublishToFacebook::class, 1);
    }

    public function test_post_now_works_for_older_entries_and_refuses_duplicates(): void
    {
        Queue::fake();
        $admin = $this->userWithRole('admin');
        $old = Blog::query()->create(['title' => 'Old Post', 'type' => 'blog', 'status' => 'published', 'published_at' => now()->subYear(), 'facebook_post' => false]);
        $done = News::query()->create(['title' => 'Done', 'status' => 'published', 'published_at' => now()->subDay(), 'facebook_post' => true, 'facebook_post_id' => '1_2']);

        $this->assertSame(0, app(FacebookPublisher::class)->queueDue(), 'entries not opted in are never auto-posted');

        $this->actingAs($admin)->postJson("/api/v1/admin/blogs/{$old->id}/facebook")->assertOk();
        $this->assertTrue($old->fresh()->facebook_post);
        Queue::assertPushed(PublishToFacebook::class, fn ($job) => $job->model === Blog::class && $job->id === $old->id);

        $this->actingAs($admin)->postJson("/api/v1/admin/news/{$done->id}/facebook")->assertUnprocessable();
        $this->actingAs($this->userWithRole('counsellor'))->postJson("/api/v1/admin/news/{$done->id}/facebook")->assertForbidden();
    }

    public function test_connection_test_and_public_api_hides_facebook_fields(): void
    {
        Http::fake(['graph.facebook.com/v21.0/1234567890*' => Http::response(['id' => '1234567890', 'name' => 'PNGIEP Page'])]);

        $this->actingAs($this->userWithRole('admin'))->postJson('/api/v1/admin/settings/test-facebook')
            ->assertOk()->assertJsonPath('data.page', 'PNGIEP Page');

        $news = News::query()->create(['title' => 'Public', 'status' => 'published', 'published_at' => now()->subDay(), 'facebook_post_id' => '1_2', 'facebook_error' => 'x']);
        $this->getJson('/api/v1/news/public')->assertOk()->assertJsonMissingPath('data.facebook_post_id')->assertJsonMissingPath('data.facebook_error');
        $this->getJson('/api/v1/news')->assertOk()->assertJsonMissingPath('data.0.facebook_post_id');
        $this->assertNotNull($news);
    }
}
