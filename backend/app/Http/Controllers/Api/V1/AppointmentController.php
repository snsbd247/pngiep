<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StudyLevel;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Counsellor;
use App\Models\Destination;
use App\Models\Service;
use App\Services\AdminNotifier;
use App\Services\AppointmentBooking;
use App\Services\AppointmentScheduler;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Public booking flow: options → availability (calendar) → slots → book,
 * plus the student's "manage my appointment" link (view / cancel / reschedule).
 */
class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentScheduler $scheduler,
        private readonly AppointmentBooking $booking,
        private readonly SettingService $settings,
    ) {}

    /** Services, counsellors and booking rules for the form. */
    public function options(): JsonResponse
    {
        $counsellors = Counsellor::query()->published()->ordered()->with('services:id')
            ->get(['id', 'name', 'slug', 'photo', 'designation', 'bio', 'experience_years', 'languages', 'slot_duration']);

        return ApiResponse::success([
            'services' => Service::query()->published()->where('is_bookable', true)->ordered()->get(['id', 'title', 'slug', 'icon', 'short_description']),
            'counsellors' => $counsellors->map(fn (Counsellor $c) => $c->only(['id', 'name', 'slug', 'photo', 'designation', 'bio', 'experience_years', 'languages'])
                + ['service_ids' => $c->services->pluck('id'), 'duration' => $this->scheduler->durationFor($c)]),
            'destinations' => Destination::query()->published()->ordered()->get(['id', 'name', 'slug']),
            'rules' => [
                'timezone' => $this->scheduler->timezone(),
                'today' => $this->scheduler->now()->toDateString(),
                'last_date' => $this->scheduler->lastBookableDate()->toDateString(),
                'modes' => (array) $this->settings->get('appointment_modes', ['in_person']),
                'cancellation_policy' => $this->settings->get('appointment_cancellation_policy'),
            ],
        ]);
    }

    /** GET /appointments/availability?counsellor=1&month=2026-10 → { '2026-10-12': 6, … } */
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'counsellor' => ['required', 'integer'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        return ApiResponse::success($this->scheduler->availableDays($this->counsellor($data['counsellor']), $data['month']));
    }

    /** GET /appointments/slots?counsellor=1&date=2026-10-12 */
    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'counsellor' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return ApiResponse::success($this->scheduler->slots($this->counsellor($data['counsellor']), $data['date']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->where('status', 'published')->whereNull('deleted_at')],
            'counsellor_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'mode' => ['required', Rule::in((array) $this->settings->get('appointment_modes', ['in_person']))],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'study_level' => ['nullable', Rule::enum(StudyLevel::class)],
            'message' => ['nullable', 'string', 'max:2000'],
            'agree' => ['accepted'],
            'website' => ['prohibited'],
        ], ['agree.accepted' => 'Please accept the terms to continue.', 'website.prohibited' => 'Your booking could not be completed.']);

        $counsellor = $this->counsellor($data['counsellor_id']);
        if (! AppointmentBooking::counsellorOffers($counsellor, $data['service_id'] ?? null)) {
            throw ValidationException::withMessages(['counsellor_id' => 'This counsellor does not offer the selected service.']);
        }

        $appointment = $this->booking->book($data);

        return ApiResponse::created($this->present($appointment), 'Your appointment has been booked.');
    }

    // ------------------------------------------------------------ manage link

    public function show(string $token): JsonResponse
    {
        return ApiResponse::success($this->present($this->byToken($token)));
    }

    public function cancel(Request $request, string $token): JsonResponse
    {
        $appointment = $this->byToken($token);
        abort_unless($this->booking->canStudentChange($appointment), 422, 'This appointment can no longer be cancelled online. Please contact us.');

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;
        $this->booking->cancel($appointment, $reason, byStudent: true);
        app(AdminNotifier::class)->appointment($appointment, "Appointment cancelled by {$appointment->name}", $reason);

        return ApiResponse::success($this->present($appointment->fresh()), 'Your appointment has been cancelled.');
    }

    public function reschedule(Request $request, string $token): JsonResponse
    {
        $appointment = $this->byToken($token);
        abort_unless($this->booking->canStudentChange($appointment), 422, 'This appointment can no longer be changed online. Please contact us.');

        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'time' => ['required', 'date_format:H:i']]);
        $new = $this->booking->reschedule($appointment, $data['date'], $data['time']);
        app(AdminNotifier::class)->appointment($new, "Appointment rescheduled by {$new->name}", 'New time: '.$new->appointment_date->format('j M').' at '.substr($new->start_time, 0, 5));

        return ApiResponse::success($this->present($new), 'Your appointment has been rescheduled.');
    }

    // ------------------------------------------------------------ helpers

    private function counsellor(int $id): Counsellor
    {
        return Counsellor::query()->published()->with(['availabilities', 'timeOffs', 'services:id'])->findOrFail($id);
    }

    private function byToken(string $token): Appointment
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);

        // A rescheduled appointment's token resolves to its replacement.
        $appointment = Appointment::query()->where('manage_token', $token)->firstOrFail();
        while ($next = Appointment::query()->where('rescheduled_from_id', $appointment->id)->first()) {
            $appointment = $next;
        }

        return $appointment->load(['counsellor:id,name,photo,designation', 'service:id,title']);
    }

    private function present(Appointment $a): array
    {
        $a->loadMissing(['counsellor:id,name,photo,designation', 'service:id,title']);

        return [
            'reference' => $a->reference,
            'status' => $a->status->value,
            'status_label' => $a->status->label(),
            'date' => $a->appointment_date->toDateString(),
            'start_time' => substr($a->start_time, 0, 5),
            'end_time' => substr($a->end_time, 0, 5),
            'duration_minutes' => $a->duration_minutes,
            'timezone' => $this->scheduler->timezone(),
            'mode' => $a->mode,
            'name' => $a->name,
            'email' => preg_replace('/(?<=^.).*(?=@)/', '***', (string) $a->email),
            'counsellor' => $a->counsellor?->only(['id', 'name', 'photo', 'designation']),
            'service' => $a->service?->only(['id', 'title']),
            'manage_token' => $a->manage_token,
            'can_change' => $this->booking->canStudentChange($a),
            'cancellation_policy' => $this->settings->get('appointment_cancellation_policy'),
        ];
    }
}
