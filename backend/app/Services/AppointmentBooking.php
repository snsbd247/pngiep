<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\LeadSource;
use App\Models\Appointment;
use App\Models\Counsellor;
use App\Models\Destination;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates and changes appointments. Double booking is prevented three ways:
 *  1. the counsellor row is locked (SELECT … FOR UPDATE) for the whole transaction,
 *  2. the slot is re-checked against live availability inside the lock,
 *  3. the (counsellor, date, start, slot_lock) unique index rejects any duplicate.
 */
class AppointmentBooking
{
    public function __construct(
        private readonly AppointmentScheduler $scheduler,
        private readonly LeadService $leads,
        private readonly TemplateMailer $mailer,
        private readonly SettingService $settings,
    ) {}

    public function book(array $data): Appointment
    {
        $appointment = $this->inSlotLock($data['counsellor_id'], function (Counsellor $counsellor) use ($data) {
            $this->assertSlotFree($counsellor, $data['date'], $data['time']);

            $lead = $this->leads->capture(LeadSource::Appointment, [
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'destination_id' => $data['destination_id'] ?? null, 'study_level' => $data['study_level'] ?? null,
                'message' => $data['message'] ?? null,
            ], notify: false);

            $autoConfirm = (bool) $this->settings->get('appointment_auto_confirm', false);
            $start = CarbonImmutable::parse("{$data['date']} {$data['time']}");

            return Appointment::query()->create([
                'service_id' => $data['service_id'] ?? null,
                'counsellor_id' => $counsellor->id,
                'lead_id' => $lead->id,
                'destination_id' => $data['destination_id'] ?? null,
                'appointment_date' => $data['date'],
                'start_time' => $start->format('H:i:s'),
                'end_time' => $start->addMinutes($this->scheduler->durationFor($counsellor))->format('H:i:s'),
                'duration_minutes' => $this->scheduler->durationFor($counsellor),
                'mode' => $data['mode'] ?? 'in_person',
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'],
                'study_level' => $data['study_level'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => $autoConfirm ? AppointmentStatus::Confirmed : AppointmentStatus::Pending,
                'confirmed_at' => $autoConfirm ? now() : null,
                'ip_address' => request()?->ip(),
            ]);
        });

        $appointment->load(['counsellor', 'service']);
        $this->mailer->send($appointment->status === AppointmentStatus::Confirmed ? 'appointment_confirmed' : 'appointment_received', $appointment->email, $this->vars($appointment));
        $this->mailer->toAdmin('admin_new_appointment', $this->vars($appointment));
        app(AdminNotifier::class)->appointment($appointment, "New appointment: {$appointment->name}",
            $appointment->appointment_date->format('j M').' at '.substr($appointment->start_time, 0, 5).($appointment->service ? " · {$appointment->service->title}" : ''));

        return $appointment;
    }

    /** Moves an appointment: the old one is marked "rescheduled" (freeing its slot) and a new one is created. */
    public function reschedule(Appointment $old, string $date, string $time, ?int $counsellorId = null): Appointment
    {
        $this->assertChangeable($old);

        $new = $this->inSlotLock($counsellorId ?? $old->counsellor_id, function (Counsellor $counsellor) use ($old, $date, $time) {
            $this->assertSlotFree($counsellor, $date, $time, ignoreId: $old->id);

            $old->update(['status' => AppointmentStatus::Rescheduled]);
            $start = CarbonImmutable::parse("{$date} {$time}");
            $duration = $this->scheduler->durationFor($counsellor);

            return Appointment::query()->create($old->only([
                'service_id', 'lead_id', 'destination_id', 'mode', 'name', 'email', 'phone', 'study_level', 'message',
            ]) + [
                'counsellor_id' => $counsellor->id,
                'appointment_date' => $date,
                'start_time' => $start->format('H:i:s'),
                'end_time' => $start->addMinutes($duration)->format('H:i:s'),
                'duration_minutes' => $duration,
                'status' => AppointmentStatus::Confirmed,
                'confirmed_at' => now(),
                'rescheduled_from_id' => $old->id,
            ]);
        });

        $new->load(['counsellor', 'service']);
        $this->mailer->send('appointment_rescheduled', $new->email, $this->vars($new, ['old_date' => $old->appointment_date->format('l, j F Y'), 'old_time' => substr($old->start_time, 0, 5)]));
        $this->mailer->toAdmin('admin_appointment_changed', $this->vars($new, ['change' => 'rescheduled']));

        return $new;
    }

    public function cancel(Appointment $appointment, ?string $reason = null, bool $byStudent = false): Appointment
    {
        $this->assertChangeable($appointment);

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?: ($byStudent ? 'Cancelled by the student' : null),
        ]);

        $appointment->load(['counsellor', 'service']);
        $this->mailer->send('appointment_cancelled', $appointment->email, $this->vars($appointment));
        if ($byStudent) {
            $this->mailer->toAdmin('admin_appointment_changed', $this->vars($appointment, ['change' => 'cancelled by the student']));
        }

