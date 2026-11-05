<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSections;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class University extends Model
{
    use FlushesPublicCache, HasPublishing, HasSections, HasSeo, HasSlug, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected string $sectionModel = UniversitySection::class;

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'intakes' => 'array',
            'gallery' => 'array',
            'highlights' => 'array',
            'tuition_fee_min' => 'decimal:2',
            'tuition_fee_max' => 'decimal:2',
            'application_fee' => 'decimal:2',
            'established_year' => 'integer',
            'world_ranking' => 'integer',
            'national_ranking' => 'integer',
            'student_count' => 'integer',
            'international_student_count' => 'integer',
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function successStories(): HasMany
    {
        return $this->hasMany(SuccessStory::class);
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable');
    }
}
