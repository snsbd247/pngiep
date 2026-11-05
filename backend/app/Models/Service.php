<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSections;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use FlushesPublicCache, HasPublishing, HasSections, HasSeo, HasSlug, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected string $slugSource = 'title';

    protected string $sectionModel = ServiceSection::class;

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'array',
            'is_bookable' => 'boolean',
        ];
    }

    public function counsellors(): BelongsToMany
    {
        return $this->belongsToMany(Counsellor::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable');
    }
}
