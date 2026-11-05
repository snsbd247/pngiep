<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AppointmentStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /** Whether an appointment in this status still occupies its time slot. */
    public function occupiesSlot(): bool
    {
        return ! in_array($this, [self::Cancelled, self::Rescheduled], true);
    }
}
