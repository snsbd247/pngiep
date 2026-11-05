<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Counsellor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Generates bookable time slots from counsellor weekly availability, time off,
 * existing appointments and the booking rules in Settings > Appointment.
 *
 * Dates and times are "wall clock" values in the business timezone
 * (Settings > Appointment > Timezone), which is how they are stored.
 */
class AppointmentScheduler
{
    public function __construct(private readonly SettingService $settings) {}

    public function timezone(): string
    {
        $tz = (string) $this->settings->get('appointment_timezone', config('app.timezone'));

        return in_array($tz, timezone_identifiers_list(), true) ? $tz : config('app.timezone');
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function durationFor(Counsellor $counsellor): int
    {
        return (int) ($counsellor->slot_duration ?: $this->settings->get('appointment_duration', 30));
    }

    /** Latest bookable date (inclusive). */
    public function lastBookableDate(): CarbonImmutable
    {
        return $this->now()->startOfDay()->addDays(max(1, (int) $this->settings->get('appointment_advance_days', 30)));
    }

    /** Earliest bookable moment (now + minimum notice). */
    public function earliestStart(): CarbonImmutable
    {
        return $this->now()->addHours(max(0, (int) $this->settings->get('appointment_min_notice_hours', 0)));
    }

    public function isWorkingDay(CarbonImmutable $date): bool
    {
        $days = (array) $this->settings->get('appointment_working_days', [0, 1, 2, 3, 4, 5, 6]);

        return in_array($date->dayOfWeek, array_map('intval', $days), true);
    }

    /**
     * Free slots for one counsellor on one date.
     *
     * @return list<array{start: string, end: string}> times as H:i
     */
    public function slots(Counsellor $counsellor, string $date, ?int $ignoreAppointmentId = null): array
    {
        $tz = $this->timezone();
        $day = CarbonImmutable::parse($date, $tz)->startOfDay();

        if ($day->lt($this->now()->startOfDay()) || $day->gt($this->lastBookableDate()) || ! $this->isWorkingDay($day)) {
            return [];
        }

        $timeOffs = $counsellor->timeOffs->filter(fn ($t) => $t->date->toDateString() === $day->toDateString());
        if ($timeOffs->contains(fn ($t) => $t->start_time === null)) {
            return []; // whole day off
        }

        $windows = $counsellor->availabilities->where('is_active', true)->where('day_of_week', $day->dayOfWeek);
        $duration = $this->durationFor($counsellor);
        $earliest = $this->earliestStart();
        $busy = $this->busyRanges($counsellor->id, $day->toDateString(), $ignoreAppointmentId)->toBase()
            ->merge($timeOffs->map(fn ($t) => [$day->setTimeFromTimeString($t->start_time), $day->setTimeFromTimeString($t->end_time)]));

        $slots = [];
        foreach ($windows as $window) {
            $cursor = $day->setTimeFromTimeString($window->start_time);
            $windowEnd = $day->setTimeFromTimeString($window->end_time);

            while ($cursor->addMinutes($duration)->lte($windowEnd)) {
                $end = $cursor->addMinutes($duration);
                $overlaps = $busy->contains(fn ($range) => $cursor->lt($range[1]) && $end->gt($range[0]));

                if ($cursor->gte($earliest) && ! $overlaps) {
                    $slots[$cursor->format('H:i')] = ['start' => $cursor->format('H:i'), 'end' => $end->format('H:i')];
                }
                $cursor = $end;
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /** Days in a month with at least one free slot: ['2026-10-12' => 6, …]. */
    public function availableDays(Counsellor $counsellor, string $month): array
    {
        $start = CarbonImmutable::parse("{$month}-01", $this->timezone());
        $days = [];

        // One query for the whole month instead of one per day.
        $this->monthBookings = $this->occupying($counsellor->id)
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $start->endOfMonth()->toDateString())
            ->get(['appointment_date', 'start_time', 'end_time'])
            ->groupBy(fn ($a) => $a->appointment_date->toDateString());

        try {
            for ($day = $start; $day->month === $start->month; $day = $day->addDay()) {
                $count = count($this->slots($counsellor, $day->toDateString()));
                if ($count > 0) {
                    $days[$day->toDateString()] = $count;
                }
            }
        } finally {
            // Never reuse the snapshot for booking checks.
            $this->monthBookings = null;
        }

        return $days;
    }

    public function isSlotFree(Counsellor $counsellor, string $date, string $start, ?int $ignoreAppointmentId = null): bool
    {
        return collect($this->slots($counsellor, $date, $ignoreAppointmentId))->contains('start', substr($start, 0, 5));
    }

    /** Month snapshot used only while availableDays() runs. */
    private ?Collection $monthBookings = null;

    private function occupying(int $counsellorId)
    {
        return Appointment::query()
            ->where('counsellor_id', $counsellorId)
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::Rescheduled->value]);
    }

    /** @return Collection<int, array{0: CarbonImmutable, 1: CarbonImmutable}> */
    private function busyRanges(int $counsellorId, string $date, ?int $ignoreId): Collection
    {
        $tz = $this->timezone();
        $bookings = $this->monthBookings !== null && ! $ignoreId
            ? ($this->monthBookings->get($date) ?? collect())
            : $this->occupying($counsellorId)->whereDate('appointment_date', $date)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->get(['start_time', 'end_time']);

        return $bookings->toBase()
            ->map(fn ($a) => [CarbonImmutable::parse("{$date} {$a->start_time}", $tz), CarbonImmutable::parse("{$date} {$a->end_time}", $tz)]);
    }
}
