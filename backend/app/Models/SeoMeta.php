<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = ['title', 'description', 'keywords', 'canonical_url', 'og_title', 'og_description', 'og_image', 'robots', 'schema'];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
