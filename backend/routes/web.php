<?php

use App\Http\Controllers\SpaController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
|--------------------------------------------------------------------------
| SPA shell
|--------------------------------------------------------------------------
| Every non-API, non-file URL returns the React build's index.html with
| server-rendered <title>, meta description, canonical and Open Graph tags
| injected, so crawlers and link previews see correct metadata without
| needing a Node.js server.
*/

// Crawler files: no session or XSRF cookies (a cacheable response must not set cookies,
// and search engines do not need them).
Route::withoutMiddleware([
    StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class,
    AddQueuedCookiesToResponse::class, EncryptCookies::class,
])->group(function () {
    Route::get('robots.txt', [SpaController::class, 'robots']);
    Route::get('favicon.ico', [SpaController::class, 'favicon']);
    Route::get('sitemap.xml', [SpaController::class, 'sitemap']);
});

// Canonical destination URLs are /study-in-{slug}.
Route::get('destinations/{slug}', [SpaController::class, 'destinationRedirect'])->where('slug', '[a-z0-9-]+');

Route::get('/{path?}', [SpaController::class, 'show'])
    ->where('path', '^(?!api/|sanctum/|storage/|spa/|up$).*$')
    ->name('spa');
