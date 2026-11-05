<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // Restrictions that never break content: no plugins, no <base> hijacking, forms only to
        // this site, no framing by other sites. Script/style sources stay open because admins
        // may paste analytics snippets and pages embed YouTube / Google Maps.
        if (! $response->headers->has('Content-Security-Policy')) {
            $csp = "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
            $response->headers->set('Content-Security-Policy', $request->isSecure() ? "{$csp}; upgrade-insecure-requests" : $csp);
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            // Browsers ignore (and warn about) COOP on plain HTTP origins.
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }

        return $response;
    }
}
