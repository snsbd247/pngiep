<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Services\ActivityLogger;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * Raw HTML/JS injected into public pages. It runs on the same site as the admin panel, so only
     * a Super Admin may change it (otherwise an Admin could act with a Super Admin's session).
     */
    private const SUPER_ADMIN_ONLY = ['analytics_head_code'];

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->present($request));
    }

    public function update(SettingsUpdateRequest $request, ActivityLogger $logger): JsonResponse
    {
        $values = $request->validated('settings');

        if (! $request->user()->isSuperAdmin()) {
            foreach (self::SUPER_ADMIN_ONLY as $key) {
                if (array_key_exists($key, $values) && trim((string) $values[$key]) !== trim((string) $this->settings->get($key))) {
                    return ApiResponse::error('Only a Super Admin can change the analytics code.', 403);
                }
                unset($values[$key]);
            }
        }

        $changed = $this->settings->update($values);

        if ($changed) {
            $logger->log('update', 'settings', description: 'Settings updated', properties: ['keys' => $changed]);
        }

        return ApiResponse::success($this->present($request), 'Settings saved.');
    }

    /** Admin settings, with Super-Admin-only fields marked read-only for everyone else. */
    private function present(Request $request): array
    {
        $settings = $this->settings->adminSettings();
        if ($request->user()?->isSuperAdmin()) {
            return $settings;
        }

        return array_map(fn (array $group) => array_map(
            fn (array $s) => in_array($s['key'], self::SUPER_ADMIN_ONLY, true) ? $s + ['locked' => true] : $s,
            $group,
        ), $settings);
    }

    /** Sends a test email using the saved SMTP settings. */
    public function testMail(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            Mail::raw('This is a test email. Your SMTP settings are working.', function ($message) use ($data) {
                $message->to($data['email'])->subject('SMTP test email');
            });
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error('Could not send the test email: '.$e->getMessage(), 422);
        }

        return ApiResponse::success(null, 'Test email sent to '.$data['email'].'.');
    }
}
