<?php

namespace App\Http\Middleware;

use App\Support\CspNonce;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function __construct(private readonly CspNonce $nonce) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->policy($request));
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            // Browsers ignore (and warn about) COOP on plain HTTP origins.
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }

        return $response;
    }

    /**
     * Scripts: only tags carrying this request's nonce (the app bundle, the base-path boot
     * script, an analytics snippet) and what they load ('strict-dynamic'). 'unsafe-inline'
     * and https: are ignored by browsers that support nonces; they only keep very old
     * browsers working. Images, video, API calls and styles stay open to HTTPS because
     * content uses external images (Unsplash, YouTube thumbnails) and analytics beacons.
     */
    private function policy(Request $request): string
    {
        $nonce = $this->nonce->value();
        $webmail = $this->origin((string) config('services.cpanel.webmail_url'));

        $directives = [
            'default-src' => "'self'",
            'script-src' => "'nonce-{$nonce}' 'strict-dynamic' 'unsafe-inline' https:",
            'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
            'font-src' => "'self' data: https://fonts.gstatic.com",
            'img-src' => "'self' data: blob: https:",
            'media-src' => "'self' https:",
            'connect-src' => "'self' https:",
            // YouTube embeds, Google Maps on the contact page, Tag Manager's <noscript> frame.
            'frame-src' => "'self' https://www.youtube-nocookie.com https://www.youtube.com https://www.google.com https://maps.google.com https://www.googletagmanager.com",
            'object-src' => "'none'",
            'base-uri' => "'self'",
            // The admin "Webmail" button posts a one-time login to the cPanel Webmail host.
            'form-action' => trim("'self' {$webmail}"),
            'frame-ancestors' => "'self'",
        ];

        $csp = collect($directives)->map(fn ($value, $name) => "{$name} {$value}")->implode('; ');

        return $request->isSecure() ? "{$csp}; upgrade-insecure-requests" : $csp;
    }

    private function origin(string $url): string
    {
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
