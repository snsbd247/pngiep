<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Global website data needed on every public page (settings + menus),
 * delivered in one request and cached server-side.
 */
class SiteController extends Controller
{
    public function __construct(private readonly SettingService $settings, private readonly MenuService $menus) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success([
            'settings' => $this->settings->publicSettings(),
            'menus' => $this->menus->tree(),
        ]);
    }

    public function settings(): JsonResponse
    {
        return ApiResponse::success($this->settings->publicSettings());
    }

    public function menus(): JsonResponse
    {
        return ApiResponse::success($this->menus->tree());
    }
}
