<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Course;
use App\Models\Destination;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\GalleryVideo;
use App\Models\SuccessStory;
use App\Models\Testimonial;
use App\Models\University;
use App\Support\DemoContent;
use Database\Seeders\Concerns\StockImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMO content (courses with sample fees, success stories, testimonials, gallery, blog) on
 * top of the real partner universities, so the website looks complete until real content
 * is added. The people, quotes and fees are illustrative.
 *
 * Every record created here is listed in storage/app/demo-content.json and can be removed
 * in one step with `php artisan app:demo-content remove`.
 */
class DemoDirectorySeeder extends Seeder
{
    use StockImages;

    private const PUBLISHED = ContentStatus::Published->value;

    /** @var array<string, list<int>> model key => ids created by this seeder */
    private array $created = [];

    public function run(): void
    {
        if (Course::query()->exists() || is_file(DemoContent::manifestPath())) {
            return;
        }
        if (! University::query()->exists()) {
            $this->call(PartnerDirectorySeeder::class);
        }

        $destinations = Destination::query()->get()->keyBy('country_code');
        $universities = University::query()->get()->keyBy('slug');

        $this->courses($universities->all());
        $this->stories($destinations->all(), $universities->all());
        $this->testimonials();
        $this->gallery();
        $this->blog();

        DemoContent::saveManifest($this->created);
    }

    private function track(string $key, $model)
    {
        $this->created[$key][] = $model->id;

        return $model;
    }

    /** Sample yearly tuition by currency and study level. */
    private function fee(?string $currency, string $level): ?int
    {
        $base = ['INR' => 180000, 'AED' => 45000, 'AUD' => 24000][$currency] ?? null;
        $factor = ['diploma' => 0.6, 'undergraduate' => 1.0, 'postgraduate' => 1.3, 'doctorate' => 1.1][$level] ?? 1.0;

        return $base ? (int) round($base * $factor, -3) : null;
    }

    private function courses(array $universities): void
    {
        $catalogue = [
            // name, subject, degree, level, months, photo
            ['B.Tech Computer Science & Engineering', 'Computer Science', 'B.Tech', 'undergraduate', 48, '1499750310107-5fef28a66643'],
            ['Bachelor of Business Administration (BBA)', 'Business & Management', 'BBA', 'undergraduate', 36, '1552664730-d307ca884978'],
            ['Master of Business Administration (MBA)', 'Business & Management', 'MBA', 'postgraduate', 24, '1556761175-5973dc0f32e7'],
            ['B.Sc Nursing', 'Health & Life Sciences', 'B.Sc', 'undergraduate', 48, '1531545514256-b1400bc00f31'],
            ['Bachelor of Computer Applications (BCA)', 'Computer Science', 'BCA', 'undergraduate', 36, '1454165804606-c3d57bc86b40'],
            ['Diploma in Civil Engineering', 'Engineering', 'Diploma', 'diploma', 36, '1524178232363-1fb2b075b655'],
            ['M.Tech Data Science', 'Computer Science', 'M.Tech', 'postgraduate', 24, '1513258496099-48168024aec0'],
            ['PhD in Management', 'Business & Management', 'PhD', 'doctorate', 36, '1497633762265-9d179a990aa6'],
        ];

        $index = 0;
        foreach (array_values($universities) as $u => $university) {
            $offered = collect($catalogue)->filter(fn ($c, $k) => ($k + $u) % 3 !== 0)->take(5);
            foreach ($offered as [$name, $subject, $degree, $level, $months, $photo]) {
                $this->track('courses', Course::query()->create([
                    'university_id' => $university->id,
                    'name' => $name,
                    'slug' => Str::slug("{$name} {$university->name}"),
                    'subject' => $subject, 'degree_type' => $degree, 'study_level' => $level,
                    'duration' => ($months / 12).' Years', 'duration_months' => $months,
                    'currency_code' => $university->currency_code, 'intakes' => $university->intakes,
                    'tuition_fee' => $this->fee($university->currency_code, $level), 'fee_period' => 'year',
                    'short_description' => "Study {$name} at {$university->name}.",
                    'description' => "<p>The {$name} programme at {$university->name} combines academic theory with practical learning.</p><p>Ask us about scholarships, fees and the next intake.</p>",
                    'entry_requirements' => '<ul><li>Grade 12 certificate with good grades in core subjects</li><li>Character reference, police clearance and medical report</li></ul>',
                    'highlights' => ['English-taught programme', 'Scholarships for eligible students', 'Admission and visa support from Port Moresby'],
                    'image' => $this->photo($photo, 800),
                    'is_featured' => $index % 3 === 0, 'status' => self::PUBLISHED, 'sort_order' => $index++,
                ]));
            }
            // Sample fee range on the university card (cleared again when demo content is removed).
            $fees = $university->courses()->whereNotNull('tuition_fee')->pluck('tuition_fee');
            if ($fees->isNotEmpty() && $university->tuition_fee_min === null) {
                $university->update(['tuition_fee_min' => $fees->min(), 'tuition_fee_max' => $fees->max()]);
                $this->created['university_fees'][] = $university->id;
            }
        }
    }

