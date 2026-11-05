<?php

namespace App\Http\Requests\Admin\Directory;

use App\Enums\StudyLevel;
use App\Http\Requests\Admin\ContentRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'university_id' => ['required', 'integer', Rule::exists('universities', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:190'],
            'slug' => $this->slug('courses'),
            'subject' => ['nullable', 'string', 'max:120'],
            'degree_type' => ['nullable', 'string', 'max:50'],
            'study_level' => ['required', Rule::enum(StudyLevel::class)],
            'duration' => ['nullable', 'string', 'max:60'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'currency_code' => ['nullable', 'string', 'size:3', 'alpha'],
            'tuition_fee' => ['nullable', 'numeric', 'min:0'],
            'fee_period' => ['required', Rule::in(['year', 'semester', 'total'])],
            'intakes' => ['nullable', 'array', 'max:12'],
            'intakes.*' => ['string', 'max:60'],
            'application_deadline' => ['nullable', 'string', 'max:120'],
            'ielts_requirement' => ['nullable', 'numeric', 'between:0,9'],
            'entry_requirements' => ['nullable', 'string', 'max:50000'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:100000'],
            'highlights' => ['nullable', 'array', 'max:15'],
            'highlights.*' => ['string', 'max:190'],
            'image' => $this->image(),
            'is_featured' => ['boolean'],
            'status' => $this->status(),
            ...$this->seo(),
        ];
    }
}