        return $appointment;
    }

    public function setStatus(Appointment $appointment, AppointmentStatus $status): Appointment
    {
        abort_if(in_array($status, [AppointmentStatus::Cancelled, AppointmentStatus::Rescheduled], true), 422, 'Use cancel or reschedule instead.');
        abort_if($appointment->status === AppointmentStatus::Rescheduled, 422, 'This appointment was moved; update the new one instead.');
        abort_if($appointment->status === AppointmentStatus::Cancelled && $status !== AppointmentStatus::Pending, 422, 'Cancelled appointments cannot change status.');

        if ($appointment->status === AppointmentStatus::Cancelled) {
            // Re-opening a cancelled booking must still respect the slot.
            $counsellor = $appointment->counsellor()->firstOrFail();
            $this->assertSlotFree($counsellor, $appointment->appointment_date->toDateString(), $appointment->start_time, ignoreId: $appointment->id, ignoreRules: true);
        }

        $appointment->fill(['status' => $status] + match ($status) {
            AppointmentStatus::Confirmed => ['confirmed_at' => now()],
            AppointmentStatus::Completed => ['completed_at' => now()],
            default => [],
        })->save();

        if ($status === AppointmentStatus::Confirmed) {
            $appointment->load(['counsellor', 'service']);
            $this->mailer->send('appointment_confirmed', $appointment->email, $this->vars($appointment));
        }

        return $appointment;
    }

    /** Whether the student may still cancel / reschedule (Settings > Appointment > cancellation notice). */
    public function canStudentChange(Appointment $appointment): bool
    {
        if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)) {
            return false;
        }
        $starts = CarbonImmutable::parse($appointment->appointment_date->toDateString().' '.$appointment->start_time, $this->scheduler->timezone());

        return $this->scheduler->now()->addHours((int) $this->settings->get('appointment_cancel_notice_hours', 24))->lt($starts);
    }

    public function vars(Appointment $a, array $extra = []): array
    {
        $date = CarbonImmutable::parse($a->appointment_date->toDateString());

        return $extra + [
            'name' => $a->name,
            'email' => $a->email,
            'phone' => $a->phone,
            'reference' => $a->reference,
            'date' => $date->format('l, j F Y'),
            'time' => substr($a->start_time, 0, 5).' – '.substr($a->end_time, 0, 5),
            'timezone' => $this->scheduler->timezone(),
            'counsellor' => $a->counsellor?->name ?? '',
            'service' => $a->service?->title ?? '',
            'mode' => ['in_person' => 'In person', 'online' => 'Online (video call)', 'phone' => 'Phone call'][$a->mode] ?? $a->mode,
            'status' => $a->status->label(),
            'reason' => $a->cancellation_reason ?? '',
            'manage_url' => rtrim(config('app.frontend_url'), '/')."/appointment/{$a->manage_token}",
            'admin_url' => rtrim(config('app.frontend_url'), '/')."/admin/appointments/{$a->id}",
            'destination' => $a->destination_id ? (Destination::query()->whereKey($a->destination_id)->value('name') ?? '') : '',
        ];
    }

    // ------------------------------------------------------------ internals

    /** Runs $callback with the counsellor row locked; maps a unique-index clash to a friendly error. */
    private function inSlotLock(int $counsellorId, callable $callback): Appointment
    {
        try {
            return DB::transaction(function () use ($counsellorId, $callback) {
                $counsellor = Counsellor::query()->published()->whereKey($counsellorId)->lockForUpdate()
                    ->with(['availabilities', 'timeOffs'])->first();

                if (! $counsellor) {
                    throw ValidationException::withMessages(['counsellor_id' => 'This counsellor is not available for booking.']);
                }

                return $callback($counsellor);
            });
        } catch (QueryException $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'appointments_slot_unique') || str_contains($message, 'duplicate entry') || str_contains($message, 'unique constraint')) {
                throw ValidationException::withMessages(['time' => 'Sorry, this time slot was just booked. Please choose another time.']);
            }
            throw $e;
        }
    }

    private function assertSlotFree(Counsellor $counsellor, string $date, string $time, ?int $ignoreId = null, bool $ignoreRules = false): void
    {
        if ($ignoreRules) {
            $clash = Appointment::query()->where('counsellor_id', $counsellor->id)->whereDate('appointment_date', $date)
                ->where('start_time', substr($time, 0, 5).':00')->occupyingSlot()->whereKeyNot($ignoreId)->exists();
            if ($clash) {
                throw ValidationException::withMessages(['time' => 'That time slot is now taken by another booking.']);
            }

            return;
        }

        if (! $this->scheduler->isSlotFree($counsellor, $date, $time, $ignoreId)) {
            throw ValidationException::withMessages(['time' => 'Sorry, this time slot is no longer available. Please choose another time.']);
        }
    }

    private function assertChangeable(Appointment $appointment): void
    {
        abort_unless(in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true), 422, 'Only pending or confirmed appointments can be changed.');
    }

    /** Services a counsellor offers (empty = all bookable services). */
    public static function counsellorOffers(Counsellor $counsellor, ?int $serviceId): bool
    {
        if (! $serviceId) {
            return true;
        }
        $ids = $counsellor->services->pluck('id');

        return $ids->isEmpty() || $ids->contains($serviceId) || ! Service::query()->whereKey($serviceId)->exists();
    }
}
