<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use Database\Seeders\ContentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed([PageSeeder::class, ContentSeeder::class, MenuSeeder::class]);
    }

    private function homepageKeys(): array
    {
        return collect($this->getJson('/api/v1/homepage')->assertOk()->json('data.sections'))->pluck('key')->all();
    }

    public function test_homepage_returns_enabled_sections_in_admin_order(): void
    {
        $keys = $this->homepageKeys();

        $this->assertSame('hero', $keys[0]);
        $this->assertNotContains('cta', $keys); // seeded as hidden
        $this->getJson('/api/v1/homepage')->assertJsonPath('data.sections.0.slides.0.highlight', 'Starts Here');
    }

    public function test_admin_changes_appear_on_the_public_homepage_immediately(): void
    {
        $admin = $this->userWithRole('content-manager');
        $this->homepageKeys(); // warm the cache

        $services = HomepageSection::query()->where('key', 'services')->first();
        $this->actingAs($admin)->putJson("/api/v1/admin/homepage-sections/{$services->id}", [
            'title' => 'Everything *We Do*', 'is_active' => true,
        ])->assertOk();

        $section = collect($this->getJson('/api/v1/homepage')->json('data.sections'))->firstWhere('key', 'services');
        $this->assertSame('Everything *We Do*', $section['title']);

        $this->actingAs($admin)->putJson("/api/v1/admin/homepage-sections/{$services->id}", ['is_active' => false])->assertOk();
        $this->assertNotContains('services', $this->homepageKeys());
    }

    public function test_homepage_sections_can_be_reordered_but_not_created_or_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $ids = HomepageSection::query()->orderBy('sort_order')->pluck('id')->reverse()->values()->all();

        $this->actingAs($admin)->postJson('/api/v1/admin/homepage-sections/reorder', ['ids' => $ids])->assertOk();
        $this->assertSame('newsletter', $this->homepageKeys()[0]);

        $this->actingAs($admin)->deleteJson("/api/v1/admin/homepage-sections/{$ids[0]}")->assertStatus(405);
        $this->actingAs($admin)->postJson('/api/v1/admin/homepage-sections', ['title' => 'x'])->assertStatus(405);
    }

    public function test_section_data_is_validated_and_html_is_sanitised(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson('/api/v1/admin/pages', [
            'title' => 'Bad link', 'template' => 'default', 'status' => 'published',
            'sections' => [['type' => 'cta', 'data' => ['primary_text' => 'Go', 'primary_url' => 'javascript:alert(1)']]],
        ])->assertStatus(422)->assertJsonValidationErrors(['sections.0.data.primary_url']);

        $this->actingAs($admin)->postJson('/api/v1/admin/pages', [
            'title' => 'Study Guide', 'template' => 'default', 'status' => 'published',
            'content' => '<p onclick="steal()">Hello<script>alert(1)</script></p>',
            'sections' => [
                ['type' => 'rich_text', 'title' => 'Intro', 'data' => ['body' => '<h2>Funding</h2><img src=x onerror=alert(1)>', 'unknown' => 'dropped']],
                ['type' => 'feature_cards', 'data' => ['style' => 'grid', 'items' => [['icon' => 'award', 'title' => 'Merit']]]],
            ],
        ])->assertCreated();

        $page = Page::query()->where('slug', 'study-guide')->with('sections')->firstOrFail();
        $this->assertStringNotContainsString('script', $page->content);
        $this->assertStringNotContainsString('onclick', $page->content);
        $this->assertStringNotContainsString('onerror', $page->sections[0]->data['body']);
        $this->assertArrayNotHasKey('unknown', $page->sections[0]->data);

        $this->getJson('/api/v1/pages/study-guide')->assertOk()->assertJsonCount(2, 'data.sections');
    }

    public function test_page_sections_are_synced_on_update(): void
    {
        $admin = $this->userWithRole('admin');
        $about = Page::query()->where('slug', 'about')->with('sections')->firstOrFail();
        $keep = $about->sections->first();

        $this->actingAs($admin)->putJson("/api/v1/admin/pages/{$about->id}", [
            'title' => 'About Us', 'slug' => 'about', 'template' => 'about', 'status' => 'published',
            'sections' => [
                ['type' => 'faq', 'title' => 'Questions', 'data' => ['page_key' => 'contact']],
                ['id' => $keep->id, 'type' => $keep->type, 'title' => 'Renamed', 'data' => $keep->data],
            ],
        ])->assertOk();

        $sections = $about->fresh()->sections;
        $this->assertCount(2, $sections);
        $this->assertSame('faq', $sections[0]->type);
        $this->assertSame($keep->id, $sections[1]->id);
        $this->assertSame('Renamed', $sections[1]->title);

        $this->getJson('/api/v1/pages/about')->assertJsonCount(5, 'data.sections.0.faqs');
    }

    public function test_reserved_slugs_and_unpublished_pages(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson('/api/v1/admin/pages', ['title' => 'Blog', 'slug' => 'blog', 'template' => 'default', 'status' => 'published'])
            ->assertStatus(422)->assertJsonValidationErrors(['slug']);

        $this->actingAs($admin)->postJson('/api/v1/admin/pages', ['title' => 'Secret', 'template' => 'default', 'status' => 'draft'])->assertCreated();
        $this->getJson('/api/v1/pages/secret')->assertNotFound();
    }

    public function test_menu_reorder_supports_one_level_of_nesting(): void
    {
        $admin = $this->userWithRole('admin');
        $menu = Menu::query()->where('location', 'footer_quick')->firstOrFail();
        [$a, $b, $c] = $menu->items()->orderBy('sort_order')->take(3)->get();

        $this->actingAs($admin)->postJson('/api/v1/admin/menu-items/reorder', ['menu_id' => $menu->id, 'items' => [
            ['id' => $b->id, 'parent_id' => null], ['id' => $a->id, 'parent_id' => $b->id], ['id' => $c->id, 'parent_id' => null],
        ]])->assertOk();

        $tree = $this->getJson('/api/v1/site')->json('data.menus.footer_quick.items');
        $this->assertSame($b->label, $tree[0]['label']);
        $this->assertSame($a->label, $tree[0]['children'][0]['label']);

        // A child cannot also be a parent.
        $this->actingAs($admin)->postJson('/api/v1/admin/menu-items/reorder', ['menu_id' => $menu->id, 'items' => [
            ['id' => $a->id, 'parent_id' => null], ['id' => $b->id, 'parent_id' => $a->id], ['id' => $c->id, 'parent_id' => $b->id],
        ]])->assertStatus(422);

        $this->actingAs($admin)->postJson('/api/v1/admin/menu-items', [
            'menu_id' => $menu->id, 'label' => 'Evil', 'link_type' => 'external', 'url' => 'javascript:alert(1)', 'target' => '_self',
        ])->assertStatus(422)->assertJsonValidationErrors(['url']);

        $this->assertTrue(MenuItem::query()->where('label', $a->label)->exists());
    }

    public function test_cms_permissions(): void
    {
        $counsellor = $this->userWithRole('counsellor');
        foreach (['homepage-sections', 'hero-slides', 'pages', 'services', 'faqs', 'menus'] as $path) {
            $this->actingAs($counsellor)->getJson("/api/v1/admin/{$path}")->assertForbidden();
        }

        $cm = $this->userWithRole('content-manager');
        $this->actingAs($cm)->getJson('/api/v1/admin/services')->assertOk();
        $this->actingAs($cm)->postJson('/api/v1/admin/services', ['title' => 'Test Prep', 'status' => 'published', 'seo' => ['title' => 'IELTS prep']])
            ->assertCreated()->assertJsonPath('data.slug', 'test-prep');
    }

    public function test_newsletter_subscription_is_idempotent(): void
    {
        $this->postJson('/api/v1/newsletter', ['email' => 'Student@Example.com'])->assertOk();
        $this->postJson('/api/v1/newsletter', ['email' => 'student@example.com'])->assertOk();
        $this->postJson('/api/v1/newsletter', ['email' => 'nope'])->assertStatus(422);

        $this->assertSame(1, NewsletterSubscriber::query()->count());
    }
}
