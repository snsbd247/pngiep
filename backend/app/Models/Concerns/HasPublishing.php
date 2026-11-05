<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Common query scopes for content that has status / featured / sort_order columns.
 */
trait HasPublishing
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), ContentStatus::Published->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_featured'), true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy($this->qualifyColumn('sort_order'))->orderByDesc($this->qualifyColumn('id'));
    }
}
