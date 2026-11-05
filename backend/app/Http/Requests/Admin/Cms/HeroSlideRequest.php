<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;

class HeroSlideRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'badge' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:190'],
            'highlight' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'image' => $this->image(),
            'mobile_image' => $this->image(),
            'primary_button_text' => ['nullable', 'string', 'max:60'],
            'primary_button_url' => $this->link(),
            'secondary_button_text' => ['nullable', 'string', 'max:60'],
            'secondary_button_url' => $this->link(),
            'video_url' => $this->link(),
            'video_label' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ];
    }
}
