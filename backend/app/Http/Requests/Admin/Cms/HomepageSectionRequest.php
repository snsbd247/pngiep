<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;
use App\Models\HomepageSection;
use App\Support\SectionSchema;

class HomepageSectionRequest extends ContentRequest
{
    public function rules(): array
    {
        $section = HomepageSection::query()->findOrFail($this->recordId());
        $schema = SectionSchema::homepageSection($section->key) ?? ['fields' => []];

        return [
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:190'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'image' => $this->image(),
            'button_text' => ['nullable', 'string', 'max:60'],
            'button_url' => $this->link(),
            'items_limit' => ['nullable', 'integer', 'min:1', 'max:24'],
            'is_active' => ['boolean'],
            ...SectionSchema::rules($schema['fields']),
        ];
    }
}
