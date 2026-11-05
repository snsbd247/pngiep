<?php

namespace App\Http\Requests\Admin\Directory;

use App\Http\Requests\Admin\ContentRequest;
use App\Support\SectionSchema;
use Illuminate\Validation\Rule;

class DestinationRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => $this->slug('destinations'),
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'destination_category_id' => ['nullable', 'integer', Rule::exists('terms', 'id')->where('taxonomy', 'destination_category')],
            'tagline' => ['nullable', 'string', 'max:190'],
            'capital' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', 'string', 'max:120'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'overview' => ['nullable', 'string', 'max:100000'],
            'thumbnail' => $this->image(),
            'hero_image' => $this->image(),
            'currency_code' => ['nullable', 'string', 'size:3', 'alpha'],
            'tuition_fee_min' => ['nullable', 'numeric', 'min:0'],
            'tuition_fee_max' => ['nullable', 'numeric', 'min:0', 'gte:tuition_fee_min'],
            'living_cost_min' => ['nullable', 'numeric', 'min:0'],
            'living_cost_max' => ['nullable', 'numeric', 'min:0', 'gte:living_cost_min'],
            'post_study_work' => ['nullable', 'string', 'max:190'],
            'intakes' => ['nullable', 'array', 'max:12'],
            'intakes.*' => ['string', 'max:60'],
            'quick_facts' => ['nullable', 'array', 'max:12'],
            'quick_facts.*.icon' => $this->icon(),
            'quick_facts.*.value' => ['required', 'string', 'max:60'],
            'quick_facts.*.label' => ['required', 'string', 'max:120'],
            'video_url' => $this->youtube(),
            'is_featured' => ['boolean'],
            'status' => $this->status(),
            ...$this->seo(),
            ...SectionSchema::listRules((array) $this->input('sections', [])),
        ];
    }
}