    private function stories(array $destinations, array $universities): void
    {
        $campus = [$this->photo('1607237138185-eedd9c632b0b', 900), $this->photo('1522202176988-66273c2fd55f', 900), $this->photo('1541339907198-e08756dedf3f', 900)];
        // Placeholder talks (public YouTube videos) until real student videos are added.
        $videos = ['UF8uR6Z6KLc', 'H14bBuluwB8', 'arj7oStGLkU'];

        foreach ([
            ['Grace Kila', '1534528741775-53994a69daeb', 'in', 'parul-university', 'B.Sc Nursing', 'undergraduate', 'July 2024', 'The team helped me with my NID, passport and visa, and now I am studying nursing in India. Thank you PNGIEP!'],
            ['Peter Kaupa', '1507003211169-0a1dd7228f2d', 'in', 'lovely-professional-university', 'B.Tech Computer Science & Engineering', 'undergraduate', 'July 2024', 'From the first meeting in Port Moresby to my first day on campus, everything was organised for me.'],
            ['Mary Tau', '1544005313-94ddf0286df2', 'in', 'guru-kashi-university', 'Bachelor of Business Administration (BBA)', 'undergraduate', 'July 2023', 'I received a scholarship and full support with my application. I recommend PNGIEP to every Grade 12 student.'],
            ['James Wari', '1506794778202-cad84cf45f1d', 'ae', 'britts-imperial-university', 'Master of Business Administration (MBA)', 'postgraduate', 'September 2024', 'Studying in Dubai has opened many doors for my career. The team made the process simple.'],
            ['Ruth Bage', '1438761681033-6461ffad8d80', 'au', 'south-sydney-college', 'Bachelor of Business Administration (BBA)', 'undergraduate', 'February 2024', 'They guided me through every document for my Australian student visa.'],
            ['Daniel Mone', '1500648767791-00dcc994a43e', 'in', 'aditya-educational-institutions', 'Diploma in Civil Engineering', 'diploma', 'July 2024', 'Fast, friendly and honest. They even booked my flight to India.'],
        ] as $i => [$name, $photo, $code, $uniSlug, $course, $level, $intake, $quote]) {
            $destination = $destinations[$code] ?? null;
            $uni = $universities[$uniSlug] ?? null;
            if (! $destination) {
                continue;
            }
            $this->track('success_stories', SuccessStory::query()->create([
                'student_name' => $name, 'photo' => $this->photo($photo, 500), 'cover_image' => $destination->hero_image,
                'destination_id' => $destination->id, 'university_id' => $uni?->id,
                'university_name' => $uni?->name, 'course_name' => $course,
                'study_level' => $level, 'intake' => $intake, 'year' => (int) substr($intake, -4), 'outcome' => 'Visa Approved',
                'quote' => $quote, 'excerpt' => $quote,
                'story' => "<p>{$quote}</p><p>Choosing the right university and course, preparing documents and getting a student visa felt overwhelming at first. The PNG International Education Portal team explained every step, helped with my NID and passport, and supported me until I arrived in {$destination->name}.</p>",
                'gallery' => [$this->photo($photo, 700), ...$campus],
                'youtube_id' => $videos[$i] ?? null,
                'video_url' => isset($videos[$i]) ? "https://www.youtube.com/watch?v={$videos[$i]}" : null,
                'is_featured' => true, 'status' => self::PUBLISHED, 'sort_order' => $i,
            ]));
        }
    }

