<?php

namespace App\Support;

/**
 * One random nonce per request for the Content-Security-Policy. Only <script> tags that
 * carry it may run (plus whatever those scripts load, via 'strict-dynamic'), so injected
 * markup cannot execute JavaScript even if it ever slipped past the HTML filter.
 * Registered as a scoped singleton (AppServiceProvider).
 */
class CspNonce
{
    /** Placeholder Vite writes into index.html (vite.config.js › html.cspNonce). */
    public const PLACEHOLDER = 'CSP_NONCE_PLACEHOLDER';

    private ?string $value = null;

    public function value(): string
    {
        return $this->value ??= base64_encode(random_bytes(18));
    }

    /** Add the nonce to every <script> tag in an admin-entered snippet (e.g. Google Analytics). */
    public function addTo(string $html): string
    {
        return preg_replace('/<script\b(?![^>]*\bnonce=)/i', '<script nonce="'.$this->value().'"', $html);
    }
}
