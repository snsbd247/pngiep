<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    use ManagesNotes;

    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'assigned_to' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filtered = fn () => Lead::query()
            ->when($params['source'] ?? null, fn ($q, $s) => $q->where('source', $s))
            ->when($params['assigned_to'] ?? null, fn ($q, $a) => $q->where('assigned_to', $a))
            ->when($params['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")));

        $page = $filtered()->when($params['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->with(['destination:id,name', 'assignee:id,name'])
            ->latest('id')->paginate($params['per_page'] ?? 20);

        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
            'counts' => $filtered()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /** GET /admin/leads/sources?from=&to=: totals, new, converted and conversion rate per source. */
    public function sources(Request $request): JsonResponse
    {
        $range = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $rows = Lead::query()
            ->when($range['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d.' 00:00:00'))
            ->when($range['to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', $d.' 23:59:59'))
            ->selectRaw('source, count(*) as total, sum(case when status = ? then 1 else 0 end) as new_count, sum(case when status = ? then 1 else 0 end) as converted, max(created_at) as last_at', [LeadStatus::New->value, LeadStatus::Converted->value])
            ->groupBy('source')->get()->keyBy(fn ($r) => $r->source instanceof LeadSource ? $r->source->value : $r->source);

        $total = (int) $rows->sum('total');

        return ApiResponse::success(collect(LeadSource::cases())->map(function (LeadSource $s) use ($rows, $total) {
            $r = $rows[$s->value] ?? null;
            $count = (int) ($r->total ?? 0);

            return [
                'source' => $s->value, 'label' => $s->label(), 'total' => $count, 'new' => (int) ($r->new_count ?? 0), 'converted' => (int) ($r->converted ?? 0),
                'conversion_rate' => $count ? round(($r->converted ?? 0) / $count * 100, 1) : 0, 'share' => $total ? round($count / $total * 100, 1) : 0,
                'last_at' => $r?->last_at ? \Illuminate\Support\Carbon::parse($r->last_at)->toIso8601String() : null,
            ];
        })->sortByDesc('total')->values(), meta: ['total' => $total]);
    }

    public function show(int $id): JsonResponse
    {
        $lead = Lead::query()->with([
            'destination:id,name', 'university:id,name,slug', 'course:id,name,slug', 'assignee:id,name', 'notes.user:id,name',
            'appointments' => fn ($q) => $q->latest('appointment_date')->select(['id', 'lead_id', 'reference', 'appointment_date', 'start_time', 'status', 'counsellor_id'])->with('counsellor:id,name'),
            'applications' => fn ($q) => $q->latest('id')->select(['id', 'lead_id', 'reference', 'status', 'created_at']),
            'contactMessages' => fn ($q) => $q->latest('id')->select(['id', 'lead_id', 'subject', 'inquiry_type', 'status', 'created_at']),
        ])->findOrFail($id);

        return ApiResponse::success($lead);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $lead = Lead::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);

        if (isset($data['status']) && $data['status'] !== LeadStatus::New->value && $lead->status === LeadStatus::New) {
            $data['last_contacted_at'] = now();
        }
        $lead->update($data);

        return ApiResponse::success($lead->load(['assignee:id,name']), 'Lead updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        Lead::query()->findOrFail($id)->delete();

        return ApiResponse::success(null, 'Lead deleted.');
    }

    protected function findForNotes(int $id): Model
    {
        return Lead::query()->findOrFail($id);
    }
}
