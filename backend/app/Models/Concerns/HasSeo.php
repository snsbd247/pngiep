<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public static function bootHasSeo(): void
    {
        static::forceDeleted(fn ($model) => $model->seo()->delete());
        static::deleted(function ($model) {
            if (! method_exists($model, 'isForceDeleting')) {
                $model->seo()->delete();
            }
        });
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /** Create or update the SEO record from validated input (null values clear fields). */
    public function syncSeo(?array $data): void
    {
        if ($data === null) {
            return;
        }

        $this->seo()->updateOrCreate([], array_intersect_key($data, array_flip((new SeoMeta)->getFillable())));
    }
}
