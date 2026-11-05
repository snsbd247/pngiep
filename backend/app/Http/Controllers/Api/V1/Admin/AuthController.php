<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('web')->attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();
        $this->logger->log('login', 'auth', $user, "{$user->name} signed in");

        return ApiResponse::success((new UserResource($user->load('role')))->withPermissions(), 'Signed in successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->logger->log('logout', 'auth', $user, "{$user->name} signed out");

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(null, 'Signed out successfully.');
    }

    /**
     * Current session state for the SPA: the user, or null when signed out.
     * Unlike /me this never returns 401, so checking the session is not logged as an error.
     */
    public function session(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();

        if (! $user?->is_active) {
            return ApiResponse::success(null);
        }

        return ApiResponse::success((new UserResource($user->load('role')))->withPermissions());
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success((new UserResource($request->user()->load('role')))->withPermissions());
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Always respond identically so the endpoint cannot be used to discover accounts.
        Password::sendResetLink($request->only('email'));

        return ApiResponse::success(null, 'If that email belongs to an account, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                $this->logger->log('password_reset', 'auth', $user, "{$user->name} reset their password", userId: $user->id);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return ApiResponse::success(null, 'Your password has been reset. You can now sign in.');
    }
}
