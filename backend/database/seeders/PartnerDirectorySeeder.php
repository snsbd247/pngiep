<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Faq;
use App\Models\University;
use Database\Seeders\Concerns\StockImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The company's real study destinations and the universities that have issued PNGIEP a
 * "Certificate of Authorization" (source: PNGIEP Executive Summary). Runs in every
 * environment and never overwrites records that already exist.
 *
 * Only facts from the company are used: no rankings, fees or student numbers. Admins
 * complete those in Directory › Universities.
 */
class PartnerDirectorySeeder extends Seeder
{
    use StockImages;

    /** Scholarship entry prerequisites published by PNGIEP. */
    public const SCHOLARSHIP_REQUIREMENTS = [
        'Age 18 years and above',
        'A/B grades in core subjects in Grade 12 and C grades in minor subjects',
        'Good character reference from a school administrator, institution or church',
        'Financially sound, with a sponsorship guarantee from parents, government or an organisation',
        'Qualified police clearance report',
        'Qualified medical report',
    ];

    public function run(): void
    {
        $destinations = $this->destinations();
        $this->universities($destinations);
    }

    /** @return array<string, Destination> keyed by country code */
    private function destinations(): array
    {
        $list = [
            // code, name, slug, short name, photo, tagline, capital, language, currency, intakes, overview
            ['in', 'India', 'india', 'India', '1524492412937-b28074a5d7da', 'Quality degrees with scholarships for Pacific students', 'New Delhi', 'English / Hindi', 'INR', ['July'],
                'India is home to large, modern private universities offering diploma, bachelor, master and PhD programmes taught in English. Many Indian universities offer scholarships for students from developing countries. PNG International Education Portal admitted 300 students to Indian universities in 2023–2024.'],
            ['ae', 'United Arab Emirates', 'uae', 'Dubai', '1512453979798-5ea266f8880c', 'Study in Dubai, a global business hub', 'Abu Dhabi', 'Arabic / English', 'AED', [],
                'Dubai is an international city at the crossroads of Asia, Europe and Africa, with English-taught programmes and strong links to global business.'],
            ['au', 'Australia', 'australia', 'Australia', '1506973035872-a4ec16b8e8d9', 'A close neighbour with world-class education', 'Canberra', 'English', 'AUD', [],
                "Australia is Papua New Guinea's closest neighbour and one of the world's most popular study destinations, known for practical, career-focused education."],
        ];

        $result = [];
        foreach ($list as $i => [$code, $name, $slug, $short, $photo, $tagline, $capital, $language, $currency, $intakes, $overview]) {
            $existing = Destination::query()->where('slug', $slug)->first();
            if ($existing) {
                $result[$code] = $existing;

                continue;
            }

            $d = Destination::query()->create([
                'name' => $name, 'slug' => $slug, 'country_code' => $code, 'tagline' => $tagline,
                'capital' => $capital, 'language' => $language, 'currency_code' => $currency, 'intakes' => $intakes ?: null,
                'short_description' => "{$tagline}. Partner universities, scholarships and full application support from PNG International Education Portal.",
                'overview' => "<p>{$overview}</p><p>We handle your admission, NID and passport, student visa and flights, so you can focus on your studies.</p>",
                'thumbnail' => $this->photo($photo, 800), 'hero_image' => $this->photo($photo, 1800),
                'is_featured' => true, 'status' => 'published', 'sort_order' => $i,
            ]);

            $sections = [
                ['related_universities', "Our Partner Universities *in {$short}*", 'Universities that have authorised PNGIEP to represent them in Papua New Guinea.', ['limit' => 8]],
                ['list', 'Scholarship *Entry Requirements*', 'To qualify for a scholarship you will need:', ['items' => array_map(fn ($t) => ['text' => $t], self::SCHOLARSHIP_REQUIREMENTS)]],
                ['faq', 'Frequently Asked *Questions*', "Common questions about studying in {$short}.", ['page_key' => $slug]],
                ['cta', "Ready to Study in {$short}?", 'Talk to our team in Port Moresby and start your application today.', [
                    'primary_text' => 'Book Appointment', 'primary_url' => '/book-appointment', 'secondary_text' => 'Apply Now', 'secondary_url' => "/apply?destination={$slug}",
                    'background_image' => $d->hero_image,
                ]],
            ];
            foreach ($sections as $j => [$type, $title, $subtitle, $data]) {
                $d->sections()->create(['type' => $type, 'key' => "{$type}-".($j + 1), 'title' => $title, 'subtitle' => $subtitle, 'data' => $data, 'sort_order' => $j, 'is_active' => true]);
            }

            foreach ([
                ['Do you offer scholarships?', 'Many partner universities offer scholarships based on academic merit, financial background and geographical location. We also work with the national and provincial governments and District Development Authorities on full scholarships.'],
                ['What documents do I need?', 'Your Grade 12 certificate and transcripts, NID and birth certificate, passport, character reference, police clearance and medical report. We can help you get your NID, birth certificate and passport quickly.'],
                ['How do I apply?', 'Use the Apply Now page or visit our office in Gordons Commercial Area, Port Moresby. We prepare and submit your application and guide you through the student visa.'],
            ] as $j => [$q, $a]) {
                Faq::query()->create(['page_key' => $slug, 'category' => 'Destinations', 'question' => $q, 'answer' => "<p>{$a}</p>", 'sort_order' => $j, 'status' => 'published']);
            }
            $d->syncSeo(['title' => "Study in {$short} from Papua New Guinea | PNG International Education Portal"]);
            $result[$code] = $d;
        }

        return $result;
    }

