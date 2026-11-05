<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\ContentRequest;
use Illuminate\Validation\Rule;

class GalleryVideoRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'youtube_url' => ['required', ...array_slice($this->youtube(), 1)],
            'gallery_category_id' => ['nullable', 'integer', Rule::exists('gallery_categories', 'id')->where('type', 'video')],
            'description' => ['nullable', 'string', 'max:2000'],
            'thumbnail' => $this->image(),
            'duration' => ['nullable', 'string', 'max:10', 'regex:/^\d{1,2}(:\d{2}){1,2}$/'],
            'is_featured' => ['boolean'],
            'status' => $this->status(),
        ];
    }

    public function messages(): array
    {
        return ['duration.regex' => 'Use mm:ss or hh:mm:ss, e.g. 03:45.'];
    }
}
