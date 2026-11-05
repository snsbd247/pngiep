<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Counsellor;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\HomepageSection;
use App\Models\HomepageStatistic;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\TimelineItem;
use Database\Seeders\Concerns\StockImages;
use Illuminate\Database\Seeder;

/**
 * Initial website content (homepage, about page, services, team …). Everything here is
 * editable in the admin panel. Each block only seeds when its table is empty, so
 * re-running never overwrites admin changes.
 */
class ContentSeeder extends Seeder
{
    use StockImages;

    public function run(): void
    {
        $this->homepageSections();
        $this->heroSlides();
        $this->statistics();
        $this->services();
        $this->partners();
        $this->team();
        $this->counsellors();
        $this->timeline();
        $this->scholarshipsPage();
        $this->faqs();
        $this->aboutPage();
        $this->listingPages();
    }

    /** Built-in list pages: hero + sections around the API-driven list (template "listing"). */
    private function listingPages(): void
    {
        $cta = fn (string $eyebrow, string $title, string $subtitle) => ['type' => 'cta', 'eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle, 'data' => [
            'primary_text' => 'Book Free Consultation', 'primary_url' => '/book-appointment', 'secondary_text' => 'Contact Us', 'secondary_url' => '/contact',
            'background_image' => $this->photo('1541339907198-e08756dedf3f', 1600),
        ]];

