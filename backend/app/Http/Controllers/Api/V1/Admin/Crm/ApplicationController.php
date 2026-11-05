<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    use ManagesNotes;

    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'status' => ['nullable', Rule::enum(ApplicationStatus::class)],
            'destination_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filtered = fn () => Application::query()
            ->when($params['destination_id'] ?? null, fn ($q, $d) => $q->where('destination_id', $d))
            ->when($params['assigned_to'] ?? null, fn ($q, $a) => $q->where('assigned_to', $a))
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%")));

        $page = $filtered()->when($params['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->with(['destination:id,name', 'university:id,name', 'course:id,name', 'assignee:id,name'])->withCount('documents')
            ->latest('id')->paginate($params['per_page'] ?? 20);

        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
            'counts' => $filtered()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Application::query()->with([
            'destination:id,name', 'university:id,name,slug', 'course:id,name,slug', 'assignee:id,name', 'lead:id,name,status',
            'documents', 'notes.user:id,name',
        ])->findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $application = Application::query()->findOrFail($id);
        $application->update($request->validate([
            'status' => ['sometimes', Rule::enum(ApplicationStatus::class)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ]));

        return ApiResponse::success($application->load('assignee:id,name'), 'Application updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        Application::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'Application deleted.');
    }

    /**
     * Streams a private document to an authorised admin. Files live outside the web
     * root; there is no public URL. Every download is written to the audit log.
     */
    public function download(int $id, int $documentId, ActivityLogger $logger): StreamedResponse
    {
        $document = ApplicationDocument::query()->where('application_id', $id)->findOrFail($documentId);
        $disk = Storage::disk('documents');
        abort_unless($disk->exists($document->path), 404);

        $logger->log('download', 'applications', $document->application, "Downloaded {$document->type->label()} ({$document->original_name})");

        return $disk->download($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    protected function findForNotes(int $id): Model
    {
        return Application::query()->findOrFail($id);
    }
}
