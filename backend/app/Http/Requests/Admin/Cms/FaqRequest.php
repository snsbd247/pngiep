<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;

class FaqRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', 'string', 'max:50'],
            'page_key' => ['nullable', 'string', 'max:50', 'alpha_dash'],
            'status' => $this->status(),
        ];
    }
}
