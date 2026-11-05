<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\ContentRequest;

class BlogTagRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'slug' => $this->slug('blog_tags'),
        ];
    }
}
