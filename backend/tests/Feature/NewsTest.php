<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(ContentSeeder::class);
    }

    public function test_migration_creates_the_news_listing_page(): void
    {
        $page = Page::query()->where('slug', 'news')->firstOrFail();

        $this->assertSame('listing', $page->template);
        $this->getJson('/api/v1/pages/news')->assertOk();
        $this->get('/news')->assertOk();
    }

    public function test_admin_publishes_schedules_and_sanitises_news(): void
    {
        $admin = $this->userWithRole('content-manager');

        $this->actingAs($admin)->postJson('/api/v1/admin/news', [
            'title' => 'Scholarship Results Announced', 'status' => 'published', 'source_name' => 'Ministry', 'source_url' => 'https://example.com/results',
            'content' => '<p>Congrats<script>alert(1)</script></p>',
        ])->assertCreated()->assertJsonPath('data.author_name', $admin->name);

        $this->actingAs($admin)->postJson('/api/v1/admin/news', [
            'title' => 'Future News', 'status' => 'published', 'published_at' => now()->addDays(2)->toDateTimeString(),
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/v1/admin/news', [
            'title' => 'Bad Source', 'status' => 'draft', 'source_url' => 'javascript:alert(1)',
        ])->assertUnprocessable()->assertJsonValidationErrors('source_url');

        $news = News::query()->where('slug', 'scholarship-results-announced')->firstOrFail();
        $this->assertStringNotContainsString('<script', (string) $news->content);
        $this->assertNotNull($news->published_at);

        $this->getJson('/api/v1/news')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('meta.all_count', 1);
        $this->getJson('/api/v1/news?search=scholarship')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/news/future-news')->assertNotFound();
        $this->getJson('/api/v1/news/scholarship-results-announced')->assertOk()
            ->assertJsonPath('data.source_name', 'Ministry')->assertJsonStructure(['meta' => ['popular']]);

        $this->get('/news/scholarship-results-announced')->assertOk()->assertSee('"@type":"NewsArticle"', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/news/scholarship-results-announced', false);
        $this->getJson('/api/v1/search?q=scholarship')->assertOk()->assertJsonPath('data.news.0.url', '/news/scholarship-results-announced');
    }

    public function test_news_views_count_once_per_visitor(): void
    {
        $news = News::query()->create(['title' => 'Open Day', 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->getJson('/api/v1/news/open-day')->assertOk();
        $this->getJson('/api/v1/news/open-day')->assertOk();

        $this->assertSame(1, $news->fresh()->views_count);
    }

    public function test_news_requires_permission_and_reserves_the_slug(): void
    {
        $this->actingAs($this->userWithRole('counsellor'))->getJson('/api/v1/admin/news')->assertForbidden();

        $this->actingAs($this->userWithRole('admin'))->postJson('/api/v1/admin/pages', [
            'title' => 'News', 'slug' => 'news', 'template' => 'default', 'status' => 'draft',
        ])->assertUnprocessable()->assertJsonValidationErrors('slug');
    }
}
