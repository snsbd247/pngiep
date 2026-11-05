<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /** Menu groups: Login / Content / User / System activities. */
    public const GROUPS = [
        'login' => ['actions' => ['login', 'logout', 'password_reset']],
        'users' => ['modules' => ['users', 'roles']],
        'system' => ['modules' => ['settings', 'email_templates', 'system', 'menus', 'menu_items']],
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer'],
            'group' => ['nullable', 'in:login,content,users,system'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->when($filters['module'] ?? null, fn ($q, $m) => $q->where('module', $m))
            ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($filters['user_id'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
            ->when($filters['group'] ?? null, fn ($q, $g) => match ($g) {
                'login' => $q->whereIn('action', self::GROUPS['login']['actions']),
                'users' => $q->whereIn('module', self::GROUPS['users']['modules']),
                'system' => $q->whereIn('module', self::GROUPS['system']['modules']),
                // Content = everything that is not sign-ins, people or system configuration.
                'content' => $q->whereNotIn('module', ['auth', ...self::GROUPS['users']['modules'], ...self::GROUPS['system']['modules']]),
            })
            ->latest('id')
            ->paginate($filters['per_page'] ?? 25);

        return ApiResponse::paginated(ActivityLogResource::collection($logs), extraMeta: [
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
        ]);
    }
}