    private function testimonials(): void
    {
        foreach ([
            ['Grace Kila', '1534528741775-53994a69daeb', 'India', 'in', 'Parul University', 'B.Sc Nursing', 'PNGIEP helped me with my NID, passport, visa and flight. Now I am studying nursing in India. Thank you!'],
            ['Peter Kaupa', '1507003211169-0a1dd7228f2d', 'India', 'in', 'Lovely Professional University', 'B.Tech Computer Science & Engineering', 'Everything was organised for me, from the first meeting in Port Moresby to my first day on campus.'],
            ['Ruth Bage', '1438761681033-6461ffad8d80', 'Australia', 'au', 'South Sydney College', 'Bachelor of Business Administration (BBA)', 'They guided me through every document for my student visa. Highly recommended!'],
        ] as $i => [$name, $photo, $country, $code, $university, $course, $quote]) {
            $this->track('testimonials', Testimonial::query()->create([
                'name' => $name, 'photo' => $this->photo($photo, 300), 'country' => $country, 'country_code' => $code,
                'university' => $university, 'course' => $course, 'content' => $quote, 'rating' => 5,
                'is_featured' => true, 'status' => self::PUBLISHED, 'sort_order' => $i,
            ]));
        }
    }

    private function gallery(): void
    {
        $categories = [];
        foreach ([
            ['Student Success', 'users'], ['Events', 'calendar-days'], ['University Visits', 'landmark'], ['Seminars', 'presentation'],
            ['Visa Success', 'stamp'], ['Office Activities', 'building-2'], ['Others', 'images'],
        ] as $i => [$name, $icon]) {
            $categories[$name] = GalleryCategory::query()->create(['name' => $name, 'type' => 'photo', 'icon' => $icon, 'sort_order' => $i]);
        }

        $pool = ['1540575467063-178a50c2df87', '1600880292203-757bb62b4baf', '1568792923760-d70635a89fdc', '1541339907198-e08756dedf3f',
            '1524178232363-1fb2b075b655', '1556761175-5973dc0f32e7', '1517486808906-6ca8b3f04846', '1531545514256-b1400bc00f31',
            '1522202176988-66273c2fd55f', '1552664730-d307ca884978', '1607237138185-eedd9c632b0b', '1571260899304-425eee4c7efc'];

        foreach ([
            ['Student Visa Success Celebration', 'Student Success', 0], ['Parul University Delegation Visit', 'University Visits', 1],
            ['Study Abroad Seminar – Port Moresby', 'Seminars', 0], ['LPU Campus Tour', 'University Visits', 2],
            ['Education Fair – Port Moresby', 'Events', 4], ['Visa Success Moments', 'Visa Success', 3],
            ['Student Counselling Sessions', 'Office Activities', 1], ['July Intake Departure', 'Student Success', 6],
            ['Our Office in Gordons', 'Office Activities', 5], ['Scholarship Award Ceremony', 'Student Success', 3],
        ] as $i => [$title, $category, $offset]) {
            $album = $this->track('gallery_albums', GalleryAlbum::query()->create([
                'gallery_category_id' => $categories[$category]->id, 'title' => $title, 'event_date' => now()->subDays(20 * $i)->toDateString(),
                'description' => "Highlights from {$title}.", 'is_featured' => $i < 4, 'status' => self::PUBLISHED, 'sort_order' => $i,
            ]));
            $album->forceFill(['created_at' => $album->event_date->copy()->setTime(15, 0)])->save();
            foreach (range(0, 4 + ($i % 4)) as $n) {
                $album->images()->create([
                    'gallery_category_id' => $categories[$category]->id, 'title' => $title,
                    'image' => $this->photo($pool[($offset + $n) % count($pool)], 1000),
                    'is_featured' => $n === 0 && $i < 4, 'status' => self::PUBLISHED, 'sort_order' => $n,
                ]);
            }
        }

        $videoCategories = [];
        foreach ([['Student Stories', 'graduation-cap'], ['Events & Seminars', 'presentation'], ['Study Tips', 'lightbulb']] as $i => [$name, $icon]) {
            $videoCategories[$name] = GalleryCategory::query()->create(['name' => $name, 'type' => 'video', 'icon' => $icon, 'sort_order' => $i]);
        }
        // Placeholder talks (public YouTube videos) to demonstrate the video gallery.
        foreach ([
            ['Student Visa Success Story', 'Student Stories', 'UF8uR6Z6KLc', '15:05'],
            ['Life as an International Student', 'Events & Seminars', 'H14bBuluwB8', '06:12'],
            ['How to Stay Motivated While Studying', 'Study Tips', 'arj7oStGLkU', '14:03'],
            ['Interview Confidence Tips', 'Study Tips', 'Ks-_Mh1QhMc', '21:03'],
        ] as $i => [$title, $category, $id, $duration]) {
            $this->track('gallery_videos', GalleryVideo::query()->create([
                'gallery_category_id' => $videoCategories[$category]->id, 'title' => $title, 'youtube_url' => "https://www.youtube.com/watch?v={$id}",
                'youtube_id' => $id, 'duration' => $duration, 'is_featured' => $i < 2, 'status' => self::PUBLISHED, 'sort_order' => $i,
            ]));
        }
    }

