<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starter legal pages (editable in Admin > Pages). Never overwrites existing pages.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'subtitle' => 'How we collect, use and protect your information.',
                'content' => '<h2>Information we collect</h2><p>We collect the details you provide when you contact us, book an appointment or submit an application, such as your name, email address, phone number and study preferences.</p><h2>How we use it</h2><p>We use your information only to respond to your enquiries and to provide our counselling and application services.</p><h2>Contact</h2><p>For any privacy question, please contact us using the details on our Contact page.</p>',
            ],
            'terms-and-conditions' => [
                'title' => 'Terms & Conditions',
                'subtitle' => 'The terms that apply when you use our website and services.',
                'content' => '<h2>Use of this website</h2><p>The information on this website is provided for general guidance. Admission and visa decisions are made solely by universities and immigration authorities.</p><h2>Appointments</h2><p>Appointments are subject to counsellor availability and our cancellation policy.</p>',
            ],
        ];

        foreach ($pages as $slug => $data) {
            Page::query()->firstOrCreate(['slug' => $slug], $data + [
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
            ]);
        }
    }
}
