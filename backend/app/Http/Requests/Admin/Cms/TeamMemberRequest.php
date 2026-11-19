<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;

class TeamMemberRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => $this->slug('team_members'),
            'designation' => ['nullable', 'string', 'max:120'],
            'photo' => $this->image(),
            'bio' => ['nullable', 'string', 'max:2000'],
            'highlights' => ['nullable', 'array', 'max:12'],
            'highlights.*' => ['string', 'max:160'],
            'profile' => ['nullable', 'string', 'max:100000'],
            'message_title' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:100000'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'social_links' => ['nullable', 'array'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'social_links.x' => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.whatsapp' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            ...$this->seo(),
        ];
    }
}
