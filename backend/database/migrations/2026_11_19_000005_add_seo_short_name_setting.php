<?php

use Database\Seeders\SettingSeeder;
use Illuminate\Database\Migrations\Migration;

/** Adds Settings › SEO › "Short site name in page titles" (existing values are untouched). */
return new class extends Migration
{
    public function up(): void
    {
        (new SettingSeeder)->run();
        app(\App\Services\SettingService::class)->flush();
    }

    public function down(): void
    {
        \App\Models\Setting::query()->where('key', 'seo_short_name')->delete();
    }
};
