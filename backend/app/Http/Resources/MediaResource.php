<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $thumb = $this->variants['thumb'] ?? null;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'folder' => $this->folder,
            'path' => $this->path,
            'url' => $this->url,
            'thumb_url' => $thumb ? Storage::disk($this->disk)->url($thumb) : ($this->is_image ? $this->url : null),
            'filename' => $this->filename,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
            'title' => $this->title,
            'is_image' => $this->is_image,
            'uploaded_by' => $this->whenLoaded('uploader', fn () => $this->uploader?->only(['id', 'name'])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
