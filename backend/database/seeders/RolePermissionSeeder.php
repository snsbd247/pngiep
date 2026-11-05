<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

/**
 * Idempotent: safe to re-run after adding permissions to config/permissions.php.
 * Existing roles keep any permissions an admin granted; new defaults are added.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = Permissions::modules();

        foreach (Permissions::all() as $slug => $name) {
            Permission::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'module' => $modules[$slug]]);
        }

        // Remove permissions no longer defined in config.
        Permission::query()->whereNotIn('slug', array_keys(Permissions::all()))->delete();

        foreach (config('permissions.roles') as $slug => $definition) {
            $role = Role::query()->firstOrNew(['slug' => $slug]);
            $isNew = ! $role->exists;
            $role->fill(['name' => $definition['name'], 'description' => $definition['description']]);
            $role->is_system = true;
            $role->save();

            $ids = Permission::query()->whereIn('slug', Permissions::resolve($definition['permissions']))->pluck('id');
            $isNew ? $role->permissions()->sync($ids) : $role->permissions()->syncWithoutDetaching($ids);
        }
    }
}
