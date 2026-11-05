<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

/**
 * Defines every admin-editable setting (group, type, label, visibility) with
 * starter values. Idempotent: existing values are never overwritten, so it is
 * safe to re-run after adding new settings.
 *
 * is_public = exposed to the website via GET /api/v1/site. Secrets are never public.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $order = 0;

        foreach ($this->definitions() as $group => $settings) {
            foreach ($settings as $key => $def) {
                $setting = Setting::query()->firstOrNew(['key' => $key]);
                $isNew = ! $setting->exists;

                $setting->fill([
                    'group' => $group,
                    'type' => $def['type'] ?? 'text',
                    'label' => $def['label'],
                    'help' => $def['help'] ?? null,
                    'options' => $def['options'] ?? null,
                    'is_public' => $def['public'] ?? true,
                    'is_encrypted' => $def['encrypted'] ?? false,
                    'sort_order' => $order++,
                ]);

                if ($isNew) {
                    $setting->setTypedValue($def['value'] ?? null);
                }

                $setting->save();
            }
        }

        app(SettingService::class)->flush();
    }

    private function definitions(): array
    {
        $durations = array_map(fn ($m) => ['value' => (string) $m, 'label' => "{$m} minutes"], [15, 30, 45, 60]);
        $weekdays = array_map(fn ($d, $name) => ['value' => $d, 'label' => $name], range(0, 6), ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
        $modes = [
            ['value' => 'in_person', 'label' => 'In person'],
            ['value' => 'online', 'label' => 'Online (video)'],
            ['value' => 'phone', 'label' => 'Phone call'],
        ];

        return [
            'general' => [
                'site_name' => ['label' => 'Website name', 'value' => 'PNG International Education Portal'],
                'site_tagline' => ['label' => 'Tagline', 'value' => 'Study in India, Australia, Dubai & Other Countries'],
                'site_logo' => ['label' => 'Logo', 'type' => 'image', 'help' => 'Shown in the header. Transparent PNG/WebP recommended.'],
                'logo_show_name' => ['label' => 'Show the website name next to the logo', 'type' => 'boolean', 'value' => true, 'help' => 'Turn off if the logo image already contains the full name in a readable size.'],
                'site_logo_light' => ['label' => 'Logo (light version)', 'type' => 'image', 'help' => 'Used on dark backgrounds such as the footer.'],
                'site_country' => ['label' => 'Country (flag)', 'value' => 'pg', 'help' => 'Two-letter country code, shown as a flag next to "Visit Website" in the admin header.'],
                'site_favicon' => ['label' => 'Favicon', 'type' => 'image', 'help' => 'Square image, at least 64×64.'],
            ],
            'header' => [
                'topbar_enabled' => ['label' => 'Show top utility bar', 'type' => 'boolean', 'value' => true],
                'topbar_social_label' => ['label' => 'Top bar social label', 'value' => 'Follow Us:'],
                'topbar_message' => ['label' => 'Top bar announcement (optional)'],
                'header_search_enabled' => ['label' => 'Show search icon', 'type' => 'boolean', 'value' => true],
                'header_cta_text' => ['label' => 'Header button text', 'value' => 'Book Appointment'],
                'header_cta_url' => ['label' => 'Header button link', 'value' => '/book-appointment'],
            ],
            'contact' => [
                'contact_phone' => ['label' => 'Phone', 'value' => '+675 7293 0003'],
                'contact_phone_secondary' => ['label' => 'Other phone numbers', 'value' => '+675 7292 9823, +675 7113 6560', 'help' => 'Separate several numbers with commas.'],
                'contact_whatsapp' => ['label' => 'WhatsApp number', 'value' => '+67572930003', 'help' => 'International format, digits only after +.'],
                'contact_email' => ['label' => 'Email', 'type' => 'email', 'value' => 'pngiepstudiesabroad2526@gmail.com'],
                'contact_address' => ['label' => 'Address', 'type' => 'textarea', 'value' => 'Section 52, Allotment 55, Kawai Dr, Gordons Commercial Area, Port Moresby, Papua New Guinea'],
                'contact_postal_address' => ['label' => 'Postal address', 'type' => 'textarea', 'value' => 'PO Box 1695, Port Moresby, National Capital District, Papua New Guinea'],
                'contact_location' => ['label' => 'Short location (top bar)', 'value' => 'Port Moresby, PNG'],
                'office_hours' => ['label' => 'Office hours', 'type' => 'textarea', 'value' => 'Mon – Fri, 8:00 AM – 4:30 PM', 'help' => 'Hidden on the website when empty.'],
                'contact_email_note' => ['label' => 'Email card note', 'value' => 'We respond within 24 hours'],
                'contact_chat_title' => ['label' => 'Chat card title', 'value' => 'Talk to Our Advisor'],
                'contact_chat_note' => ['label' => 'Chat card note', 'value' => 'Get instant support on WhatsApp'],
                'contact_inquiry_types' => ['label' => 'Contact form inquiry types', 'type' => 'textarea', 'value' => "General Enquiry\nUniversity Admission\nVisa Assistance\nScholarship Guidance\nAppointment\nPartnership", 'help' => 'One per line.'],
                'map_embed_url' => ['label' => 'Google Maps embed URL', 'type' => 'url', 'help' => 'From Google Maps > Share > Embed a map (the src URL only).'],
            ],
            'social' => [
                'social_facebook' => ['label' => 'Facebook', 'type' => 'url', 'value' => 'https://www.facebook.com/'],
                'social_linkedin' => ['label' => 'LinkedIn', 'type' => 'url', 'value' => 'https://www.linkedin.com/'],
                'social_instagram' => ['label' => 'Instagram', 'type' => 'url', 'value' => 'https://www.instagram.com/'],
                'social_youtube' => ['label' => 'YouTube', 'type' => 'url', 'value' => 'https://www.youtube.com/'],
                'social_tiktok' => ['label' => 'TikTok', 'type' => 'url'],
                'social_x' => ['label' => 'X (Twitter)', 'type' => 'url'],
            ],
            'appearance' => [
                'color_primary' => ['label' => 'Primary colour', 'type' => 'color', 'value' => '#1A3FA6'],
                'color_secondary' => ['label' => 'Secondary colour (dark)', 'type' => 'color', 'value' => '#0B1D52'],
                'color_accent' => ['label' => 'Accent colour', 'type' => 'color', 'value' => '#1E8A3E'],
            ],
            'seo' => [
                'seo_default_title' => ['label' => 'Default SEO title', 'value' => 'PNG International Education Portal | Study Abroad from Papua New Guinea'],
                'seo_default_description' => ['label' => 'Default meta description', 'type' => 'textarea', 'value' => 'PNG International Education Portal in Port Moresby helps students study in India, Australia, Dubai and other countries, with admissions, study visa, passport, NID & birth certificate assistance and airline ticketing.'],
                'seo_default_og_image' => ['label' => 'Default social share image', 'type' => 'image', 'help' => '1200×630 recommended.'],
                'google_site_verification' => ['label' => 'Google site verification code', 'public' => false],
                'analytics_head_code' => ['label' => 'Analytics code (inserted in <head>)', 'type' => 'code', 'public' => false, 'help' => 'Paste the full Google Analytics / Tag Manager snippet.'],
                'robots_txt' => ['label' => 'Custom robots.txt', 'type' => 'textarea', 'public' => false, 'help' => 'Leave empty to use the default.'],
            ],
            'appointment' => [
                'appointment_timezone' => ['label' => 'Booking timezone', 'value' => 'Pacific/Port_Moresby', 'help' => 'Time slots are shown and stored in this timezone (e.g. Asia/Dubai, Asia/Dhaka, Europe/London).'],
                'appointment_timezone_label' => ['label' => 'Timezone label shown to visitors', 'value' => 'PNG Time (UTC+10)'],
                'appointment_duration' => ['label' => 'Default appointment duration', 'type' => 'select', 'options' => $durations, 'value' => '30'],
                'appointment_advance_days' => ['label' => 'Bookable days in advance', 'type' => 'number', 'value' => 30],
                'appointment_min_notice_hours' => ['label' => 'Minimum booking notice (hours)', 'type' => 'number', 'value' => 12],
                'appointment_cancel_notice_hours' => ['label' => 'Cancellation notice (hours)', 'type' => 'number', 'value' => 24],
                'appointment_cancellation_policy' => ['label' => 'Cancellation policy text', 'type' => 'textarea', 'value' => 'Please cancel or reschedule at least 24 hours in advance.'],
                'appointment_working_days' => ['label' => 'Working days', 'type' => 'json', 'options' => $weekdays, 'value' => [1, 2, 3, 4, 5]],
                'appointment_auto_confirm' => ['label' => 'Auto-confirm new bookings', 'type' => 'boolean', 'value' => false, 'public' => false],
                'appointment_modes' => ['label' => 'Meeting modes offered', 'type' => 'json', 'options' => $modes, 'value' => ['in_person', 'online', 'phone']],
            ],
            'email' => [
                'mail_host' => ['label' => 'SMTP host', 'public' => false],
                'mail_port' => ['label' => 'SMTP port', 'type' => 'number', 'value' => 587, 'public' => false],
                'mail_username' => ['label' => 'SMTP username', 'public' => false],
                'mail_password' => ['label' => 'SMTP password', 'type' => 'password', 'public' => false, 'encrypted' => true, 'help' => 'Leave blank to keep the current password.'],
                'mail_encryption' => ['label' => 'Encryption', 'type' => 'select', 'public' => false, 'value' => 'tls',
                    'options' => [['value' => 'tls', 'label' => 'TLS (587)'], ['value' => 'ssl', 'label' => 'SSL (465)'], ['value' => 'none', 'label' => 'None']]],
                'mail_from_address' => ['label' => 'From email', 'type' => 'email', 'public' => false],
                'mail_from_name' => ['label' => 'From name', 'public' => false],
                'admin_notification_email' => ['label' => 'Admin notification email', 'type' => 'email', 'public' => false, 'help' => 'New leads, appointments and applications are sent here.'],
            ],
            'forms' => [
                'enquiry_destination_title' => ['label' => 'Destination enquiry form title', 'value' => 'Get Free Guidance for {name}', 'help' => '{name} is replaced with the destination name.'],
                'enquiry_university_title' => ['label' => 'University enquiry form title', 'value' => 'Interested in Studying Here?'],
                'enquiry_course_title' => ['label' => 'Course enquiry form title', 'value' => 'Interested in This Course?'],
                'enquiry_service_title' => ['label' => 'Service enquiry form title', 'value' => 'Get Help With {name}', 'help' => '{name} is replaced with the service name.'],
                'enquiry_subtitle' => ['label' => 'Enquiry form subtitle', 'value' => 'Speak with our expert counsellors and get personalized advice.'],
                'enquiry_button' => ['label' => 'Enquiry form button', 'value' => 'Book Free Consultation'],
                'story_form_title' => ['label' => 'Share-your-story form title', 'value' => 'Share Your Success Story'],
                'story_form_subtitle' => ['label' => 'Share-your-story form subtitle', 'value' => "Have you studied abroad with our support? We'd love to feature your story!"],
                'story_form_button' => ['label' => 'Share-your-story button', 'value' => 'Submit Your Story'],
                'apply_button' => ['label' => '"Apply" button text', 'value' => 'Apply Now'],
            ],
            'footer' => [
                'footer_description' => ['label' => 'Footer description', 'type' => 'textarea', 'value' => 'Helping students from Papua New Guinea study in India, Australia, Dubai and beyond, with admissions, study visas, documents and travel in one place.'],
                'footer_copyright' => ['label' => 'Copyright text', 'value' => '© {year} PNG International Education Portal. All rights reserved.', 'help' => '{year} is replaced with the current year.'],
            ],
        ];
    }
}
