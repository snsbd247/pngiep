<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\StudyLevel;
use App\Http\Requests\Admin\ContentRequest;
use App\Support\SafeUrl;
use Illuminate\Validation\Rule;

class SuccessStoryRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:120'],
            'slug' => $this->slug('success_stories'),
            'photo' => $this->image(),
            'cover_image' => $this->image(),
            'destination_id' => ['nullable', 'integer', Rule::exists('destinations', 'id')->whereNull('deleted_at')],
            'university_id' => ['nullable', 'integer', Rule::exists('universities', 'id')->whereNull('deleted_at')],
            'story_category_id' => ['nullable', 'integer', Rule::exists('terms', 'id')->where('taxonomy', 'story_category')],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->whereNull('deleted_at')],
            'university_name' => ['nullable', 'string', 'max:190'],
            'course_name' => ['nullable', 'string', 'max:190'],
            'study_level' => ['nullable', Rule::enum(StudyLevel::class)],
            'intake' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'between:1990,'.(date('Y') + 2)],
            'outcome' => ['nullable', 'string', 'max:60'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'quote' => ['nullable', 'string', 'max:1000'],
            'story' => ['nullable', 'string', 'max:100000'],
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*' => ['string', 'max:500', new SafeUrl],
            'video_url' => $this->youtube(),
            'is_featured' => ['boolean'],
            'status' => $this->status(),
            ...$this->seo(),
        ];
    }
}
