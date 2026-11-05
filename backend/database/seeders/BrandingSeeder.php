<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Installs the bundled company logo, favicon and social share image (resources/branding) into the public
 * media disk and selects them in Settings, unless an admin already chose others.
 */
class BrandingSeeder extends Seeder
{
    private const FILES = [
        'site_logo' => 'logo.png',
        'site_favicon' => 'favicon.png',
        'seo_default_og_image' => 'og-image.jpg',
    ];

    public function run(): void
    {
        foreach (self::FILES as $key => $file) {
            $source = resource_path("branding/{$file}");
            $setting = Setting::query()->where('key', $key)->first();
            if (! $setting || filled($setting->value) || ! is_file($source)) {
                continue;
            }

            $path = "branding/{$file}";
            Storage::disk('public')->put($path, file_get_contents($source));
            $setting->setTypedValue($path);
            $setting->save();
        }

        app(SettingService::class)->flush();
    }
}
