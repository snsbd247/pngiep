<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSections;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Destination extends Model
{
    use FlushesPublicCache, HasPublishing, HasSections, HasSeo, HasSlug, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected string $sectionModel = DestinationSection::class;

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'intakes' => 'array',
            'quick_facts' => 'array',
            'tuition_fee_min' => 'decimal:2',
            'tuition_fee_max' => 'decimal:2',
            'living_cost_min' => 'decimal:2',
            'living_cost_max' => 'decimal:2',
        ];
    }

    public function universities(): HasMany
    {
        return $this->hasMany(University::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function successStories(): HasMany
    {
        return $this->hasMany(SuccessStory::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Term::class, 'destination_category_id');
    }
}
