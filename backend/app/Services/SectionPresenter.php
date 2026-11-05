<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\University;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Turns stored sections into public payloads, resolving "automatic" blocks
 * (statistics, team, FAQs, related universities / courses …) in the context
 * of the record that owns them.
 */
class SectionPresenter
{
    public function __construct(private readonly ContentBlocks $blocks) {}

    /** @param  Collection<int, Model>  $sections */
    public function presentAll(Collection $sections, ?Model $owner = null): array
    {
        return $sections->map(fn (Model $s) => $this->present($s, $owner))->values()->all();
    }

    public function present(Model $section, ?Model $owner = null): array
    {
        $data = $section->data ?? [];
        $limit = isset($data['limit']) ? (int) $data['limit'] : null;

        $items = match ($section->type) {
            'statistics' => ['statistics' => $this->blocks->statistics($data['group'] ?? 'home')],
            'timeline' => ['timeline' => $this->blocks->timeline()],
            'team' => ['team' => $this->blocks->team($limit)],
            'partners' => ['partners' => $this->blocks->partners($data['category'] ?? null)],
            'testimonials' => ['testimonials' => $this->blocks->testimonials($limit ?? 6)],
            'faq' => ['faqs' => $this->blocks->faqs($data['page_key'] ?? 'general')],
            'related_universities' => ['universities' => $this->relatedUniversities($owner, $limit ?? 8)],
            'related_courses' => ['courses' => $this->relatedCourses($owner, $limit ?? 8)],
            'story_videos' => ['videos' => $this->blocks->storyVideos($limit ?? 3)],
            default => [],
        };

        return $section->only(['id', 'type', 'eyebrow', 'title', 'subtitle', 'data']) + $items;
    }

    private function relatedUniversities(?Model $owner, int $limit): array
    {
        return match (true) {
            $owner instanceof Destination => $this->blocks->universityCards(fn ($q) => $q->where('destination_id', $owner->id), $limit),
            $owner instanceof University => $this->blocks->universityCards(fn ($q) => $q->where('destination_id', $owner->destination_id)->whereKeyNot($owner->id), $limit),
            default => $this->blocks->universityCards(fn ($q) => $q->featured(), $limit),
        };
    }

    private function relatedCourses(?Model $owner, int $limit): array
    {
        return match (true) {
            $owner instanceof Destination => $this->blocks->courseCards(fn ($q) => $q->where('courses.destination_id', $owner->id), $limit),
            $owner instanceof University => $this->blocks->courseCards(fn ($q) => $q->where('university_id', $owner->id), $limit),
            default => $this->blocks->courseCards(fn ($q) => $q->featured(), $limit),
        };
    }
}
