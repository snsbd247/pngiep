<?php

namespace App\Http\Requests\Admin\Directory;

use App\Http\Requests\Admin\ContentRequest;
use App\Support\SafeUrl;
use App\Support\SectionSchema;
use Illuminate\Validation\Rule;

class UniversityRequest extends ContentRequest
{
    public function rules(): array
    {
        $count = ['nullable', 'integer', 'min:0', 'max:10000000'];

        return [
            'destination_id' => ['required', 'integer', Rule::exists('destinations', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:190'],
            'slug' => $this->slug('universities'),
            'logo' => $this->image(),
            'thumbnail' => $this->image(),
            'cover_image' => $this->image(),
            'type' => ['required', Rule::in(['public', 'private'])],
            'city' => ['nullable', 'string', 'max:120'],
            'campus_type' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'established_year' => ['nullable', 'integer', 'between:1000,'.(date('Y') + 1)],
            'world_ranking' => $count,
            'national_ranking' => $count,
            'student_count' => $count,
            'international_student_count' => $count,
            'currency_code' => ['nullable', 'string', 'size:3', 'alpha'],
            'tuition_fee_min' => ['nullable', 'numeric', 'min:0'],
            'tuition_fee_max' => ['nullable', 'numeric', 'min:0', 'gte:tuition_fee_min'],
            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'intakes' => ['nullable', 'array', 'max:12'],
            'intakes.*' => ['string', 'max:60'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'about' => ['nullable', 'string', 'max:100000'],
            'highlights' => ['nullable', 'array', 'max:15'],
            'highlights.*' => ['string', 'max:190'],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => ['string', 'max:500', new SafeUrl],
            'video_url' => $this->youtube(),
            'is_featured' => ['boolean'],
            'status' => $this->status(),
            ...$this->seo(),
            ...SectionSchema::listRules((array) $this->input('sections', [])),
        ];
    }
}