    private function universities(array $destinations): void
    {
        $buildings = ['1568792923760-d70635a89fdc', '1607237138185-eedd9c632b0b', '1562774053-701939374585', '1592280771190-3e2e4d571952'];
        $list = [
            // name, country, city, website
            ['Parul University', 'in', 'Vadodara, Gujarat', 'https://paruluniversity.ac.in'],
            ['Lovely Professional University', 'in', 'Phagwara, Punjab', 'https://www.lpu.in'],
            ['Guru Kashi University', 'in', 'Talwandi Sabo, Punjab', null],
            ['Aditya Educational Institutions', 'in', 'Andhra Pradesh', null],
            ['Britts Imperial University', 'ae', 'Dubai', null],
            ['South Sydney College', 'au', 'Sydney', null],
        ];

        foreach ($list as $i => [$name, $code, $city, $website]) {
            $slug = Str::slug($name);
            if (University::query()->where('slug', $slug)->exists()) {
                continue;
            }
            $destination = $destinations[$code];
            $photo = $buildings[$i % count($buildings)];

            $u = University::query()->create([
                'destination_id' => $destination->id, 'name' => $name, 'slug' => $slug, 'city' => $city, 'type' => 'private',
                'currency_code' => $destination->currency_code, 'intakes' => $destination->intakes, 'website' => $website,
                'short_description' => "{$name} has authorised PNG International Education Portal to represent it in Papua New Guinea.",
                'about' => "<p>{$name} ({$city}, {$destination->name}) has issued a Certificate of Authorization to PNG International Education Portal to recruit and admit students from Papua New Guinea.</p><p>Programmes range from diploma and undergraduate to master's and PhD. Scholarship criteria and entry requirements vary by programme. Contact us for the latest details.</p>",
                'highlights' => ['Authorised PNGIEP partner', 'Diploma to PhD programmes', 'Scholarships for eligible students', 'Full admission and visa support from Port Moresby'],
                'thumbnail' => $this->photo($photo, 800), 'cover_image' => $this->photo($photo, 1800),
                'is_featured' => true, 'status' => 'published', 'sort_order' => $i,
            ]);

            foreach ([
                ['list', 'Scholarship *Entry Requirements*', 'Each university sets its own scholarship criteria. These are the general prerequisites:', ['items' => array_map(fn ($t) => ['text' => $t], self::SCHOLARSHIP_REQUIREMENTS)]],
                ['related_courses', 'Popular *Courses*', "Programmes offered by {$name}.", ['limit' => 8]],
                ['faq', 'Frequently Asked *Questions*', null, ['page_key' => $destination->slug]],
                ['cta', "Apply to {$name}", 'We prepare your application, documents and student visa.', [
                    'primary_text' => 'Apply Now', 'primary_url' => "/apply?university={$slug}", 'secondary_text' => 'Book Appointment', 'secondary_url' => '/book-appointment',
                    'background_image' => $u->cover_image,
                ]],
            ] as $j => [$type, $title, $subtitle, $data]) {
                $u->sections()->create(['type' => $type, 'key' => "{$type}-".($j + 1), 'title' => $title, 'subtitle' => $subtitle, 'data' => $data, 'sort_order' => $j, 'is_active' => true]);
            }
        }
    }
}
