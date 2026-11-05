<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /** GET /admin/profile/logins: the signed-in user's own sign-ins and sign-outs. */
    public function logins(Request $request): JsonResponse
    {
        $page = \App\Models\ActivityLog::query()->where('user_id', $request->user()->id)
            ->whereIn('action', ActivityLogController::GROUPS['login']['actions'])
            ->latest('id')->paginate(20, ['id', 'action', 'description', 'ip_address', 'user_agent', 'created_at']);

        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
        ]);
    }

    public function update(ProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email', 'phone', 'avatar']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        return ApiResponse::success((new UserResource($user->load('role')))->withPermissions(), 'Profile updated.');
    }
}
