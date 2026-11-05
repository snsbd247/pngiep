<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.seed_admin.email');

        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        $password = config('app.seed_admin.password') ?: Str::password(16);

        User::query()->create([
            'name' => config('app.seed_admin.name'),
            'email' => $email,
            'password' => $password,
            'role_id' => Role::query()->where('slug', Role::SUPER_ADMIN)->value('id'),
            'is_active' => true,
        ])->forceFill(['email_verified_at' => now()])->save();

        if (! config('app.seed_admin.password')) {
            $this->command?->warn("Super admin created: {$email} / {$password}  (store it safely, then change it)");
        }
    }
}
