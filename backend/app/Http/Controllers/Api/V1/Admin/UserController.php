<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->with('role')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->when($filters['role_id'] ?? null, fn ($q, $r) => $q->where('role_id', $r))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);

        return ApiResponse::paginated(UserResource::collection($users));
    }

    public function store(UserRequest $request): JsonResponse
    {
        $this->guardRoleAssignment($request);
        $user = User::query()->create($request->validated());

        return ApiResponse::created(new UserResource($user->load('role')), 'User created.');
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user->load('role')));
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);
        $this->guardRoleAssignment($request);

        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        // Prevent locking yourself out.
        if ($user->is($request->user())) {
            unset($data['role_id'], $data['is_active']);
        }

        $user->update($data);

        return ApiResponse::success(new UserResource($user->load('role')), 'User updated.');
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);
        $user->delete();

        return ApiResponse::success(null, 'User deleted.');
    }

    /** Only a Super Admin may create or promote another Super Admin. */
    private function guardRoleAssignment(UserRequest $request): void
    {
        $role = Role::query()->find($request->validated('role_id'));
        if ($role?->slug === Role::SUPER_ADMIN && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }
    }
}
