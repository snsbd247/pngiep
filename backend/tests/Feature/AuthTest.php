<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_admin_can_sign_in_and_receives_permissions(): void
    {
        $user = $this->userWithRole('admin', ['email' => 'a@example.com']);

        $this->withHeaders($this->spaHeaders)
            ->postJson('/api/v1/admin/auth/login', ['email' => 'a@example.com', 'password' => 'Secret123'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role.slug', 'admin');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login', 'user_id' => $user->id]);
    }

    public function test_wrong_password_returns_validation_error_envelope(): void
    {
        $this->userWithRole('admin', ['email' => 'a@example.com']);

        $this->withHeaders($this->spaHeaders)
            ->postJson('/api/v1/admin/auth/login', ['email' => 'a@example.com', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['email']]);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $this->userWithRole('admin', ['email' => 'off@example.com', 'is_active' => false]);

        $this->withHeaders($this->spaHeaders)
            ->postJson('/api/v1/admin/auth/login', ['email' => 'off@example.com', 'password' => 'Secret123'])
            ->assertStatus(422);
    }

    public function test_deactivated_user_with_existing_session_is_rejected(): void
    {
        $user = $this->userWithRole('admin');
        $user->update(['is_active' => false]);

        $this->actingAs($user)->withHeaders($this->spaHeaders)
            ->getJson('/api/v1/admin/dashboard')
            ->assertForbidden();
    }

    public function test_session_endpoint_returns_null_when_signed_out(): void
    {
        $this->withHeaders($this->spaHeaders)
            ->getJson('/api/v1/admin/auth/session')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/auth/me')->assertUnauthorized()->assertJsonPath('success', false);
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }

    public function test_logout_ends_session_and_is_audited(): void
    {
        $user = $this->userWithRole('admin');

        $this->actingAs($user)->withHeaders($this->spaHeaders)
            ->postJson('/api/v1/admin/auth/logout')
            ->assertOk();

        $this->assertGuest('web');
        $this->assertSame(1, ActivityLog::query()->where('action', 'logout')->count());
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders($this->spaHeaders)->postJson('/api/v1/admin/auth/login', ['email' => 'x@example.com', 'password' => 'bad']);
        }

        $this->withHeaders($this->spaHeaders)
            ->postJson('/api/v1/admin/auth/login', ['email' => 'x@example.com', 'password' => 'bad'])
            ->assertStatus(429)
            ->assertJsonPath('success', false);
    }
}
