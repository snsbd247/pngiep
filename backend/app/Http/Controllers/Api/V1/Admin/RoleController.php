<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()->with('permissions:id,slug')->withCount('users')->orderBy('id')->get();

        return ApiResponse::success([
            'roles' => RoleResource::collection($roles)->resolve(),
            'permissions' => Permission::query()->orderBy('module')->orderBy('id')->get(['slug', 'name', 'module'])->groupBy('module'),
        ]);
    }

    /** Update a role's permissions. The Super Admin role is immutable. */
    public function update(Request $request, Role $role, ActivityLogger $logger): JsonResponse
    {
        abort_if($role->slug === Role::SUPER_ADMIN, 422, 'The Super Admin role always has every permission.');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'slug')],
        ]);

        $role->update(collect($data)->only(['name', 'description'])->all());
        $role->permissions()->sync(Permission::query()->whereIn('slug', $data['permissions'])->pluck('id'));
        $logger->log('update', 'roles', $role, "Permissions updated for role {$role->name}", ['permissions' => $data['permissions']]);

        return ApiResponse::success(new RoleResource($role->load('permissions:id,slug')->loadCount('users')), 'Role updated.');
    }

    /** Lightweight list for user-form dropdowns. */
    public function options(): JsonResponse
    {
        return ApiResponse::success(Role::query()->orderBy('id')->get(['id', 'name', 'slug']));
    }
}
