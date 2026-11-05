<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\ContentRequest;
use App\Support\SafeUrl;
use Illuminate\Validation\Rule;

class GalleryAlbumRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => $this->slug('gallery_albums'),
            'gallery_category_id' => ['nullable', 'integer', Rule::exists('gallery_categories', 'id')->where('type', 'photo')],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_image' => $this->image(),
            'event_date' => ['nullable', 'date'],
            'photos' => ['nullable', 'array', 'max:200'],
            'photos.*' => ['string', 'max:500', new SafeUrl],
            'is_featured' => ['boolean'],
            'status' => $this->status(),
        ];
    }
}
