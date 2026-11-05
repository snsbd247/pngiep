<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts internal paths ("/contact", "#section"), storage paths ("media/x.webp"),
 * http(s) URLs and mailto:/tel: links. Rejects script-capable schemes such as
 * javascript: and data:, which would allow stored XSS through links and images.
 */
class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $normalised = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', $value));

        if (preg_match('/^[a-z][a-z0-9+.-]*:/', $normalised) && ! preg_match('/^(https?|mailto|tel):/', $normalised)) {
            $fail('The :attribute must be a web address or a path on this site.');
        }
    }
}
