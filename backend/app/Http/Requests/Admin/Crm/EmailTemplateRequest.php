<?php

namespace App\Http\Requests\Admin\Crm;

use App\Http\Requests\Admin\ContentRequest;

class EmailTemplateRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:50000'],
            'is_active' => ['boolean'],
        ];
    }
}