    private function blog(): void
    {
        $categories = [];
        foreach ([
            ['Study Abroad Tips', 'blog', 'graduation-cap'], ['University Updates', 'blog', 'landmark'], ['Visa Notice', 'notice', 'stamp'],
            ['Scholarships', 'blog', 'star'], ['Events', 'blog', 'calendar-days'], ['Career Guidance', 'blog', 'briefcase-business'],
            ['General Notice', 'notice', 'megaphone'],
        ] as $i => [$name, $type, $icon]) {
            $categories[$name] = BlogCategory::query()->create(['name' => $name, 'type' => $type, 'icon' => $icon, 'sort_order' => $i]);
        }

        $destinations = Destination::query()->pluck('id', 'slug');
        $posts = [
            ['How to Qualify for a Scholarship to Study in India', 'Scholarships', '1497633762265-9d179a990aa6', 'Grades, references, sponsorship and clearances: what you need to apply for a scholarship.', 3, 'india', ['Scholarships', 'India']],
            ['July Intake: Registration for Orientation Is Open', 'University Updates', '1524492412937-b28074a5d7da', 'Registration for the July intake orientation has begun. Book a consultation to secure your place.', 6, 'india', ['Intake', 'India']],
            ['Student Visa for India: Step-by-Step Guide', 'Visa Notice', '1454165804606-c3d57bc86b40', 'The documents and steps for your Indian student visa, from admission letter to approval.', 8, 'india', ['Visa', 'India']],
            ['Get Your NID and Passport Quickly', 'Study Abroad Tips', '1522202176988-66273c2fd55f', 'How our quick NID, birth certificate and passport assistance gets you ready to travel.', 10, null, ['NID', 'Passport']],
            ['Studying in Dubai: What to Expect', 'Study Abroad Tips', '1512453979798-5ea266f8880c', 'Life, study and opportunities in Dubai for students from Papua New Guinea.', 12, 'uae', ['Dubai', 'Tips']],
            ['Why Study in Australia?', 'Career Guidance', '1506973035872-a4ec16b8e8d9', 'Practical, career-focused education close to home.', 15, 'australia', ['Australia', 'Careers']],
            ['Education Fair in Port Moresby', 'Events', '1524178232363-1fb2b075b655', 'Meet our partner universities and get free counselling at our education fair.', 18, null, ['Events']],
            ['Office Closed for Independence Day', 'General Notice', '1556761175-5973dc0f32e7', 'Our office will be closed on 16 September for Independence Day.', 24, null, ['Notice']],
        ];

        foreach ($posts as $i => [$title, $category, $photo, $excerpt, $daysAgo, $destination, $tags]) {
            $post = $this->track('blogs', Blog::query()->create([
                'blog_category_id' => $categories[$category]->id, 'type' => $categories[$category]->type,
                'destination_id' => $destination ? $destinations[$destination] ?? null : null,
                'title' => $title, 'featured_image' => $this->photo($photo, 1200), 'excerpt' => $excerpt,
                'content' => "<p>{$excerpt}</p><h2>Key points</h2><ul><li>Start your preparation early and keep your documents organised.</li><li>Have your NID, birth certificate and passport ready.</li><li>Speak with our team for personal advice.</li></ul><h2>How we can help</h2><p>Visit our office in Gordons Commercial Area or book a free consultation to get started.</p>",
                'author_name' => 'PNG International Education Portal', 'status' => self::PUBLISHED, 'published_at' => now()->subDays($daysAgo),
                'is_featured' => $i < 3, 'views_count' => 500 - $i * 40,
            ]));
            $post->tags()->sync(collect($tags)->map(fn ($t) => BlogTag::query()->firstOrCreate(['slug' => Str::slug($t)], ['name' => $t])->id));
        }
    }
}
