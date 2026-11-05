<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\LeadSource;
use App\Models\NewsletterSubscriber;
use App\Services\LeadService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /** POST /api/v1/newsletter. Always answers the same way, so it can't be used to probe emails. */
    public function subscribe(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $subscriber = NewsletterSubscriber::query()->updateOrCreate(
            ['email' => strtolower($data['email'])],
            ['name' => $data['name'] ?? null, 'status' => 'subscribed', 'ip_address' => $request->ip()],
        );

        if ($subscriber->wasRecentlyCreated) {
            $leads->capture(LeadSource::Newsletter, ['email' => $data['email'], 'name' => $data['name'] ?? null], notify: false);
        }

        return ApiResponse::success(null, 'Thank you for subscribing!');
    }
}
