<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\StaffMailbox;
use App\Support\ApiResponse;
use App\Support\UserSessions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

    public function store(UserRequest $request, StaffMailbox $mailboxes): JsonResponse
    {
        $this->guardRoleAssignment($request);
        $user = User::query()->create(Arr::except($request->validated(), 'mailbox'));
        $mailbox = $this->syncMailbox($request, $user, $mailboxes);

        return ApiResponse::success(new UserResource($user->load('role')), $this->message('User created.', $mailbox), 201, ['mailbox' => $mailbox]);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user->load('role')));
    }

    public function update(UserRequest $request, User $user, StaffMailbox $mailboxes): JsonResponse
    {
        Gate::authorize('update', $user);
        $this->guardRoleAssignment($request);

        $data = Arr::except($request->validated(), 'mailbox');
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        // Prevent locking yourself out.
        if ($user->is($request->user())) {
            unset($data['role_id'], $data['is_active']);
        }

        $user->update($data);
        if (isset($data['password'])) {
            // An admin set a new password: sign the user out everywhere (their own browser too,
            // unless they are editing themselves).
            UserSessions::endOthers($user, $user->is($request->user()) ? $request->session()->getId() : null);
        }
        $mailbox = $this->syncMailbox($request, $user, $mailboxes);

        return ApiResponse::success(new UserResource($user->load('role')), $this->message('User updated.', $mailbox), meta: ['mailbox' => $mailbox]);
    }

    public function destroy(User $user, StaffMailbox $mailboxes): JsonResponse
    {
        Gate::authorize('delete', $user);
        // Only the link goes; the mailbox and its mail stay on cPanel (delete it separately if needed).
        $mailboxes->unlink($user);
        $user->delete();

        return ApiResponse::success(null, 'User deleted.');
    }

    /**
     * Mailbox changes only when the field is sent and the admin may manage mailboxes;
     * otherwise the current link is left as it is.
     */
    private function syncMailbox(UserRequest $request, User $user, StaffMailbox $mailboxes): ?array
    {
        if (! $request->has('mailbox') || ! $request->user()->can('mailboxes.manage')) {
            return null;
        }

        return $mailboxes->sync($user, $request->boolean('mailbox'));
    }

    private function message(string $base, ?array $mailbox): string
    {
        return match ($mailbox['status'] ?? null) {
            'created' => "{$base} Mailbox {$mailbox['mailbox']} was created.",
            'linked' => "{$base} Mailbox {$mailbox['mailbox']} is linked.",
            'error' => "{$base} The mailbox could not be set up: {$mailbox['error']}",
            default => $base,
        };
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
