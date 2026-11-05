<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Counsellor;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\GalleryCategory;
use App\Models\HomepageStatistic;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Service;
use App\Models\Term;
use App\Models\University;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Option lists for admin dropdowns: GET /admin/lookups?types=pages,statistic_groups
 */
class LookupController extends Controller
{
    /** Built-in places where FAQ groups are shown. */
    private const FAQ_PAGE_KEYS = ['home', 'about', 'contact', 'appointment', 'services', 'destinations', 'universities', 'general'];

    public function __invoke(Request $request): JsonResponse
    {
        $types = array_filter(explode(',', (string) $request->query('types')));
        $options = fn ($values) => collect($values)->filter()->unique()->sort()->values()->map(fn ($v) => ['value' => $v, 'label' => $v]);

        $resolvers = [
            'pages' => fn () => Page::query()->orderBy('title')->get(['id', 'title', 'slug'])
                ->map(fn ($p) => ['value' => $p->id, 'label' => "{$p->title} (/{$p->slug})"]),
            'destinations' => fn () => Destination::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name]),
            'universities' => fn () => University::query()->with('destination:id,name')->orderBy('name')->get(['id', 'name', 'destination_id'])
                ->map(fn ($u) => ['value' => $u->id, 'label' => $u->name.($u->destination ? " ({$u->destination->name})" : '')]),
            'photo_categories' => fn () => GalleryCategory::query()->where('type', 'photo')->orderBy('sort_order')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name]),
            'video_categories' => fn () => GalleryCategory::query()->where('type', 'video')->orderBy('sort_order')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name]),
            'blog_categories' => fn () => BlogCategory::query()->orderBy('sort_order')->get(['id', 'name', 'type'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name.($c->type === 'notice' ? ' (notice)' : '')]),
            'courses' => fn () => Course::query()->with('university:id,name')->orderBy('name')->get(['id', 'name', 'university_id'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name.' – '.$c->university?->name]),
            'services' => fn () => Service::query()->orderBy('sort_order')->get(['id', 'title'])
                ->map(fn ($s) => ['value' => $s->id, 'label' => $s->title]),
            'counsellors' => fn () => Counsellor::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name]),
            // Login accounts that can be linked to a counsellor profile.
            'counsellor_users' => fn () => User::query()->whereHas('role', fn ($q) => $q->where('slug', 'counsellor'))->orderBy('name')->get(['id', 'name', 'email'])
                ->map(fn ($u) => ['value' => $u->id, 'label' => "{$u->name} ({$u->email})"]),
            'staff' => fn () => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['value' => $u->id, 'label' => $u->name]),
            // Admin-managed lists: id-based for pages / destinations / stories, name-based elsewhere.
            ...collect(Term::TAXONOMIES)->mapWithKeys(fn ($def, $taxonomy) => [
                Str::plural($taxonomy) => fn () => Term::query()->of($taxonomy)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'is_active'])
                    ->map(fn ($t) => ['value' => $def['stores'] === 'id' ? $t->id : $t->name, 'label' => $t->name.($t->is_active ? '' : ' (hidden)')]),
            ])->all(),
            'tags' => fn () => $options(BlogTag::query()->orderBy('name')->pluck('name')),
            'subjects' => fn () => $options(Course::query()->distinct()->pluck('subject')),
            'statistic_groups' => fn () => $options(HomepageStatistic::query()->distinct()->pluck('group')->push('home', 'about')),
            'faq_page_keys' => fn () => $options(Faq::query()->distinct()->pluck('page_key')->merge(self::FAQ_PAGE_KEYS)),
        ];

        return ApiResponse::success(collect($resolvers)->only($types)->map(fn ($resolve) => $resolve()));
    }
}
