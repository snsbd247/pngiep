<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Same-origin Referer so Sanctum treats requests as first-party (session-based) SPA calls. */
    protected array $spaHeaders = ['Referer' => 'http://localhost/admin', 'Accept' => 'application/json'];

    protected function seedFoundation(): void
    {
        $this->seed([RolePermissionSeeder::class, SettingSeeder::class]);
    }

    protected function userWithRole(string $roleSlug, array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'role_id' => Role::query()->where('slug', $roleSlug)->value('id'),
            'password' => 'Secret123',
        ]);
    }
}
