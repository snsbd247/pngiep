<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;

class StatisticRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:50', 'alpha_dash'],
            'label' => ['required', 'string', 'max:120'],
            'value' => ['required', 'string', 'max:50'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'icon' => $this->icon(),
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
