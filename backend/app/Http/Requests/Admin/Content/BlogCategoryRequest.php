<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\ContentRequest;
use Illuminate\Validation\Rule;

class BlogCategoryRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => $this->slug('blog_categories'),
            'type' => ['required', Rule::in(['blog', 'notice'])],
            'icon' => $this->icon(),
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
