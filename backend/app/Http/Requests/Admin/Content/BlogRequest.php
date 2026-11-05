<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\ContentRequest;
use Illuminate\Validation\Rule;

class BlogRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => $this->slug('blogs'),
            'type' => ['required', Rule::in(['blog', 'notice'])],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'destination_id' => ['nullable', 'integer', Rule::exists('destinations', 'id')->whereNull('deleted_at')],
            'author_name' => ['nullable', 'string', 'max:120'],
            'featured_image' => $this->image(),
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'max:200000'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'status' => $this->status(),
            ...$this->seo(),
        ];
    }
}
