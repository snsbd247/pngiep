<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Every seeder is idempotent, so `php artisan db:seed --force` is safe in production.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            SettingSeeder::class,
            BrandingSeeder::class,
            PageSeeder::class,
            ContentSeeder::class,
            PartnerDirectorySeeder::class,
            TermSeeder::class,
            EmailTemplateSeeder::class,
            MenuSeeder::class,
        ]);

        // Demo courses / stories / testimonials / gallery / blog until real content is added.
        // Remove it all with: php artisan app:demo-content remove
        $this->call(DemoDirectorySeeder::class);

        // Sample enquiries / appointments / applications to preview the admin dashboard. Local only:
        // never fill a live CRM with fictitious students.
        if (app()->environment('local')) {
            $this->call(DemoCrmSeeder::class);
        }
    }
}
