<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryCategory extends Model
{
    use FlushesPublicCache, HasActiveFlag, HasSlug, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function albums(): HasMany
    {
        return $this->hasMany(GalleryAlbum::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(GalleryVideo::class);
    }
}
