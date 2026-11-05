<?php

namespace App\Http\Requests\Admin\Terms;

use Illuminate\Foundation\Http\FormRequest;

/** Entry of an admin-managed list (category, degree type, intake …). Access is checked by the route group. */
class TermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
