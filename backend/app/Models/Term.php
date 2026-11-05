<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * An entry in an admin-managed list (see TAXONOMIES). `stores` says how records keep the
 * choice: by term id (foreign key) or by the term's name (existing text columns).
 */
class Term extends Model
{
    use LogsActivity;

    public const TAXONOMIES = [
        'page_category' => ['label' => 'Page Categories', 'permission' => 'pages.manage', 'stores' => 'id'],
        'destination_category' => ['label' => 'Destination Categories', 'permission' => 'destinations.manage', 'stores' => 'id'],
        'story_category' => ['label' => 'Story Categories', 'permission' => 'success_stories.manage', 'stores' => 'id'],
        'partner_category' => ['label' => 'Partner Categories', 'permission' => 'partners.manage', 'stores' => 'name'],
        'faq_category' => ['label' => 'FAQ Categories', 'permission' => 'faqs.manage', 'stores' => 'name'],
        'degree_type' => ['label' => 'Degree Types', 'permission' => 'courses.manage', 'stores' => 'name'],
        'intake' => ['label' => 'Intakes', 'permission' => 'courses.manage', 'stores' => 'name'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        // Slug unique within its taxonomy.
        static::saving(function (Term $term) {
            $base = Str::slug($term->slug ?: $term->name) ?: 'item';
            $slug = $base;
            for ($i = 2; static::query()->where('taxonomy', $term->taxonomy)->where('slug', $slug)->whereKeyNot($term->id ?? 0)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $term->slug = $slug;
        });
    }

    public function scopeOf(Builder $query, string $taxonomy): Builder
    {
        return $query->where('taxonomy', $taxonomy);
    }

    public function activityModule(): string
    {
        return Str::plural($this->taxonomy ?? 'terms');
    }
}
