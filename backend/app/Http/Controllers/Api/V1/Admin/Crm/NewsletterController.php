<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;
        $page = NewsletterSubscriber::query()->when($search, fn ($q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->latest('id')->paginate(25, ['id', 'email', 'name', 'status', 'created_at']);

        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        NewsletterSubscriber::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'Subscriber removed.');
    }

    /** CSV export for email marketing tools. */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'name', 'status', 'subscribed_at']);
            NewsletterSubscriber::query()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    // Prefix formula-like values to prevent CSV injection in spreadsheet apps.
                    $safe = fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'".$v : $v;
                    fputcsv($out, [$safe($row->email), $safe($row->name), $row->status, $row->created_at?->toDateTimeString()]);
                }
            });
            fclose($out);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
