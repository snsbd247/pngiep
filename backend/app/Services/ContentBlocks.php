<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\GalleryVideo;
use App\Models\HeroSlide;
use App\Models\HomepageStatistic;
use App\Models\Partner;
use App\Models\Service;
use App\Models\SuccessStory;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\TimelineItem;
use App\Models\University;

/**
 * Read-only queries for public content blocks, shared by the homepage and CMS pages.
 * Each method returns plain arrays with only the columns the website needs.
 */
class ContentBlocks
{
    public const UNIVERSITY_CARD = ['id', 'destination_id', 'name', 'slug', 'logo', 'thumbnail', 'cover_image', 'type', 'city',
        'world_ranking', 'student_count', 'international_student_count', 'tuition_fee_min', 'tuition_fee_max', 'currency_code', 'short_description'];

    public const COURSE_CARD = ['courses.id', 'courses.university_id', 'courses.destination_id', 'courses.name', 'courses.slug', 'courses.subject',
        'courses.degree_type', 'courses.study_level', 'courses.duration', 'courses.tuition_fee', 'courses.currency_code', 'courses.fee_period',
        'courses.intakes', 'courses.image', 'courses.ielts_requirement'];

    public function heroSlides(): array
    {
        return HeroSlide::query()->active()->ordered()->get()->makeHidden(['created_at', 'updated_at', 'sort_order', 'is_active'])->toArray();
    }

    public function statistics(string $group = 'home'): array
    {
        return HomepageStatistic::query()->active()->ordered()->where('group', $group)
            ->get(['id', 'label', 'value', 'suffix', 'icon', 'description'])->toArray();
    }

    public function destinations(int $limit = 5, bool $featuredOnly = true): array
    {
        return Destination::query()->published()->when($featuredOnly, fn ($q) => $q->featured())->ordered()->limit($limit)
            ->get(['id', 'name', 'slug', 'country_code', 'thumbnail', 'hero_image', 'tagline', 'short_description'])->toArray();
    }

    public function services(int $limit = 8, bool $featuredOnly = true): array
    {
        return Service::query()->published()->when($featuredOnly, fn ($q) => $q->featured())->ordered()->limit($limit)
            ->get(['id', 'title', 'slug', 'icon', 'image', 'short_description'])->toArray();
    }

    public function universities(int $limit = 12): array
    {
        return University::query()->published()->featured()->ordered()->limit($limit)
            ->with('destination:id,name,slug,country_code')
            ->get(['id', 'destination_id', 'name', 'slug', 'logo', 'thumbnail', 'city', 'world_ranking'])->toArray();
    }

    /** University cards (list pages, carousels). $scope narrows the query. */
    public function universityCards(callable $scope, int $limit = 8): array
    {
        return University::query()->published()->tap($scope)->ordered()->limit($limit)
            ->with('destination:id,name,slug,country_code')
            ->withCount(['courses' => fn ($q) => $q->published()])
            ->get(self::UNIVERSITY_CARD)->toArray();
    }

    /** Course cards with their university and destination. */
    public function courseCards(callable $scope, int $limit = 8): array
    {
        return Course::query()->published()->tap($scope)->ordered()->limit($limit)
            ->with(['university:id,name,slug,logo', 'destination:id,name,slug,country_code'])
            ->get(self::COURSE_CARD)->toArray();
    }

    public function successStories(int $limit = 6): array
    {
        return SuccessStory::query()->published()->featured()->whereNotNull('quote')->ordered()->limit($limit)
            ->with(['destination:id,name,country_code', 'university:id,name,slug', 'course:id,name,slug'])
            ->get(['id', 'student_name', 'slug', 'photo', 'destination_id', 'university_id', 'course_id', 'university_name', 'course_name', 'quote'])
            ->toArray();
    }

    /** Success stories with a YouTube video, shaped like gallery videos. */
    public function storyVideos(int $limit = 3): array
    {
        return SuccessStory::query()->published()->whereNotNull('youtube_id')->ordered()->limit($limit)
            ->with('university:id,name')
            ->get(['id', 'student_name', 'slug', 'university_id', 'university_name', 'youtube_id', 'cover_image', 'photo'])
            ->map(fn (SuccessStory $s) => [
                'id' => $s->id, 'title' => $s->student_name, 'youtube_id' => $s->youtube_id, 'thumbnail' => $s->cover_image,
                'subtitle' => $s->university?->name ?? $s->university_name, 'url' => "/success-stories/{$s->slug}",
            ])->all();
    }

    public function testimonials(int $limit = 6): array
    {
        return Testimonial::query()->published()->ordered()->orderByDesc('is_featured')->limit($limit)
            ->get(['id', 'name', 'photo', 'country', 'country_code', 'university', 'course', 'content', 'rating'])->toArray();
    }

    public function photos(int $limit = 4): array
    {
        return GalleryImage::query()->published()->orderByDesc('is_featured')->ordered()->limit($limit)
            ->with('category:id,name,slug')
            ->get(['id', 'gallery_category_id', 'title', 'image'])->toArray();
    }

    public function videos(int $limit = 2): array
    {
        return GalleryVideo::query()->published()->orderByDesc('is_featured')->ordered()->limit($limit)
            ->with('category:id,name,slug')
            ->get(['id', 'gallery_category_id', 'title', 'youtube_id', 'thumbnail'])->toArray();
    }

    public function latestPosts(int $limit = 3): array
    {
        return Blog::query()->published()->latest('published_at')->limit($limit)
            ->with('category:id,name,slug')
            ->get(['id', 'blog_category_id', 'type', 'title', 'slug', 'featured_image', 'excerpt', 'published_at'])->toArray();
    }

    public function partners(?string $category = null, int $limit = 30): array
    {
        return Partner::query()->published()->ordered()->when($category, fn ($q) => $q->where('category', $category))->limit($limit)
            ->get(['id', 'name', 'logo', 'website'])->toArray();
    }

    public function team(?int $limit = null): array
    {
        return TeamMember::query()->active()->ordered()->when($limit, fn ($q) => $q->limit($limit))
            ->get(['id', 'name', 'designation', 'photo', 'bio', 'social_links'])->toArray();
    }

    public function timeline(): array
    {
        return TimelineItem::query()->active()->ordered()->get(['id', 'year', 'title', 'icon', 'description', 'image'])->toArray();
    }

    public function faqs(string $pageKey, int $limit = 20): array
    {
        return Faq::query()->published()->ordered()->where('page_key', $pageKey)->limit($limit)
            ->get(['id', 'question', 'answer', 'category'])->toArray();
    }
}
