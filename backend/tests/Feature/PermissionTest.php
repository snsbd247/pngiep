<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_content_manager_cannot_access_settings_or_users(): void
    {
        $user = $this->userWithRole('content-manager');

        foreach (['settings', 'users', 'roles', 'activity-logs'] as $path) {
            $this->actingAs($user)->getJson("/api/v1/admin/{$path}")->assertForbidden();
        }
        $this->actingAs($user)->putJson('/api/v1/admin/settings', ['settings' => ['site_name' => 'x']])->assertForbidden();

        $this->actingAs($user)->getJson('/api/v1/admin/media')->assertOk();
        $this->actingAs($user)->getJson('/api/v1/admin/dashboard')->assertOk();
    }

    public function test_counsellor_only_has_dashboard_and_appointments(): void
    {
        $user = $this->userWithRole('counsellor');

        $this->actingAs($user)->getJson('/api/v1/admin/dashboard')->assertOk()
            ->assertJsonPath('data.recent_enquiries', null)
            ->assertJsonPath('data.recent_applications', null);
        $this->actingAs($user)->getJson('/api/v1/admin/media')->assertForbidden();
    }

    public function test_admin_cannot_manage_roles_but_super_admin_can(): void
    {
        $this->actingAs($this->userWithRole('admin'))->getJson('/api/v1/admin/roles')->assertForbidden();
        $this->actingAs($this->userWithRole('super-admin'))->getJson('/api/v1/admin/roles')->assertOk();
    }

    public function test_admin_cannot_create_or_edit_super_admins(): void
    {
        $admin = $this->userWithRole('admin');
        $super = $this->userWithRole('super-admin');
        $superRoleId = Role::query()->where('slug', Role::SUPER_ADMIN)->value('id');

        $this->actingAs($admin)->postJson('/api/v1/admin/users', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'role_id' => $superRoleId,
            'password' => 'Secret123', 'password_confirmation' => 'Secret123',
        ])->assertForbidden();

        $this->actingAs($admin)->putJson("/api/v1/admin/users/{$super->id}", [
            'name' => 'Renamed', 'email' => $super->email, 'role_id' => $superRoleId,
        ])->assertForbidden();

        $this->actingAs($admin)->deleteJson("/api/v1/admin/users/{$super->id}")->assertForbidden();
    }

    public function test_user_cannot_delete_themselves_or_change_own_role(): void
    {
        $super = $this->userWithRole('super-admin');
        $adminRoleId = Role::query()->where('slug', 'admin')->value('id');

        $this->actingAs($super)->deleteJson("/api/v1/admin/users/{$super->id}")->assertForbidden();

        $this->actingAs($super)->putJson("/api/v1/admin/users/{$super->id}", [
            'name' => 'Me', 'email' => $super->email, 'role_id' => $adminRoleId, 'is_active' => false,
        ])->assertOk();

        $this->assertTrue($super->fresh()->isSuperAdmin());
        $this->assertTrue($super->fresh()->is_active);
    }

    public function test_super_admin_role_permissions_are_immutable(): void
    {
        $super = $this->userWithRole('super-admin');
        $role = Role::query()->where('slug', Role::SUPER_ADMIN)->first();

        $this->actingAs($super)->putJson("/api/v1/admin/roles/{$role->id}", ['permissions' => []])->assertStatus(422);
    }

    public function test_role_permission_changes_take_effect(): void
    {
        $super = $this->userWithRole('super-admin');
        $cm = $this->userWithRole('content-manager');
        $role = Role::query()->where('slug', 'content-manager')->first();

        $this->actingAs($super)->putJson("/api/v1/admin/roles/{$role->id}", ['permissions' => ['dashboard.view']])->assertOk();

        $this->actingAs($cm->fresh())->getJson('/api/v1/admin/media')->assertForbidden();
    }
}
