<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishToFacebook;
use App\Services\FacebookPublisher;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/** Facebook auto-post: manual "post now / retry" for one entry, and the settings connection test. */
class FacebookController extends Controller
{
    public function __construct(private FacebookPublisher $facebook) {}

    public function news(int $id): JsonResponse
    {
        return $this->postNow('news', $id);
    }

    public function blog(int $id): JsonResponse
    {
        return $this->postNow('blogs', $id);
    }

    public function test(): JsonResponse
    {
        try {
            $name = $this->facebook->testConnection();
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(['page' => $name], "Connected to the Facebook Page \"{$name}\".");
    }

    /** Opt the entry in, clear an earlier error and queue it (also for entries older than the feature). */
    private function postNow(string $resource, int $id): JsonResponse
    {
        $model = FacebookPublisher::MODELS[$resource];
        $entry = $model::query()->findOrFail($id);

        if ($entry->facebook_post_id) {
            return ApiResponse::error('This entry is already on Facebook.', 422);
        }
        if (! $this->facebook->configured()) {
            return ApiResponse::error('Set up Facebook first in Settings › Facebook.', 422);
        }

        $entry->forceFill(['facebook_post' => true, 'facebook_error' => null])->saveQuietly();

        if (! $this->facebook->isDue($entry)) {
            return ApiResponse::success(null, 'It will be posted to Facebook when it is published.');
        }

        PublishToFacebook::dispatch($model, $entry->getKey());

        return ApiResponse::success(null, 'Queued for Facebook. It usually appears within a minute or two.');
    }
}
