<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Counsellor;
use App\Services\AppointmentBooking;
use App\Services\AppointmentScheduler;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin appointments. Users with appointments.manage see everything; counsellors
 * (appointments.view only) see and manage just their own appointments.
 */
class AppointmentController extends Controller
{
    use ManagesNotes;

    public function __construct(private readonly AppointmentBooking $booking, private readonly AppointmentScheduler $scheduler) {}

    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'view' => ['nullable', 'in:today,upcoming,past,all'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'counsellor_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $today = $this->scheduler->now()->toDateString();
        $view = $params['view'] ?? 'upcoming';

        $base = fn () => $this->scoped()
            ->when($params['counsellor_id'] ?? null, fn ($q, $c) => $q->where('counsellor_id', $c))
            ->when($params['date_from'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', '>=', $d))
            ->when($params['date_to'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', '<=', $d))
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%")));

        $query = $base()
            ->when($view === 'today', fn ($q) => $q->whereDate('appointment_date', $today))
            ->when($view === 'upcoming', fn ($q) => $q->whereDate('appointment_date', '>=', $today))
            ->when($view === 'past', fn ($q) => $q->whereDate('appointment_date', '<', $today))
            ->when($params['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->with(['counsellor:id,name', 'service:id,title']);

        $view === 'past' ? $query->orderByDesc('appointment_date')->orderByDesc('start_time') : $query->orderBy('appointment_date')->orderBy('start_time');
        $page = $query->paginate($params['per_page'] ?? 20);

        $counts = $base()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
            'counts' => $counts,
            'today_count' => $base()->whereDate('appointment_date', $today)->occupyingSlot()->count(),
            'timezone' => $this->scheduler->timezone(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $a = $this->find($id)->load([
            'counsellor:id,name,photo,designation', 'service:id,title', 'destination:id,name', 'lead:id,name,status',
            'notes.user:id,name', 'rescheduledFrom:id,reference,appointment_date,start_time',
        ]);

        return ApiResponse::success($a->toArray() + [
            'rescheduled_to' => Appointment::query()->where('rescheduled_from_id', $a->id)->first(['id', 'reference', 'appointment_date', 'start_time']),
            'timezone' => $this->scheduler->timezone(),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $status = AppointmentStatus::from($request->validate([
            'status' => ['required', Rule::in([AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value, AppointmentStatus::Completed->value, AppointmentStatus::NoShow->value])],
        ])['status']);

        return ApiResponse::success($this->booking->setStatus($this->find($id), $status), "Appointment marked as {$status->label()}.");
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        return ApiResponse::success($this->booking->cancel($this->find($id), $reason), 'Appointment cancelled. The student has been notified.');
    }

    public function reschedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'counsellor_id' => ['nullable', 'integer'],
        ]);
        $appointment = $this->find($id);
        if (isset($data['counsellor_id']) && ! $request->user()->can('appointments.manage')) {
            abort(403);
        }
        $new = $this->booking->reschedule($appointment, $data['date'], $data['time'], $data['counsellor_id'] ?? null);

        return ApiResponse::success(['id' => $new->id, 'reference' => $new->reference], 'Appointment rescheduled. The student has been notified.');
    }

    /** Free slots for the reschedule picker (same rules as the public booking page). */
    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate(['counsellor_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d'], 'ignore' => ['nullable', 'integer']]);
        $counsellor = Counsellor::query()->with(['availabilities', 'timeOffs'])->findOrFail($data['counsellor_id']);

        return ApiResponse::success($this->scheduler->slots($counsellor, $data['date'], $data['ignore'] ?? null));
    }

    protected function findForNotes(int $id): Model
    {
        return $this->find($id);
    }

    private function scoped(): Builder
    {
        $user = request()->user();

        return Appointment::query()->when(! $user->can('appointments.manage'), fn ($q) => $q->whereHas('counsellor', fn ($c) => $c->where('user_id', $user->id)));
    }

    private function find(int $id): Appointment
    {
        return $this->scoped()->findOrFail($id);
    }
}
