<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Destination;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\SuccessStory;
use App\Support\PublicCache;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(ContentSeeder::class);
    }

    public function test_blog_posts_with_tags_scheduling_and_sanitised_content(): void
    {
        $admin = $this->userWithRole('content-manager');
        $category = BlogCategory::query()->create(['name' => 'Visa Notice', 'type' => 'notice']);

        $this->actingAs($admin)->postJson('/api/v1/admin/blogs', [
            'title' => 'UK Visa Update', 'type' => 'notice', 'blog_category_id' => $category->id, 'status' => 'published',
            'content' => '<p>Read this<script>alert(1)</script></p><img src=x onerror=alert(2)>', 'tags' => ['Visa', 'UK', 'visa'],
        ])->assertCreated()->assertJsonPath('data.tags', ['Visa', 'UK'])->assertJsonPath('data.author_name', $admin->name);

        $this->actingAs($admin)->postJson('/api/v1/admin/blogs', [
            'title' => 'Future Post', 'type' => 'blog', 'status' => 'published', 'published_at' => now()->addDays(3)->toDateTimeString(),
        ])->assertCreated();

        $post = Blog::query()->where('slug', 'uk-visa-update')->firstOrFail();
        $this->assertStringNotContainsString('script', $post->content);
        $this->assertStringNotContainsString('onerror', $post->content);

        $this->getJson('/api/v1/blogs')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('meta.all_count', 1);
        $this->getJson('/api/v1/blogs/future-post')->assertNotFound();
        $this->getJson('/api/v1/blogs?category=visa-notice')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/blogs?tag=uk')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/blogs?type=blog')->assertJsonPath('meta.total', 0);

        $this->get('/blog/uk-visa-update')->assertOk()->assertSee('"@type":"NewsArticle"', false);
    }

    public function test_blog_views_count_once_per_visitor(): void
    {
        $post = Blog::query()->create(['title' => 'Popular', 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->getJson('/api/v1/blogs/popular')->assertOk()->assertJsonStructure(['meta' => ['categories', 'popular']]);
        $this->getJson('/api/v1/blogs/popular')->assertOk();

        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_album_photos_are_synced_and_published_with_counts(): void
    {
        $admin = $this->userWithRole('content-manager');
        $events = GalleryCategory::query()->create(['name' => 'Events', 'type' => 'photo']);

        $id = $this->actingAs($admin)->postJson('/api/v1/admin/gallery-albums', [
            'title' => 'Education Fair', 'gallery_category_id' => $events->id, 'status' => 'published',
            'photos' => ['media/a.webp', 'media/b.webp', 'media/c.webp'],
        ])->assertCreated()->assertJsonPath('data.photos', ['media/a.webp', 'media/b.webp', 'media/c.webp'])->json('data.id');

        $this->actingAs($admin)->putJson("/api/v1/admin/gallery-albums/{$id}", [
            'title' => 'Education Fair', 'gallery_category_id' => $events->id, 'status' => 'published',
            'photos' => ['media/c.webp', 'media/a.webp'],
        ])->assertOk()->assertJsonPath('data.photos', ['media/c.webp', 'media/a.webp']);

        $this->getJson('/api/v1/gallery/photos?category=events')->assertOk()
            ->assertJsonPath('data.0.images_count', 2)
            ->assertJsonPath('data.0.cover_image', 'media/c.webp')
            ->assertJsonPath('meta.categories.0.count', 2);
        $this->getJson('/api/v1/gallery/albums/education-fair')->assertOk()->assertJsonCount(2, 'data.images');

        GalleryAlbum::query()->whereKey($id)->update(['status' => 'draft']);
        PublicCache::flush();
        $this->getJson('/api/v1/gallery/albums/education-fair')->assertNotFound();
    }

    public function test_videos_accept_only_youtube_urls(): void
    {
        $admin = $this->userWithRole('content-manager');

        $this->actingAs($admin)->postJson('/api/v1/admin/gallery-videos', ['title' => 'Bad', 'youtube_url' => 'https://vimeo.com/123', 'status' => 'published'])
            ->assertStatus(422)->assertJsonValidationErrors(['youtube_url']);
        $this->actingAs($admin)->postJson('/api/v1/admin/gallery-videos', ['title' => 'Short', 'youtube_url' => 'https://www.youtube.com/shorts/H14bBuluwB8', 'status' => 'published', 'duration' => '03:45'])
            ->assertCreated()->assertJsonPath('data.youtube_id', 'H14bBuluwB8');

        $this->getJson('/api/v1/gallery/videos')->assertOk()->assertJsonPath('data.0.youtube_id', 'H14bBuluwB8');
    }

    public function test_visitor_story_submissions_are_saved_as_escaped_drafts(): void
    {
        $uk = Destination::query()->create(['name' => 'United Kingdom', 'slug' => 'uk', 'status' => 'published']);
        $form = ['name' => 'Rina Das', 'email' => 'Rina@Example.com', 'destination' => 'uk', 'story' => "I got my visa!\n\n<script>alert(1)</script> Thank you team."];

        $this->postJson('/api/v1/success-stories', $form)->assertCreated();
        $this->postJson('/api/v1/success-stories', $form + ['website' => 'spam'])->assertStatus(422);
        $this->postJson('/api/v1/success-stories', ['name' => 'x', 'email' => 'x@example.com', 'story' => 'short'])->assertStatus(422);

        $story = SuccessStory::query()->firstOrFail();
        $this->assertSame('draft', $story->status);
        $this->assertSame('rina@example.com', $story->submitter_email);
        $this->assertSame($uk->id, $story->destination_id);
        $this->assertStringNotContainsString('<script>', $story->story);
        $this->assertStringContainsString('&lt;script&gt;', $story->story);

        $this->getJson('/api/v1/success-stories')->assertJsonPath('meta.total', 0);
        $this->actingAs($this->userWithRole('admin'))->getJson('/api/v1/admin/success-stories?submissions=1')->assertJsonPath('meta.total', 1);
    }

    public function test_published_stories_list_detail_and_seo(): void
    {
        $uk = Destination::query()->create(['name' => 'United Kingdom', 'slug' => 'uk', 'country_code' => 'gb', 'status' => 'published']);
        SuccessStory::query()->create(['student_name' => 'Sarah Ahmed', 'destination_id' => $uk->id, 'quote' => 'Great!', 'status' => 'published', 'video_url' => null, 'youtube_id' => 'H14bBuluwB8']);

        $this->getJson('/api/v1/success-stories?destination=uk')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('meta.destinations.0.count', 1)->assertJsonPath('meta.videos.0.youtube_id', 'H14bBuluwB8');
        $this->getJson('/api/v1/success-stories/sarah-ahmed')->assertOk()->assertJsonMissingPath('data.submitter_email');
        $this->get('/success-stories/sarah-ahmed')->assertOk()->assertSee('Sarah Ahmed&#039;s Success Story', false);
        $this->get('/success-stories/nobody')->assertNotFound();
        $this->getJson('/api/v1/search?q=sarah')->assertJsonPath('data.success_stories.0.title', 'Sarah Ahmed');
    }

    public function test_categories_with_content_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $category = BlogCategory::query()->create(['name' => 'Tips']);
        Blog::query()->create(['title' => 'Tip', 'blog_category_id' => $category->id, 'status' => 'draft']);

        $this->actingAs($admin)->deleteJson("/api/v1/admin/blog-categories/{$category->id}")->assertStatus(422);
    }

    public function test_content_permissions(): void
    {
        $counsellor = $this->userWithRole('counsellor');
        foreach (['blogs', 'blog-categories', 'success-stories', 'gallery-albums', 'gallery-videos', 'gallery-categories'] as $path) {
            $this->actingAs($counsellor)->getJson("/api/v1/admin/{$path}")->assertForbidden();
        }
    }
}
