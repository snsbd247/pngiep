<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA shell
|--------------------------------------------------------------------------
| Every non-API, non-file URL returns the React build's index.html with
| server-rendered <title>, meta description, canonical and Open Graph tags
| injected, so crawlers and link previews see correct metadata without
| needing a Node.js server.
*/

Route::get('robots.txt', [SpaController::class, 'robots']);
Route::get('favicon.ico', [SpaController::class, 'favicon']);
Route::get('sitemap.xml', [SpaController::class, 'sitemap']);

// Canonical destination URLs are /study-in-{slug}.
Route::get('destinations/{slug}', [SpaController::class, 'destinationRedirect'])->where('slug', '[a-z0-9-]+');

Route::get('/{path?}', [SpaController::class, 'show'])
    ->where('path', '^(?!api/|sanctum/|storage/|spa/|up$).*$')
    ->name('spa');
