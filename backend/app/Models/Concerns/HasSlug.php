<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Generates a unique slug from $slugSource (default "name") when none is given.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->slugSource ?? 'name';
            if (blank($model->slug) && filled($model->{$source})) {
                $model->slug = $model->generateUniqueSlug($model->{$source});
            } elseif ($model->isDirty('slug')) {
                $model->slug = $model->generateUniqueSlug($model->slug);
            }
        });
    }

    public function generateUniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        $query = fn (string $candidate) => static::query()
            ->when(in_array(SoftDeletes::class, class_uses_recursive(static::class)), fn ($q) => $q->withTrashed())
            ->where('slug', $candidate)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->exists();

        while ($query($slug)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
