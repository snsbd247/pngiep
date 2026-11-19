<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\ContentRequest;

class NewsRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => $this->slug('news'),
            'author_name' => ['nullable', 'string', 'max:120'],
            'featured_image' => $this->image(),
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'max:200000'],
            'source_name' => ['nullable', 'string', 'max:120'],
            'source_url' => $this->link(),
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'facebook_post' => ['boolean'],
            'status' => $this->status(),
            ...$this->seo(),
        ];
    }
}
