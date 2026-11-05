<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Folder that contains artisan, app/, vendor/ … Default: one level above this public folder.
// cPanel "public_html" layout (docs/DEPLOYMENT.md, option B): change to __DIR__.'/../riths'.
$appPath = __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// The web root is wherever this file lives (backend/public or public_html).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
