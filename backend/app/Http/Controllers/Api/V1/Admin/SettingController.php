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

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->settings->adminSettings());
    }

    public function update(SettingsUpdateRequest $request, ActivityLogger $logger): JsonResponse
    {
        $changed = $this->settings->update($request->validated('settings'));

        if ($changed) {
            $logger->log('update', 'settings', description: 'Settings updated', properties: ['keys' => $changed]);
        }

        return ApiResponse::success($this->settings->adminSettings(), 'Settings saved.');
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
