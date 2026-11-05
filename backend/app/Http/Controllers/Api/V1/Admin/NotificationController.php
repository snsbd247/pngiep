<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotifier;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/** The signed-in user's own notifications (header bell, Notifications pages, settings). */
class NotificationController extends Controller
{
    public function __construct(private readonly DashboardController $dashboard) {}

    /** GET /admin/notifications/summary: bell badge, latest items and things waiting for action. */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'unread' => $user->unreadNotifications()->count(),
            'latest' => $user->notifications()->latest()->limit(6)->get()->map(fn ($n) => $this->present($n)),
            'pending' => $this->dashboard->bell($request)->getData(true)['data'],
        ]);
    }

    /** GET /admin/notifications?filter=unread&type=enquiry */
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'filter' => ['nullable', 'in:all,unread'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(AdminNotifier::TYPES))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();
        $page = $user->notifications()
            ->when(($params['filter'] ?? null) === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($params['type'] ?? null, fn ($q, $t) => $q->where('data->type', $t))
            ->latest()->paginate($params['per_page'] ?? 20);

        return ApiResponse::success(collect($page->items())->map(fn ($n) => $this->present($n)), meta: [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
            'unread' => $user->unreadNotifications()->count(),
            'types' => collect(AdminNotifier::TYPES)->map(fn ($t, $key) => ['value' => $key, 'label' => $t[0]])->values(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $this->find($request, $id)->markAsRead();

        return ApiResponse::success(null, 'Marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return ApiResponse::success(null, 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->find($request, $id)->delete();

        return ApiResponse::success(null, 'Notification deleted.');
    }

    /** GET /admin/notifications/preferences: only the types this user can receive. */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success(collect(AdminNotifier::TYPES)
            ->filter(fn ($t, $key) => $user->can($t[1]) || ($key === 'appointment' && $user->can('appointments.view')))
            ->map(fn ($t, $key) => ['type' => $key, 'label' => $t[0], 'enabled' => (bool) ($user->notification_preferences[$key] ?? true)])
            ->values());
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate(['preferences' => ['required', 'array'], 'preferences.*' => ['boolean']]);
        $user = $request->user();
        $user->forceFill(['notification_preferences' => array_merge(
            $user->notification_preferences ?? [],
            array_intersect_key($data['preferences'], AdminNotifier::TYPES),
        )])->save();

        return ApiResponse::success(null, 'Notification settings saved.');
    }

    private function find(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }

    private function present(DatabaseNotification $n): array
    {
        return $n->data + ['id' => $n->id, 'read' => $n->read_at !== null, 'created_at' => $n->created_at?->toIso8601String()];
    }
}
