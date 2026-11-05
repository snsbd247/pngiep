<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Crm\CounsellorRequest;
use App\Models\Counsellor;
use App\Services\AppointmentScheduler;
use Illuminate\Http\JsonResponse;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class CounsellorController extends CrudController
{
    protected string $model = Counsellor::class;

    protected string $request = CounsellorRequest::class;

    protected array $searchable = ['name', 'designation', 'email'];

    protected array $filters = ['status' => 'status'];

    /** GET /admin/counsellors/availability: weekly hours, upcoming days off and this week's bookings for everyone. */
    public function availability(): JsonResponse
    {
        $scheduler = app(AppointmentScheduler::class);
        $today = $scheduler->now()->toDateString();
        $weekEnd = $scheduler->now()->addDays(6)->toDateString();

        $counsellors = Counsellor::query()->orderBy('sort_order')->orderBy('name')
            ->with(['availabilities' => fn ($q) => $q->where('is_active', true)->orderBy('start_time'), 'timeOffs' => fn ($q) => $q->whereDate('date', '>=', $today)->orderBy('date')->limit(10)])
            ->withCount(['appointments as week_bookings' => fn ($q) => $q->occupyingSlot()->whereDate('appointment_date', '>=', $today)->whereDate('appointment_date', '<=', $weekEnd)])
            ->get()
            ->map(fn (Counsellor $c) => [
                'id' => $c->id, 'name' => $c->name, 'designation' => $c->designation, 'photo' => $c->photo, 'status' => $c->status,
                'slot_duration' => $scheduler->durationFor($c), 'week_bookings' => $c->week_bookings,
                'days' => collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => $c->availabilities->where('day_of_week', $d)
                    ->map(fn ($w) => substr($w->start_time, 0, 5).'–'.substr($w->end_time, 0, 5))->values()])->all(),
                'time_offs' => $c->timeOffs->map(fn ($t) => ['date' => $t->date->toDateString(), 'start_time' => $t->start_time ? substr($t->start_time, 0, 5) : null, 'end_time' => $t->end_time ? substr($t->end_time, 0, 5) : null, 'reason' => $t->reason]),
            ]);

        return ApiResponse::success($counsellors, meta: [
            'timezone' => $scheduler->timezone(),
            'working_days' => array_map('intval', (array) app(\App\Services\SettingService::class)->get('appointment_working_days', [])),
        ]);
    }

    protected function query(): Builder
    {
        return Counsellor::query()->withCount(['appointments as upcoming_count' => fn ($q) => $q->occupyingSlot()->whereDate('appointment_date', '>=', app(AppointmentScheduler::class)->now()->toDateString())]);
    }

    protected function prepare(array $data, ?Model $model): array
    {
        unset($data['service_ids'], $data['availabilities'], $data['time_offs']);

        return parent::prepare($data, $model);
    }

    protected function afterSave(Model $model, FormRequest $request): void
    {
        parent::afterSave($model, $request);
        /** @var Counsellor $model */
        if ($request->has('service_ids')) {
            $model->services()->sync($request->validated('service_ids') ?? []);
        }
        if ($request->has('availabilities')) {
            $model->availabilities()->delete();
            foreach ($request->validated('availabilities') ?? [] as $window) {
                $model->availabilities()->create($window + ['is_active' => true]);
            }
        }
        if ($request->has('time_offs')) {
            $model->timeOffs()->delete();
            foreach ($request->validated('time_offs') ?? [] as $off) {
                $model->timeOffs()->create($off);
            }
        }
    }

    protected function transform(Model $model, bool $detail = false): array
    {
        $data = $model->toArray();
        if ($detail) {
            $data['service_ids'] = $model->services()->pluck('services.id');
            $data['availabilities'] = $model->availabilities()->get(['day_of_week', 'start_time', 'end_time'])
                ->map(fn ($w) => ['day_of_week' => $w->day_of_week, 'start_time' => substr($w->start_time, 0, 5), 'end_time' => substr($w->end_time, 0, 5)]);
            $data['time_offs'] = $model->timeOffs()->orderBy('date')->get()
                ->map(fn ($t) => ['date' => $t->date->toDateString(), 'start_time' => $t->start_time ? substr($t->start_time, 0, 5) : null, 'end_time' => $t->end_time ? substr($t->end_time, 0, 5) : null, 'reason' => $t->reason]);
        }

        return $data;
    }

    protected function beforeDelete(Model $model): void
    {
        $upcoming = $model->appointments()->whereDate('appointment_date', '>=', app(AppointmentScheduler::class)->now()->toDateString())
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])->count();
        abort_if($upcoming > 0, 422, "This counsellor has {$upcoming} upcoming appointments. Reschedule or cancel them first.");
    }
}
