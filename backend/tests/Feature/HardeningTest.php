<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Lead;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Fixes from the 2026-11-19 code review. */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_forwarded_for_header_cannot_fake_the_client_ip(): void
    {
        $this->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
            ->postJson('/api/v1/enquiries', ['name' => 'Ann', 'email' => 'ann@example.com', 'phone' => '+675 7000 0000'])
            ->assertSuccessful();

        $this->assertSame('127.0.0.1', Lead::query()->where('email', 'ann@example.com')->value('ip_address'));
    }

    public function test_guest_admin_api_returns_401_even_without_json_accept_header(): void
    {
        $this->get('/api/v1/admin/users')->assertUnauthorized();
    }

    public function test_analytics_code_is_not_injected_into_the_admin_panel_and_only_super_admin_can_change_it(): void
    {
        app(SettingService::class)->update(['analytics_head_code' => '<script>window.ga=1</script>']);

        $this->get('/')->assertOk()->assertSee('window.ga=1', false);
        $this->get('/admin/login')->assertOk()->assertDontSee('window.ga=1', false);

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->getJson('/api/v1/admin/settings')->assertOk()
            ->assertJsonFragment(['key' => 'analytics_head_code', 'locked' => true]);
        $this->actingAs($admin)->putJson('/api/v1/admin/settings', ['settings' => ['analytics_head_code' => '<script>evil()</script>']])
            ->assertForbidden();
        // Saving the SEO group unchanged still works for an Admin.
        $this->actingAs($admin)->putJson('/api/v1/admin/settings', ['settings' => ['analytics_head_code' => '<script>window.ga=1</script>', 'seo_default_title' => 'X']])
            ->assertOk();

        $this->actingAs($this->userWithRole('super-admin'))
            ->putJson('/api/v1/admin/settings', ['settings' => ['analytics_head_code' => '<script>window.gtag=2</script>']])->assertOk();
        $this->assertSame('<script>window.gtag=2</script>', app(SettingService::class)->get('analytics_head_code'));
    }

    public function test_public_forms_do_not_overwrite_an_open_leads_name_or_phone(): void
    {
        $lead = Lead::query()->create(['name' => 'Real Name', 'email' => 'real@example.com', 'phone' => '+675 1111 1111', 'source' => 'contact_form', 'status' => 'new']);

        $this->postJson('/api/v1/contact', [
            'name' => 'Attacker', 'email' => 'real@example.com', 'phone' => '+675 9999 9999',
            'inquiry_type' => 'General Enquiry', 'message' => 'Please call me on this number.', 'agree' => true,
        ])->assertSuccessful();

        $lead->refresh();
        $this->assertSame('Real Name', $lead->name);
        $this->assertSame('+675 1111 1111', $lead->phone);
        $history = $lead->meta['history'];
        $this->assertSame(['name' => 'Attacker', 'phone' => '+675 9999 9999'], end($history)['submitted']);
    }

    public function test_changing_a_password_signs_out_other_sessions(): void
    {
        config(['session.driver' => 'database']); // as in production (.env SESSION_DRIVER=database)
        $admin = $this->userWithRole('admin');
        $staff = $this->userWithRole('counsellor');
        foreach (['s1', 's2'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $staff->id, 'payload' => 'x', 'last_activity' => time()]);
        }
        DB::table('sessions')->insert(['id' => 'other', 'user_id' => $admin->id, 'payload' => 'x', 'last_activity' => time()]);

        $this->actingAs($admin)->putJson("/api/v1/admin/users/{$staff->id}", [
            'name' => $staff->name, 'email' => $staff->email, 'role_id' => $staff->role_id, 'is_active' => true,
            'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123',
        ])->assertOk();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $staff->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $admin->id)->count(), 'other users are not affected');
    }

    public function test_deleted_applications_and_their_documents_are_erased_after_90_days(): void
    {
        Storage::fake('documents');
        $make = function (string $ref) {
            $app = Application::query()->create(['reference' => $ref, 'name' => 'S', 'email' => "{$ref}@example.com", 'phone' => '123456']);
            Storage::disk('documents')->put("{$ref}.pdf", 'passport');
            ApplicationDocument::query()->create(['application_id' => $app->id, 'type' => 'passport', 'original_name' => 'p.pdf', 'path' => "{$ref}.pdf", 'mime_type' => 'application/pdf', 'size' => 8]);

            return $app;
        };

        $old = $make('OLD1');
        $old->delete();
        Application::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(91)]);
        $recent = $make('NEW1');
        $recent->delete();
        $active = $make('ACT1');

        $this->artisan('model:prune', ['--model' => [Application::class]])->assertSuccessful();

        $this->assertNull(Application::withTrashed()->find($old->id));
        Storage::disk('documents')->assertMissing('OLD1.pdf');
        $this->assertNotNull(Application::withTrashed()->find($recent->id));
        Storage::disk('documents')->assertExists('NEW1.pdf');
        $this->assertNotNull(Application::query()->find($active->id));
        Storage::disk('documents')->assertExists('ACT1.pdf');
    }
}
