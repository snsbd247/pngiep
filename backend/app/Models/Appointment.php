<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasNotes, LogsActivity, SoftDeletes;

    protected $guarded = ['id', 'slot_lock'];

    protected $hidden = ['manage_token', 'slot_lock'];

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            $appointment->reference ??= static::generateReference();
            $appointment->manage_token ??= Str::random(64);
        });

        // slot_lock backs the DB unique index that prevents double booking.
        static::saving(function (Appointment $appointment) {
            $status = $appointment->status ?? AppointmentStatus::Pending;
            $appointment->slot_lock = $status->occupiesSlot() ? 1 : null;
        });

        static::deleting(function (Appointment $appointment) {
            if (! $appointment->isForceDeleting()) {
                $appointment->slot_lock = null;
                $appointment->saveQuietly();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'duration_minutes' => 'integer',
            'status' => AppointmentStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'APT-'.strtoupper(Str::random(8));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function scopeOccupyingSlot(Builder $query): Builder
    {
        return $query->whereNotNull('slot_lock');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(Counsellor::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from_id');
    }
}
