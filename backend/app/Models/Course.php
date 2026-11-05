<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use FlushesPublicCache, HasPublishing, HasSeo, HasSlug, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // Keep the denormalised destination in sync with the owning university.
        static::saving(function (Course $course) {
            if ($course->isDirty('university_id') || ! $course->destination_id) {
                $course->destination_id = University::query()->whereKey($course->university_id)->value('destination_id');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'intakes' => 'array',
            'highlights' => 'array',
            'tuition_fee' => 'decimal:2',
            'ielts_requirement' => 'decimal:1',
            'duration_months' => 'integer',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function successStories(): HasMany
    {
        return $this->hasMany(SuccessStory::class);
    }
}
