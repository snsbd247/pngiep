<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    /** Allowed types are checked by real MIME (file contents), not just the extension. */
    public const MIMES = 'jpg,jpeg,png,webp,gif,pdf';

    public const MAX_KB = 5120;

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'mimes:'.self::MIMES, 'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf', 'max:'.self::MAX_KB],
            'folder' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9 _-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.mimes' => 'Only JPG, PNG, WebP, GIF images and PDF files are allowed.',
            'files.*.max' => 'Each file must be 5 MB or smaller.',
        ];
    }
}
