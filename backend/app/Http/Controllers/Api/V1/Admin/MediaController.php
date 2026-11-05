<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media, private readonly ActivityLogger $logger) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'folder' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:image,document'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $items = Media::query()
            ->with('uploader:id,name')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('original_name', 'like', "%{$s}%")->orWhere('title', 'like', "%{$s}%")->orWhere('alt', 'like', "%{$s}%")))
            ->when($filters['folder'] ?? null, fn ($q, $f) => $q->where('folder', $f))
            ->when(($filters['type'] ?? null) === 'image', fn ($q) => $q->where('mime_type', 'like', 'image/%'))
            ->when(($filters['type'] ?? null) === 'document', fn ($q) => $q->where('mime_type', 'not like', 'image/%'))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 24);

        return ApiResponse::paginated(MediaResource::collection($items), extraMeta: [
            'folders' => Media::query()->distinct()->orderBy('folder')->pluck('folder'),
        ]);
    }

    public function store(MediaUploadRequest $request): JsonResponse
    {
        $folder = $request->validated('folder') ?: 'general';

        $items = collect($request->file('files'))
            ->map(fn ($file) => $this->media->store($file, $folder, $request->user()->id));

        $this->logger->log('upload', 'media', description: $items->count().' file(s) uploaded', properties: ['ids' => $items->pluck('id')]);

        return ApiResponse::created(MediaResource::collection($items)->resolve($request), 'Upload complete.');
    }

    public function update(Request $request, Media $medium): JsonResponse
    {
        $medium->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9 _-]+$/'],
        ]));

        return ApiResponse::success(new MediaResource($medium->load('uploader:id,name')), 'Media updated.');
    }

    public function destroy(Media $medium): JsonResponse
    {
        $this->logger->log('delete', 'media', $medium, "Deleted {$medium->original_name}");
        $this->media->delete($medium);

        return ApiResponse::success(null, 'Media deleted.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']])['ids'];

        $items = Media::query()->whereIn('id', $ids)->get();
        $items->each(fn (Media $m) => $this->media->delete($m));
        $this->logger->log('delete', 'media', description: $items->count().' file(s) deleted', properties: ['ids' => $items->pluck('id')]);

        return ApiResponse::success(null, $items->count().' file(s) deleted.');
    }
}
