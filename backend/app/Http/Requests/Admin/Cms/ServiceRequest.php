<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;
use App\Support\SectionSchema;

class ServiceRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => $this->slug('services'),
            'icon' => $this->icon(),
            'image' => $this->image(),
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:100000'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_url' => $this->link(),
            'is_bookable' => ['boolean'],
            'is_featured' => ['boolean'],
            'status' => $this->status(),
            ...$this->seo(),
            ...SectionSchema::listRules((array) $this->input('sections', [])),
        ];
    }
}
