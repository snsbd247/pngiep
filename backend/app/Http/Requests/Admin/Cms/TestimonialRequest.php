<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;

class TestimonialRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'photo' => $this->image(),
            'country' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'university' => ['nullable', 'string', 'max:190'],
            'course' => ['nullable', 'string', 'max:190'],
            'content' => ['required', 'string', 'max:2000'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'is_featured' => ['boolean'],
            'status' => $this->status(),
        ];
    }
}
