<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Application extends Model
{
    use HasNotes, LogsActivity, Prunable, SoftDeletes;

    /** Days a deleted application (and its passport / transcript files) is kept before it is erased. */
    public const PRUNE_AFTER_DAYS = 90;

    protected $guarded = ['id'];

    /**
     * `model:prune` (daily) permanently erases applications an admin deleted more than
     * PRUNE_AFTER_DAYS ago. Active applications are never touched.
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(self::PRUNE_AFTER_DAYS));
    }

    /** Runs before each application is erased: remove the students' private files (rows cascade in the DB). */
    protected function pruning(): void
    {
        $paths = $this->documents()->pluck('path')->all();
        if ($paths) {
            Storage::disk('documents')->delete($paths);
        }
    }

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}
