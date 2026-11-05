<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;

class TimelineItemRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'year' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:190'],
            'icon' => $this->icon(),
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => $this->image(),
            'is_active' => ['boolean'],
        ];
    }
}