        $pages = [
            'book-appointment' => [
                'title' => 'Book Appointment',
                'subtitle' => 'Get expert guidance from our education counsellors.',
                'excerpt' => 'Book a free consultation at your convenient time.',
                'banner_image' => $this->photo('1517486808906-6ca8b3f04846', 1800),
                'sections' => [
                    ['type' => 'listing'],
                    ['type' => 'sidebar_card', 'title' => 'Why Book a Consultation?', 'data' => ['kind' => 'list', 'items' => [
                        ['icon' => 'shield-check', 'title' => 'Personalized Guidance', 'text' => 'Get tailored advice based on your goals and profile.'],
                        ['icon' => 'users', 'title' => 'Expert Counsellors', 'text' => 'Talk to experienced and certified education consultants.'],
                        ['icon' => 'badge-check', 'title' => 'Free of Cost', 'text' => 'No consultation fees. 100% free counselling.'],
                        ['icon' => 'calendar-check', 'title' => 'Flexible Options', 'text' => 'Choose online, phone, or in-person appointments.'],
                        ['icon' => 'heart-handshake', 'title' => 'End-to-End Support', 'text' => "From university selection to visa approval, we're with you at every step."],
                    ]]],
                    ['type' => 'sidebar_card', 'title' => 'Take the First Step Towards Your Global Future', 'subtitle' => 'Book a free consultation and get expert guidance for your study abroad journey.', 'data' => [
                        'kind' => 'link', 'button_text' => 'View Our Services', 'button_url' => '/services', 'image' => $this->photo('1627556704302-624286467c65', 700),
                    ]],
                    ['type' => 'faq', 'title' => 'Frequently Asked *Questions*', 'subtitle' => 'Quick answers to common questions about booking an appointment.', 'data' => ['page_key' => 'appointment']],
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'excerpt' => "We're here to help you take the next step towards your global education journey. Get in touch with our expert team today.",
                'banner_image' => $this->photo('1522202176988-66273c2fd55f', 1800),
                'sections' => [
                    ['type' => 'listing', 'eyebrow' => 'Send us a message', 'title' => 'Get in *Touch*', 'subtitle' => 'Fill out the form below and our team will get back to you shortly.'],
                    ['type' => 'sidebar_card', 'title' => 'Our Office', 'subtitle' => 'Visit us at our office or get in touch through the details below.', 'data' => [
                        'kind' => 'link', 'image' => $this->photo('1562774053-701939374585', 900),
                    ]],
                    ['type' => 'offices', 'title' => 'Find *Us*', 'subtitle' => 'Visit our office in Gordons Commercial Area, Port Moresby.', 'data' => ['items' => [
                        ['name' => 'Head Office – Port Moresby', 'address' => 'Section 52, Allotment 55, Kawai Dr, Gordons Commercial Area, Port Moresby, Papua New Guinea', 'image' => $this->photo('1562774053-701939374585', 700),
                            'directions_url' => 'https://www.google.com/maps/search/?api=1&query=Gordons+Commercial+Area+Port+Moresby'],
                    ]]],
                    ['type' => 'faq', 'title' => 'Frequently Asked *Questions*', 'subtitle' => 'Quick answers to common questions. Still need help? Feel free to contact us.', 'data' => ['page_key' => 'contact']],
                ],
            ],
            'apply' => [
                'title' => 'Apply Now',
                'excerpt' => 'Start your application in minutes. Upload your documents and our counsellors will take it from there.',
                'banner_image' => $this->photo('1571260899304-425eee4c7efc', 1800),
                'sections' => [
                    ['type' => 'listing'],
                    ['type' => 'sidebar_card', 'title' => 'What Happens Next?', 'data' => ['kind' => 'list', 'items' => [
                        ['icon' => 'file-check', 'title' => 'Document Review', 'text' => 'A counsellor checks your documents within 2 working days.'],
                        ['icon' => 'landmark', 'title' => 'University Shortlist', 'text' => 'We confirm the best-fit universities and courses with you.'],
                        ['icon' => 'send', 'title' => 'Application Submitted', 'text' => 'We prepare and submit your applications.'],
                        ['icon' => 'stamp', 'title' => 'Offer & Visa', 'text' => 'We guide you from offer letter to visa approval.'],
                    ]]],
                ],
            ],
            'success-stories' => [
                'title' => 'Success Stories',
                'subtitle' => 'Real people. Real stories. Real success.',
                'excerpt' => 'Discover how our students achieved their global dreams with PNG International Education Portal.',
                'banner_image' => $this->photo('1541339907198-e08756dedf3f', 1800),
                'sections' => [
                    ['type' => 'statistics', 'data' => ['group' => 'success_stories', 'style' => 'overlap']],
                    ['type' => 'listing'],
                    ['type' => 'statistics', 'title' => 'Success by Numbers', 'data' => ['group' => 'success_stories', 'style' => 'sidebar']],
                    ['type' => 'story_videos', 'eyebrow' => 'Video testimonials', 'title' => 'Hear From *Our Students*', 'data' => ['limit' => 3, 'link_text' => 'View All Videos', 'link_url' => '/gallery/videos']],
                    $cta("Let's start your journey", 'Be the Next Success Story', 'Get expert guidance from our counsellors and take the first step toward your global future.'),
                ],
            ],
            'gallery' => [
                'title' => 'Gallery',
                'excerpt' => 'A glimpse into our journey — explore our events, student success moments, university visits, seminars and more.',
                'banner_image' => $this->photo('1517486808906-6ca8b3f04846', 1800),
                'sections' => [
                    ['type' => 'listing', 'title' => 'Our *Photo Gallery*'],
                    ['type' => 'sidebar_card', 'title' => 'Be a Part of Our Next Event', 'subtitle' => 'Join our upcoming seminars, university visits and special events.', 'data' => [
                        'kind' => 'link', 'icon' => 'calendar-days', 'button_text' => 'View Upcoming Events', 'button_url' => '/blog?category=events',
                    ]],
                    ['type' => 'sidebar_card', 'title' => 'Your Global Journey Starts Here', 'subtitle' => 'Meet top universities, attend exclusive events and be part of our growing success stories.', 'data' => [
                        'kind' => 'link', 'button_text' => 'Book a Free Consultation', 'button_url' => '/book-appointment', 'image' => $this->photo('1605130284535-11dd9eedc58a', 700),
                    ]],
                ],
            ],
            'video-gallery' => [
                'title' => 'Video Gallery',
                'excerpt' => 'Student stories, events, seminars and study tips — watch and get inspired.',
                'banner_image' => $this->photo('1540575467063-178a50c2df87', 1800),
                'sections' => [
                    ['type' => 'listing', 'title' => 'Watch & *Get Inspired*'],
                    $cta("Let's start your journey", 'Ready to Start Your Own Story?', 'Book a free consultation with our expert counsellors today.'),
                ],
            ],
            'blog' => [
                'title' => 'Blog & Notice',
                'excerpt' => 'Stay informed with the latest news, study abroad tips, university updates and important notices from PNG International Education Portal.',
                'banner_image' => $this->photo('1522202176988-66273c2fd55f', 1800),
                'sections' => [
                    ['type' => 'listing'],
                    ['type' => 'sidebar_card', 'title' => 'Never Miss an Update', 'subtitle' => 'Subscribe to get the latest blogs, notices and study abroad updates directly to your inbox.', 'data' => [
                        'kind' => 'newsletter', 'icon' => 'mail-check', 'button_text' => 'Subscribe',
                    ]],
                    ['type' => 'sidebar_card', 'title' => 'Need Expert Guidance?', 'subtitle' => 'Talk to our education counsellors and get personalised advice for your study abroad journey.', 'data' => [
                        'kind' => 'link', 'button_text' => 'Book a Free Consultation', 'button_url' => '/book-appointment', 'image' => $this->photo('1544005313-94ddf0286df2', 700),
                    ]],
                ],
            ],
            'services' => [
                'title' => 'Our Services',
                'excerpt' => 'Comprehensive support at every step of your study abroad journey. From university selection to visa assistance, we are with you until you achieve your global dreams.',
                'banner_image' => $this->photo('1522202176988-66273c2fd55f', 1800),
                'sections' => [
                    ['type' => 'feature_cards', 'data' => ['style' => 'bar', 'items' => [
                        ['icon' => 'headset', 'title' => 'End-to-End Support', 'text' => 'From application to arrival'],
                        ['icon' => 'users', 'title' => 'Expert Guidance', 'text' => 'Certified education counsellors'],
                        ['icon' => 'landmark', 'title' => 'Global University Network', 'text' => 'Partnered with 500+ institutions'],
                        ['icon' => 'chart-column-increasing', 'title' => 'High Success Rate', 'text' => 'Trusted by thousands of students'],
                    ]]],
                    ['type' => 'listing', 'eyebrow' => 'What we offer', 'title' => 'Our *Core Services*', 'subtitle' => 'Tailored solutions to make your study abroad journey simple, transparent and successful.'],
                    ['type' => 'feature_cards', 'eyebrow' => 'Our process', 'title' => 'How We *Work*', 'subtitle' => 'A simple and transparent process designed for your success.', 'data' => ['style' => 'steps', 'items' => [
                        ['icon' => 'message-circle-more', 'title' => 'Initial Consultation', 'text' => 'Understand your goals and profile'],
                        ['icon' => 'landmark', 'title' => 'University Shortlisting', 'text' => 'Find the right options for you'],
                        ['icon' => 'file-text', 'title' => 'Application Support', 'text' => 'Complete and review your application'],
                        ['icon' => 'stamp', 'title' => 'Visa Guidance', 'text' => 'Prepare and submit your visa application'],
                        ['icon' => 'plane', 'title' => 'Pre-Departure & Beyond', 'text' => 'Get ready and continue support after arrival'],
                    ]]],
                    $cta("Let's start your journey", 'Get Expert Guidance Today', 'Book a free consultation with our experienced counsellors and take the first step toward your global future.'),
                ],
            ],
            'universities' => [
                'title' => 'Universities',
                'excerpt' => 'Explore world-class universities across top study destinations. Find the right university for your academic goals.',
                'banner_image' => $this->photo('1568792923760-d70635a89fdc', 1800),
                'sections' => [
                    ['type' => 'listing'],
                    $cta("Let's find your perfect university", 'Need Help Choosing a University?', 'Get expert guidance from our education counsellors and find the right university for your future.'),
                ],
            ],
            'courses' => [
                'title' => 'Find a Course',
                'excerpt' => 'Search undergraduate, postgraduate and research programmes at leading universities worldwide.',
                'banner_image' => $this->photo('1571260899304-425eee4c7efc', 1800),
                'sections' => [
                    ['type' => 'listing'],
                    $cta("Let's start your journey", 'Not Sure Which Course Is Right for You?', 'Our counsellors match courses to your background, budget and career goals, free of charge.'),
                ],
            ],
            'destinations' => [
                'title' => 'Study Destinations',
                'excerpt' => 'Discover top countries for world-class education, post-study work opportunities and brighter careers.',
                'banner_image' => $this->photo('1513635269975-59663e0ac1ad', 1800),
                'sections' => [
                    ['type' => 'listing', 'eyebrow' => 'Explore the world', 'title' => 'Popular *Study Destinations*', 'subtitle' => 'Compare countries, costs and opportunities to find your perfect fit.'],
                    $cta("Let's start your journey", 'Ready to Study Abroad?', 'Book a free consultation with our expert team and take the first step toward your global future.'),
                ],
            ],
        ];

        foreach ($pages as $slug => $data) {
            if (Page::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $page = Page::query()->create([
                'title' => $data['title'], 'slug' => $slug, 'template' => 'listing', 'excerpt' => $data['excerpt'],
                'banner_image' => $data['banner_image'], 'status' => ContentStatus::Published->value, 'published_at' => now(),
            ]);

            foreach ($data['sections'] as $i => $section) {
                $page->sections()->create($section + ['key' => $section['type'].'-'.($i + 1), 'sort_order' => $i, 'is_active' => true, 'data' => $section['data'] ?? []]);
            }
        }
    }

    private function homepageSections(): void
    {
        $sections = [
            'hero' => ['name' => 'Hero slider'],
            'statistics' => ['name' => 'Statistics bar'],
            'destinations' => ['name' => 'Popular destinations', 'eyebrow' => 'Explore the world', 'title' => 'Popular *Study Destinations*',
                'subtitle' => 'Discover top countries for world-class education and brighter career opportunities.',
                'button_text' => 'View All Destinations', 'button_url' => '/destinations', 'items_limit' => 5],
            'services' => ['name' => 'Core services', 'eyebrow' => 'What we do', 'title' => 'Our *Core Services*',
                'subtitle' => 'Comprehensive support at every step of your study abroad journey.',
                'button_text' => 'View All Services', 'button_url' => '/services', 'items_limit' => 7],
            'universities' => ['name' => 'Featured universities', 'title' => 'Featured Universities',
                'subtitle' => 'Partnering with renowned institutions across the globe.',
                'button_text' => 'View All Universities', 'button_url' => '/universities', 'items_limit' => 12,
                'data' => ['background_image' => $this->photo('1568792923760-d70635a89fdc', 1600)]],
            'pathways' => ['name' => 'Study pathways', 'eyebrow' => 'Achieve your goals', 'title' => 'Study *Pathways*',
                'subtitle' => 'Choose the right pathway for your academic and career goals.',
                'data' => ['items' => [
                    ['icon' => 'layers', 'title' => 'Foundation', 'text' => 'Build a strong academic foundation', 'url' => '/courses?study_level=foundation'],
                    ['icon' => 'graduation-cap', 'title' => 'Undergraduate', 'text' => "Start your bachelor's degree abroad", 'url' => '/courses?study_level=undergraduate'],
                    ['icon' => 'award', 'title' => 'Final Year / Top-up', 'text' => 'Complete your degree in less time', 'url' => '/courses?study_level=diploma'],
                    ['icon' => 'book-open', 'title' => 'Postgraduate', 'text' => "Advance your career with a master's degree", 'url' => '/courses?study_level=postgraduate'],
                    ['icon' => 'flask-conical', 'title' => 'PhD', 'text' => 'Pursue world-class research opportunities', 'url' => '/courses?study_level=doctorate'],
                ]]],
            'why_choose' => ['name' => 'Why choose us', 'title' => 'Why Choose *Us*?',
                'data' => ['image' => $this->photo('1517486808906-6ca8b3f04846', 1200), 'items' => [
                    ['text' => 'Authorised representative of 6 universities'],
                    ['text' => '300 students admitted in 2023–2024'],
                    ['text' => 'Government MOUs for full scholarships'],
                    ['text' => 'One-stop shop: NID, passport, visa and flights'],
                    ['text' => 'Registered with the Investment Promotion Authority'],
                    ['text' => 'Local team in Port Moresby'],
                ]]],
            'success_stories' => ['name' => 'Success stories', 'eyebrow' => 'Real people. Real journeys', 'title' => 'Success *Stories*',
                'subtitle' => 'Hear from our students who have turned their dreams into reality.',
                'button_text' => 'View All Stories', 'button_url' => '/success-stories', 'items_limit' => 6],
            'gallery' => ['name' => 'Photo & video gallery', 'eyebrow' => 'Our moments', 'title' => 'Photo *Gallery*',
                'subtitle' => 'A glimpse of our events, university visits, and student success celebrations.',
                'button_text' => 'View Gallery', 'button_url' => '/gallery', 'items_limit' => 4,
                'data' => ['video_eyebrow' => 'Watch & get inspired', 'video_title' => 'Video *Gallery*', 'video_subtitle' => 'Student stories, events and more.',
                    'video_button_text' => 'View All Videos', 'video_button_url' => '/gallery/videos']],
            'latest_news' => ['name' => 'Latest news + appointment form', 'eyebrow' => 'Stay informed', 'title' => 'Latest *News & Updates*',
                'subtitle' => 'Read our latest articles on studying abroad, scholarships and more.',
                'button_text' => 'View All Articles', 'button_url' => '/blog', 'items_limit' => 3,
                'data' => ['form_title' => 'Book an Appointment', 'form_subtitle' => 'Speak with our expert counsellors and get personalized guidance.', 'form_button' => 'Book Appointment']],
            'testimonials' => ['name' => 'Testimonials', 'is_active' => false, 'eyebrow' => 'Testimonials', 'title' => 'What Our *Students Say*'],
            'partners' => ['name' => 'Partners', 'title' => 'Our *Partners*', 'button_text' => 'View All Partners', 'button_url' => '/about#partners'],
            'cta' => ['name' => 'Call to action banner', 'is_active' => false, 'eyebrow' => "Let's start your journey", 'title' => 'Ready to Study Abroad?',
                'subtitle' => 'Book a free consultation with our expert team and take the first step toward your global future.',
                'data' => ['primary_text' => 'Book Appointment', 'primary_url' => '/book-appointment', 'secondary_text' => 'Contact Us', 'secondary_url' => '/contact']],
            'newsletter' => ['name' => 'Newsletter signup', 'title' => 'Stay Updated',
                'subtitle' => 'Subscribe to our newsletter for the latest updates, scholarships and opportunities.',
                'data' => ['button_label' => 'Subscribe', 'placeholder' => 'Your email address']],
        ];

        $order = 0;
        foreach ($sections as $key => $attributes) {
            HomepageSection::query()->firstOrCreate(['key' => $key], $attributes + ['sort_order' => $order++, 'is_active' => true]);
        }
    }

    private function heroSlides(): void
    {
        if (HeroSlide::query()->exists()) {
            return;
        }

        HeroSlide::query()->create([
            'badge' => 'Study in India, Australia, Dubai & More',
            'title' => 'Your Global Education Journey',
            'highlight' => 'Starts Here',
            'subtitle' => 'A one-stop shop for Papua New Guinean students: admission, scholarships, NID, passport, student visa and flights, from Port Moresby to your campus abroad.',
            'image' => $this->photo('1627556704302-624286467c65', 1600),
            'primary_button_text' => 'Explore Destinations',
            'primary_button_url' => '/destinations',
            'secondary_button_text' => 'Book Free Consultation',
            'secondary_button_url' => '/book-appointment',
            'video_label' => 'Watch Our Story',
        ]);
    }

    private function statistics(): void
    {
        if (HomepageStatistic::query()->exists()) {
            return;
        }

        // Figures from the PNGIEP Executive Summary.
        foreach (['home', 'success_stories'] as $group) {
            foreach ([
                ['graduation-cap', '300', '+', 'Students Admitted (2023–2024)'],
                ['landmark', '6', '', 'Partner Universities'],
                ['earth', '3', '', 'Study Destinations'],
                ['target', '1,000', '', 'Admission Target 2025'],
            ] as $i => [$icon, $value, $suffix, $label]) {
                HomepageStatistic::query()->create(compact('group', 'icon', 'value', 'suffix', 'label') + ['sort_order' => $i]);
            }
        }
    }

    private function services(): void
    {
        if (Service::query()->exists()) {
            return;
        }

        $services = [
            ['Student Recruitment, Consultation & Admission', 'student-recruitment-admission', 'graduation-cap', '1499750310107-5fef28a66643', 'Free consultation and admission to our partner universities in India, Dubai and Australia, from diploma and undergraduate to master\'s and PhD programmes.'],
            ['Quick NID & Birth Certificate Assistance', 'nid-birth-certificate-assistance', 'file-text', '1531545514256-b1400bc00f31', 'Fast assistance obtaining your National ID and birth certificate for admission and visa applications.'],
            ['Quick Passport Assistance', 'passport-assistance', 'book-open', '1497633762265-9d179a990aa6', 'Fast help applying for or renewing your passport so you are ready to travel.'],
            ['Quick Student Visa Assistance', 'student-visa-assistance', 'stamp', '1454165804606-c3d57bc86b40', 'Step-by-step help preparing and lodging your student visa application.'],
            ['Global Airline Ticketing', 'airline-ticketing', 'plane', '1436491865332-7a61a109cc05', 'Airline tickets to anywhere in the world through our travel service, the PNG International Travel Portal (PNGITP).'],
        ];

        foreach ($services as $i => [$title, $slug, $icon, $photo, $summary]) {
            Service::query()->create([
                'title' => $title,
                'slug' => $slug,
                'icon' => $icon,
                'image' => $this->photo($photo, 900),
                'short_description' => $summary,
                'description' => "<p>{$summary}</p><p>Our counsellors work with you one-to-one, explain every option clearly and stay with you until you reach your goal.</p>",
                'features' => ['One-to-one expert guidance', 'Transparent, step-by-step process', 'Support from start to finish'],
                'cta_text' => 'Book Free Consultation',
                'cta_url' => '/book-appointment',
                'is_featured' => true,
                'status' => ContentStatus::Published->value,
                'sort_order' => $i,
            ]);
        }
    }

    private function partners(): void
    {
        if (Partner::query()->exists()) {
            return;
        }

        foreach ([
            ['Parul University', 'https://paruluniversity.ac.in'],
            ['Lovely Professional University', 'https://www.lpu.in'],
            ['Guru Kashi University', null],
            ['Aditya Educational Institutions', null],
            ['Britts Imperial University', null],
            ['South Sydney College', null],
        ] as $i => [$name, $website]) {
            Partner::query()->create(['name' => $name, 'website' => $website, 'category' => 'university', 'sort_order' => $i, 'status' => 'published']);
        }
    }

    private function team(): void
    {
        if (TeamMember::query()->exists()) {
            return;
        }

        foreach (['Dominic Yambi', 'John Yandi'] as $i => $name) {
            TeamMember::query()->create([
                'name' => $name, 'designation' => 'Director & Partner', 'sort_order' => $i,
                'bio' => 'Graduate of the Papua New Guinea University of Technology, with experience at reputable corporate companies in PNG.',
            ]);
        }
    }

    /** The directors take consultations; set real hours in Business › Counsellors. */
    private function counsellors(): void
    {
        if (Counsellor::query()->exists()) {
            return;
        }

        $services = Service::query()->pluck('id');
        foreach (['Dominic Yambi', 'John Yandi'] as $i => $name) {
            $counsellor = Counsellor::query()->create([
                'name' => $name, 'designation' => 'Director & Partner', 'languages' => ['English', 'Tok Pisin'],
                'bio' => 'Director of PNG International Education Portal and graduate of the Papua New Guinea University of Technology.',
                'status' => ContentStatus::Published->value, 'sort_order' => $i,
            ]);
            $counsellor->services()->sync($services);
            foreach ([1, 2, 3, 4, 5] as $day) { // Monday – Friday
                $counsellor->availabilities()->create(['day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '12:00']);
                $counsellor->availabilities()->create(['day_of_week' => $day, 'start_time' => '13:00', 'end_time' => '16:00']);
            }
        }
    }

    private function timeline(): void
    {
        if (TimelineItem::query()->exists()) {
            return;
        }

        foreach ([
            ['2023', 'Founded in Port Moresby and registered with the Investment Promotion Authority (IPA)', 'sprout'],
            ['2023', 'Authorised to represent universities in India, Dubai and Australia', 'handshake'],
            ['2024', '300 students admitted to study in India (2023–2024)', 'users'],
            ['2025', 'MOUs with the national government, provincial governments and DDAs for full scholarships', 'landmark'],
            ['2025', 'Target: 1,000 new admissions', 'chart-column-increasing'],
        ] as $i => [$year, $title, $icon]) {
            TimelineItem::query()->create(['year' => $year, 'title' => $title, 'icon' => $icon, 'sort_order' => $i]);
        }
    }

    private function faqs(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $groups = [
            'contact' => [
                ['How can I book a consultation?', 'Use the Book Appointment page to choose a service, counsellor, date and time. You will receive a confirmation by email.'],
                ['Do you provide online consultations?', 'Yes. You can meet our counsellors by video call, phone, or in person at our office.'],
                ['Where is your office?', 'Section 52, Allotment 55, Kawai Dr, Gordons Commercial Area, Port Moresby. Call or WhatsApp us to arrange a visit.'],
                ['Which countries do you support?', 'We place students in India, Australia, Dubai (UAE) and other countries.'],
                ['Who can get a scholarship?', 'Students aged 18 and above with A/B grades in core Grade 12 subjects, a good character reference, a sponsorship guarantee, police clearance and a medical report. See our Scholarships page for details.'],
            ],
            'appointment' => [
                ['Is the consultation really free?', 'Yes. Your first consultation with our counsellors is completely free, with no obligation.'],
                ['How will the consultation be conducted?', 'You can choose an online video call, a phone call or an in-office meeting when booking.'],
                ['Can I reschedule my appointment?', 'Yes. Use the link in your confirmation email or contact us at least 24 hours before your appointment.'],
                ['What should I prepare for the consultation?', 'Bring your academic transcripts, English test results (if any) and a short list of the countries or courses you are considering.'],
            ],
        ];

        foreach ($groups as $pageKey => $faqs) {
            foreach ($faqs as $i => [$question, $answer]) {
                Faq::query()->create(['page_key' => $pageKey, 'category' => 'General', 'question' => $question, 'answer' => "<p>{$answer}</p>", 'sort_order' => $i]);
            }
        }
    }

    private function scholarshipsPage(): void
    {
        if (Page::query()->where('slug', 'scholarships')->exists()) {
            return;
        }

        $page = Page::query()->create([
            'title' => 'Scholarships',
            'slug' => 'scholarships',
            'subtitle' => 'Study abroad with a scholarship',
            'excerpt' => 'Partner universities and government partners offer scholarships for students from Papua New Guinea. Check the entry requirements and apply with us.',
            'banner_image' => $this->photo('1571260899304-425eee4c7efc', 1800),
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
        ]);

        $sections = [
            ['type' => 'rich_text', 'eyebrow' => 'Foreign universities & scholarships', 'title' => 'Scholarships for *PNG Students*', 'data' => [
                'body' => '<p>Each partner university has its own scholarship criteria, based on factors such as academic merit, economically disadvantaged background and geographical location. Most scholarships offered by Indian universities focus on students from developing countries.</p><p>PNGIEP has also signed Memorandums of Understanding with the national government, provincial governments and District Development Authorities (DDA) to provide full scholarships for the duration of the programme. Private and individual sponsorship is also welcome.</p>',
            ]],
            ['type' => 'list', 'title' => 'Entry *Requirements*', 'subtitle' => 'To qualify for a scholarship you will need:', 'data' => [
                'items' => array_map(fn ($t) => ['text' => $t], PartnerDirectorySeeder::SCHOLARSHIP_REQUIREMENTS),
            ]],
            ['type' => 'partners', 'eyebrow' => 'Certificate of Authorization', 'title' => 'Universities We *Represent*', 'data' => []],
            ['type' => 'cta', 'eyebrow' => 'Registration is open', 'title' => 'Apply for a Scholarship', 'subtitle' => 'Talk to our team in Port Moresby about scholarships and the next intake.', 'data' => [
                'primary_text' => 'Apply Now', 'primary_url' => '/apply', 'secondary_text' => 'Book Appointment', 'secondary_url' => '/book-appointment',
                'background_image' => $this->photo('1541339907198-e08756dedf3f', 1600),
            ]],
        ];
        foreach ($sections as $i => $section) {
            $page->sections()->create($section + ['key' => $section['type'].'-'.($i + 1), 'sort_order' => $i, 'is_active' => true]);
        }
        $page->syncSeo(['title' => 'Scholarships to Study Abroad from Papua New Guinea | PNGIEP']);
    }

    private function aboutPage(): void
    {
        if (Page::query()->where('slug', 'about')->exists()) {
            return;
        }

        $page = Page::query()->create([
            'title' => 'About Us',
            'slug' => 'about',
            'template' => 'about',
            'subtitle' => 'Your Study Abroad Partner in Papua New Guinea',
            'excerpt' => 'Founded in 2023 in Port Moresby, PNG International Education Portal places Papua New Guinean students at accredited universities abroad, from diploma to PhD.',
            'banner_image' => $this->photo('1627556704302-624286467c65', 1800),
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
        ]);

        $sections = [
            ['type' => 'intro', 'eyebrow' => 'Who we are', 'title' => 'PNG International Education Portal – Your *Study Abroad Partner*', 'data' => [
                'body' => '<p>PNG International Education Portal (PNGIEP) was founded in 2023 in Port Moresby and is a fully registered business with the Investment Promotion Authority (IPA) of Papua New Guinea. We are an international recruitment and consulting agency that sources and places students at accredited universities around the world, in diploma, undergraduate, master\'s and PhD programmes.</p><p>We also provide NID and birth certificate, passport and student visa assistance, and airline ticketing through our travel service, the PNG International Travel Portal (PNGITP): a one-stop shop for every student who wants to study overseas.</p><p>PNGIEP admitted 300 students to Indian universities in 2023–2024. With Memorandums of Understanding signed with the national government, provincial governments and District Development Authorities to provide full scholarships, our target for 2025 is 1,000 admissions.</p>',
                'images' => [$this->photo('1556761175-5973dc0f32e7', 900), $this->photo('1531545514256-b1400bc00f31', 600), $this->photo('1544005313-94ddf0286df2', 600)],
                'button_text' => 'Our Services', 'button_url' => '/services',
                'video_label' => 'Watch Our Story', 'video_duration' => '2:30 minutes',
            ]],
            ['type' => 'statistics', 'data' => ['group' => 'home']],
            ['type' => 'feature_cards', 'data' => ['style' => 'large', 'items' => [
                ['icon' => 'target', 'title' => 'Our Mission', 'text' => 'To give every Papua New Guinean student a simple, affordable path to an accredited degree overseas, with admission, scholarships, documents and travel handled in one place.'],
                ['icon' => 'eye', 'title' => 'Our Vision', 'text' => 'Educated young Papua New Guineans returning home with the skills to develop their communities and the nation.'],
            ]]],
            ['type' => 'features_with_image', 'eyebrow' => 'Why choose us', 'title' => 'What Makes Us *Different*?', 'subtitle' => 'We go beyond applications — we build your global future.', 'data' => [
                'items' => [
                    ['icon' => 'plane', 'title' => 'Documents & Travel', 'text' => 'Passport, NID, birth certificate, visa and flights handled for you.'],
                    ['icon' => 'landmark', 'title' => 'Partner Universities', 'text' => 'Admission to partner universities in India, Dubai and Australia.'],
                    ['icon' => 'message-circle-more', 'title' => 'Personalized Counselling', 'text' => 'Tailored advice based on your goals and profile.'],
                    ['icon' => 'file-check', 'title' => 'End-to-End Support', 'text' => 'From application to visa and pre-departure guidance.'],
                    ['icon' => 'shield-check', 'title' => 'Transparent Process', 'text' => 'Honest and clear communication at every step.'],
                    ['icon' => 'chart-column-increasing', 'title' => 'Student Success Focus', 'text' => 'Your success is our greatest achievement.'],
                ],
                'image' => $this->photo('1571260899304-425eee4c7efc', 900),
                'image_caption' => "Your Dreams\nOur Support\nA Global Future",
            ]],
            ['type' => 'timeline', 'eyebrow' => 'Our journey', 'title' => 'Our *Journey*', 'data' => []],
            ['type' => 'team', 'eyebrow' => 'Our team', 'title' => 'Meet the *Directors*', 'subtitle' => 'PNGIEP is managed by two young Papua New Guineans, both graduates of the PNG University of Technology.', 'data' => ['limit' => 4]],
            ['type' => 'partners', 'eyebrow' => 'Certificate of Authorization', 'title' => 'Universities We *Represent*', 'data' => []],
            ['type' => 'cta', 'eyebrow' => "Let's start your journey", 'title' => 'Ready to Study Abroad?', 'subtitle' => 'Book a free consultation with our expert team and take the first step toward your global future.', 'data' => [
                'primary_text' => 'Book Appointment', 'primary_url' => '/book-appointment', 'secondary_text' => 'Contact Us', 'secondary_url' => '/contact',
                'background_image' => $this->photo('1541339907198-e08756dedf3f', 1600),
            ]],
        ];

        foreach ($sections as $i => $section) {
            $page->sections()->create($section + ['key' => $section['type'].'-'.($i + 1), 'sort_order' => $i, 'is_active' => true]);
        }

        $page->syncSeo([
            'title' => 'About PNG International Education Portal | Study Abroad from Port Moresby',
            'description' => 'PNG International Education Portal helps students from Papua New Guinea study in India, Australia and Dubai, with admissions, study visa, passport and travel support.',
        ]);
    }
}
