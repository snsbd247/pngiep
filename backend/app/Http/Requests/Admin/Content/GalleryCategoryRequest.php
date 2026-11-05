<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\ContentRequest;
use Illuminate\Validation\Rule;

class GalleryCategoryRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['photo', 'video'])],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash',
                Rule::unique('gallery_categories', 'slug')->where('type', $this->input('type'))->ignore($this->recordId())],
            'icon' => $this->icon(),
            'is_active' => ['boolean'],
        ];
    }
}
