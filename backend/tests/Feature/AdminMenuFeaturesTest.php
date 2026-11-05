<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Blog;
use App\Models\Counsellor;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Term;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\TermSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminMenuFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed([EmailTemplateSeeder::class, TermSeeder::class]);
        Mail::fake();
    }

    public function test_managed_lists_respect_module_permissions_and_rename_everywhere(): void
    {
        $editor = $this->userWithRole('content-manager');
        $counsellor = $this->userWithRole('counsellor');

        $this->actingAs($editor)->getJson('/api/v1/admin/page-categories')->assertOk()->assertJsonCount(3, 'data');
        $this->actingAs($counsellor)->getJson('/api/v1/admin/page-categories')->assertForbidden();
        $this->actingAs($editor)->getJson('/api/v1/admin/intakes')->assertOk();

        // Taxonomy can't be spoofed: the URL decides it.
        $id = $this->actingAs($editor)->postJson('/api/v1/admin/faq-categories', ['name' => 'Fees', 'taxonomy' => 'page_category'])->assertCreated()->json('data.id');
        $this->assertSame('faq_category', Term::query()->find($id)->taxonomy);

        Faq::query()->create(['question' => 'How much?', 'answer' => '<p>x</p>', 'category' => 'Fees', 'page_key' => 'general', 'status' => 'published']);
        $this->actingAs($editor)->putJson("/api/v1/admin/faq-categories/{$id}", ['name' => 'Tuition Fees'])->assertOk()->assertJsonPath('data.usage_count', 1);
        $this->assertSame('Tuition Fees', Faq::query()->value('category'));

        // Id-based list: pages filter by category; deleting the category clears it on the page.
        $legal = Term::query()->of('page_category')->where('name', 'Legal')->value('id');
        $page = Page::query()->create(['title' => 'Cookies', 'slug' => 'cookies', 'template' => 'default', 'status' => 'published', 'page_category_id' => $legal]);
        $this->actingAs($editor)->getJson("/api/v1/admin/pages?page_category_id={$legal}")->assertOk()->assertJsonPath('data.0.id', $page->id);
        $this->actingAs($editor)->deleteJson("/api/v1/admin/page-categories/{$legal}")->assertOk();
        $this->assertNull($page->fresh()->page_category_id);

        $this->actingAs($editor)->getJson('/api/v1/admin/lookups?types=page_categories,intakes')->assertOk()
            ->assertJsonPath('data.intakes.0.value', 'January');
    }

    public function test_blog_views_drafts_scheduled_published_and_tags(): void
    {
        $admin = $this->userWithRole('admin');
        Blog::query()->create(['title' => 'Draft one', 'status' => 'draft']);
        Blog::query()->create(['title' => 'Later', 'status' => 'published', 'published_at' => now()->addWeek()]);
        Blog::query()->create(['title' => 'Live', 'status' => 'published', 'published_at' => now()->subDay()]);

        foreach (['draft' => 'Draft one', 'scheduled' => 'Later', 'published' => 'Live'] as $state => $title) {
            $this->actingAs($admin)->getJson("/api/v1/admin/blogs?state={$state}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', $title);
        }
        $this->actingAs($admin)->postJson('/api/v1/admin/blog-tags', ['name' => 'Visa'])->assertCreated();
        $this->actingAs($admin)->getJson('/api/v1/admin/blog-tags')->assertJsonPath('data.0.blogs_count', 0);
    }

    public function test_notifications_reach_permitted_staff_and_respect_preferences(): void
    {
        $admin = $this->userWithRole('admin');
        $editor = $this->userWithRole('content-manager');
        $quiet = $this->userWithRole('admin', ['notification_preferences' => ['message' => false]]);

        $this->postJson('/api/v1/contact', ['name' => 'Ann', 'email' => 'ann@example.com', 'phone' => '0501234567', 'inquiry_type' => 'Visa Assistance', 'message' => 'Please help with my visa.', 'agree' => true])->assertCreated();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $editor->notifications()->count(), 'No access to messages, no notification');
        $this->assertSame(0, $quiet->notifications()->count(), 'Turned off in Notification Settings');

        $res = $this->actingAs($admin)->getJson('/api/v1/admin/notifications/summary')->assertOk()->assertJsonPath('data.unread', 1)
            ->assertJsonPath('data.latest.0.type', 'message');
        $id = $res->json('data.latest.0.id');
        $this->actingAs($admin)->getJson('/api/v1/admin/notifications?filter=unread')->assertJsonCount(1, 'data');
        $this->actingAs($admin)->postJson("/api/v1/admin/notifications/{$id}/read")->assertOk();
        $this->actingAs($admin)->getJson('/api/v1/admin/notifications?filter=unread')->assertJsonCount(0, 'data');
        // Another user's notification is not reachable.
        $this->actingAs($quiet)->postJson("/api/v1/admin/notifications/{$id}/read")->assertNotFound();

        $this->actingAs($editor)->getJson('/api/v1/admin/notifications/preferences')->assertOk()->assertJsonCount(1, 'data'); // stories only
        $this->actingAs($admin)->putJson('/api/v1/admin/notifications/preferences', ['preferences' => ['enquiry' => false, 'bogus' => true]])->assertOk();
        $this->assertSame(['enquiry' => false], $admin->fresh()->notification_preferences);
    }

    public function test_reports_logs_and_system_settings(): void
    {
        $admin = $this->userWithRole('admin');
        $super = $this->userWithRole('super-admin');
        Lead::query()->create(['name' => 'A', 'email' => 'a@example.com', 'source' => 'contact_form', 'status' => 'converted']);
        Lead::query()->create(['name' => 'B', 'email' => 'b@example.com', 'source' => 'contact_form', 'status' => 'new']);

        $sources = collect($this->actingAs($admin)->getJson('/api/v1/admin/leads/sources')->assertOk()->json('data'))->keyBy('source');
        $this->assertSame(2, $sources['contact_form']['total']);
        $this->assertEquals(50, $sources['contact_form']['conversion_rate']);

        $c = Counsellor::query()->create(['name' => 'Dominic', 'status' => 'published']);
        $c->availabilities()->create(['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '12:00']);
        $this->actingAs($admin)->getJson('/api/v1/admin/counsellors/availability')->assertOk()->assertJsonPath('data.0.days.1.0', '09:00–12:00');

        ActivityLog::query()->create(['user_id' => $admin->id, 'action' => 'login', 'module' => 'auth', 'description' => 'signed in']);
        ActivityLog::query()->create(['user_id' => $admin->id, 'action' => 'updated', 'module' => 'pages', 'description' => 'Page #1 updated']);
        $this->actingAs($super)->getJson('/api/v1/admin/activity-logs?group=login')->assertJsonCount(1, 'data');
        $content = collect($this->actingAs($super)->getJson('/api/v1/admin/activity-logs?group=content')->json('data'));
        $this->assertTrue($content->contains('module', 'pages'));
        $this->assertEmpty($content->whereIn('module', ['auth', 'users', 'roles', 'settings'])->all());
        $this->actingAs($admin)->getJson('/api/v1/admin/profile/logins')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->userWithRole('counsellor'))->getJson('/api/v1/admin/profile/logins')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($admin)->getJson('/api/v1/admin/system')->assertOk()->assertJsonPath('data.can_download_backups', false);
        $this->actingAs($this->userWithRole('content-manager'))->getJson('/api/v1/admin/system')->assertForbidden();
        $this->actingAs($admin)->postJson('/api/v1/admin/system/cache-clear')->assertOk();
        $this->actingAs($admin)->getJson('/api/v1/admin/system/backups/db_2026-01-01_000000.sql.gz')->assertForbidden();
        $this->actingAs($super)->getJson('/api/v1/admin/system/backups/..%2F.env')->assertNotFound();
    }
}
