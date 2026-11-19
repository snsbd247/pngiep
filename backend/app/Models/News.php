<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A news article shown at /news and /news/{slug}. */
class News extends Model
{
    use FlushesPublicCache, HasSeo, HasSlug, LogsActivity, SoftDeletes;

    protected $table = 'news';

    protected $guarded = ['id'];

    protected string $slugSource = 'title';

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published and not scheduled in the future. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
