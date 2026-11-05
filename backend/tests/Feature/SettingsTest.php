<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_public_site_endpoint_never_exposes_private_or_secret_settings(): void
    {
        $response = $this->getJson('/api/v1/site')->assertOk()->assertJsonPath('success', true);
        $json = json_encode($response->json('data.settings'));

        $this->assertArrayHasKey('general', $response->json('data.settings'));
        foreach (['mail_password', 'mail_host', 'analytics_head_code', 'robots_txt', 'admin_notification_email'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function test_admin_updates_appear_on_public_endpoint(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->putJson('/api/v1/admin/settings', ['settings' => ['site_name' => 'New Name']])->assertOk();

        $this->getJson('/api/v1/site')->assertJsonPath('data.settings.general.site_name', 'New Name');
        $this->assertDatabaseHas('activity_logs', ['module' => 'settings', 'action' => 'update']);
    }

    public function test_settings_are_validated_by_type(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->putJson('/api/v1/admin/settings', ['settings' => [
            'color_primary' => 'purple',
            'contact_email' => 'not-an-email',
            'appointment_duration' => '17',
            'appointment_working_days' => [9],
            'not_a_setting' => 'x',
        ]])->assertStatus(422)->assertJsonValidationErrors([
            'settings.color_primary', 'settings.contact_email', 'settings.appointment_duration',
            'settings.appointment_working_days.0', 'settings.not_a_setting',
        ]);
    }

    public function test_secret_settings_are_encrypted_and_never_returned(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->putJson('/api/v1/admin/settings', ['settings' => ['mail_password' => 'smtp-secret']])->assertOk();

        $stored = Setting::query()->where('key', 'mail_password')->value('value');
        $this->assertNotSame('smtp-secret', $stored);
        $this->assertSame('smtp-secret', Setting::query()->where('key', 'mail_password')->first()->typedValue());

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/settings')->assertOk();
        $this->assertStringNotContainsString('smtp-secret', $response->getContent());

        // Saving an empty value keeps the existing secret.
        $this->actingAs($admin)->putJson('/api/v1/admin/settings', ['settings' => ['mail_password' => null]])->assertOk();
        $this->assertSame('smtp-secret', Setting::query()->where('key', 'mail_password')->first()->typedValue());
    }

    public function test_spa_shell_renders_server_side_meta_from_settings(): void
    {
        if (! file_exists(public_path('spa/index.html'))) {
            $this->markTestSkipped('Frontend build not present.');
        }

        Setting::query()->where('key', 'seo_default_title')->first()->forceFill(['value' => 'Server <Rendered> Title'])->save();
        app(SettingService::class)->flush();

        $this->get('/')->assertOk()->assertSee('<title>Server &lt;Rendered&gt; Title</title>', false);
        $this->get('/admin/login')->assertOk()->assertSee('noindex, nofollow', false);
    }
}
