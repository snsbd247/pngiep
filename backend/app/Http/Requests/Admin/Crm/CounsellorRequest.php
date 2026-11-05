<?php

namespace App\Http\Requests\Admin\Crm;

use App\Http\Requests\Admin\ContentRequest;
use App\Models\Role;
use Illuminate\Validation\Rule;

class CounsellorRequest extends ContentRequest
{
    public function rules(): array
    {
        $counsellorRole = Role::query()->where('slug', 'counsellor')->value('id');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => $this->slug('counsellors'),
            'photo' => $this->image(),
            'designation' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'experience_years' => ['nullable', 'integer', 'between:0,60'],
            'languages' => ['nullable', 'array', 'max:10'],
            'languages.*' => ['string', 'max:40'],
            'slot_duration' => ['nullable', Rule::in([15, 30, 45, 60])],
            // The admin login a counsellor uses to see their own appointments.
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role_id', $counsellorRole)->whereNull('deleted_at'),
                Rule::unique('counsellors', 'user_id')->ignore($this->recordId())],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'availabilities' => ['nullable', 'array', 'max:50'],
            'availabilities.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'availabilities.*.start_time' => ['required', 'date_format:H:i'],
            'availabilities.*.end_time' => ['required', 'date_format:H:i', 'after:availabilities.*.start_time'],
            'time_offs' => ['nullable', 'array', 'max:100'],
            'time_offs.*.date' => ['required', 'date_format:Y-m-d'],
            'time_offs.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:time_offs.*.end_time'],
            'time_offs.*.end_time' => ['nullable', 'date_format:H:i', 'required_with:time_offs.*.start_time', 'after:time_offs.*.start_time'],
            'time_offs.*.reason' => ['nullable', 'string', 'max:190'],
            'status' => $this->status(),
        ];
    }

    public function messages(): array
    {
        return ['availabilities.*.end_time.after' => 'The end time must be after the start time.', 'user_id.exists' => 'Choose a user with the Counsellor role.'];
    }

    /** Overlapping windows on the same day would create duplicate slots. */
    public function after(): array
    {
        return [function ($validator) {
            foreach (collect($this->input('availabilities', []))->groupBy('day_of_week') as $day => $windows) {
                $sorted = $windows->sortBy('start_time')->values();
                for ($i = 1; $i < $sorted->count(); $i++) {
                    if (($sorted[$i]['start_time'] ?? '') < ($sorted[$i - 1]['end_time'] ?? '')) {
                        $validator->errors()->add('availabilities', 'Time windows on the same day must not overlap.');

                        return;
                    }
                }
            }
        }];
    }
}
