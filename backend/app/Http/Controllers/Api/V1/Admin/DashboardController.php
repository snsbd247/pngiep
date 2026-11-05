<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AppointmentStatus;
use App\Enums\ContactStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Appointment;
use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Destination;
use App\Models\GalleryAlbum;
use App\Models\Lead;
use App\Models\Service;
use App\Models\SuccessStory;
use App\Models\University;
use App\Services\AppointmentScheduler;
use App\Services\SeoService;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin dashboard: KPIs for a date range (with change vs the previous period of the same
 * length), daily activity chart, enquiry sources, recent records, latest content and a
 * system activity feed. Every block respects the user's permissions; counsellors only
 * ever see their own appointments.
 */
class DashboardController extends Controller
{
    /** Lead source → enquiry-source bucket shown in the donut. */
    private const SOURCE_BUCKETS = [
        'contact_form' => 'Contact Form',
        'appointment' => 'Appointment',
        'apply_now' => 'Apply Now',
        'university_enquiry' => 'University Enquiry',
        'course_enquiry' => 'Course Enquiry',
        'destination_enquiry' => 'Website/Other',
        'newsletter' => 'Website/Other',
    ];

    public function __construct(private readonly AppointmentScheduler $scheduler, private readonly SeoService $seo) {}

    public function __invoke(Request $request): JsonResponse
    {
        [$from, $to] = $this->period($request);
        $user = $request->user();
        $can = fn (string $permission) => $user->can($permission);
        $appointments = $this->appointmentScope($request);

        // Previous period of the same length, for the "vs last month" figures.
        $days = $from->diffInDays($to) + 1;
        $prevFrom = $from->subDays($days);
        $prevTo = $from->subDay();
        $isMonth = $from->day === 1 && $to->isSameDay($from->endOfMonth());

        $count = fn (Builder $q, CarbonImmutable $a, CarbonImmutable $b, string $col = 'created_at') => (clone $q)->whereBetween($col, [$a->startOfDay(), $b->endOfDay()])->count();
        $card = function (string $key, Builder $query) use ($count, $from, $to, $prevFrom, $prevTo) {
            $now = $count($query, $from, $to);
            $before = $count($query, $prevFrom, $prevTo);

            return ['key' => $key, 'value' => $now, 'previous' => $before, 'change' => $before > 0 ? round(($now - $before) / $before * 100, 1) : null];
        };

        $cards = array_values(array_filter([
            $can('leads.manage') ? $card('enquiries', Lead::query()) : null,
            $can('appointments.view') ? $card('appointments', $appointments()) : null,
            $can('applications.manage') ? $card('applications', Application::query()) : null,
            $card('success_stories', SuccessStory::query()),
        ]));

        $newIn = fn (Builder $q) => $count($q, $from, $to);
        $totals = [
            ['key' => 'destinations', 'value' => Destination::query()->published()->count(), 'new' => $newIn(Destination::query())],
            ['key' => 'universities', 'value' => University::query()->published()->count(), 'new' => $newIn(University::query())],
            ['key' => 'courses', 'value' => Course::query()->published()->count(), 'new' => $newIn(Course::query())],
            ['key' => 'services', 'value' => Service::query()->published()->count(), 'new' => $newIn(Service::query())],
            ['key' => 'blogs', 'value' => Blog::query()->published()->count(), 'new' => $newIn(Blog::query())],
        ];

        return ApiResponse::success([
            'period' => [
                'from' => $from->toDateString(), 'to' => $to->toDateString(),
                'compare_label' => $isMonth ? 'vs last month' : 'vs previous period',
            ],
            'cards' => $cards,
            'totals' => $totals,
            'chart' => $this->chart($from, $to, $can, $appointments),
            'sources' => $can('leads.manage') ? $this->sources($from, $to) : null,
            'recent_enquiries' => $can('leads.manage') ? $this->recentEnquiries() : null,
            'upcoming_appointments' => $can('appointments.view') ? $this->upcomingAppointments($appointments) : null,
            'recent_applications' => $can('applications.manage') ? $this->recentApplications() : null,
            'latest_blogs' => Blog::query()->published()->with('category:id,name')->latest('published_at')->limit(3)
                ->get(['id', 'title', 'slug', 'featured_image', 'published_at', 'blog_category_id'])
                ->map(fn ($b) => ['id' => $b->id, 'title' => $b->title, 'category' => $b->category?->name, 'date' => $b->published_at?->toDateString(), 'image' => $this->seo->assetUrl($b->featured_image)]),
            'latest_stories' => SuccessStory::query()->published()->latest()->limit(3)
                ->get(['id', 'student_name', 'university_name', 'photo', 'cover_image', 'created_at'])
                ->map(fn ($s) => ['id' => $s->id, 'title' => trim($s->student_name.($s->university_name ? ' – '.$s->university_name : '')), 'date' => $s->created_at?->toDateString(), 'image' => $this->seo->assetUrl($s->photo ?: $s->cover_image)]),
            'activity' => $this->activity($can, $appointments),
            'notifications' => $this->notifications($can, $appointments),
        ]);
    }

    /** GET /admin/notifications: the header bell (any signed-in staff member). */
    public function bell(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success($this->notifications(fn (string $p) => $user->can($p), $this->appointmentScope($request)));
    }

    /** GET /admin/dashboard/report: CSV of the period (summary + enquiries, appointments, applications). */
    public function report(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $user = $request->user();
        $appointments = $this->appointmentScope($request);
        $in = fn (Builder $q, string $col = 'created_at') => $q->whereBetween($col, [$from->startOfDay(), $to->endOfDay()]);

        return response()->streamDownload(function () use ($user, $appointments, $in, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel: UTF-8
            // Neutralise spreadsheet formulas in user-entered values.
            $row = fn (array $cells) => fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v, $cells));

            $row(['Report', $from->toDateString().' to '.$to->toDateString()]);
            $row([]);
            if ($user->can('leads.manage')) {
                $leads = $in(Lead::query())->latest()->get(['name', 'email', 'phone', 'source', 'status', 'created_at']);
                $row(['ENQUIRIES', $leads->count()]);
                $row(['Name', 'Email', 'Phone', 'Source', 'Status', 'Date']);
                $leads->each(fn ($l) => $row([$l->name, $l->email, $l->phone, $l->source?->label(), $l->status?->label(), $l->created_at?->toDateTimeString()]));
                $row([]);
            }
            if ($user->can('appointments.view')) {
                $list = $in($appointments())->with(['counsellor:id,name', 'service:id,title'])->orderBy('appointment_date')->get();
                $row(['APPOINTMENTS', $list->count()]);
                $row(['Reference', 'Name', 'Email', 'Phone', 'Service', 'Counsellor', 'Date', 'Time', 'Status']);
                $list->each(fn ($a) => $row([$a->reference, $a->name, $a->email, $a->phone, $a->service?->title, $a->counsellor?->name, $a->appointment_date?->toDateString(), substr((string) $a->start_time, 0, 5), $a->status?->label()]));
                $row([]);
            }
            if ($user->can('applications.manage')) {
                $list = $in(Application::query())->with(['university:id,name', 'course:id,name'])->latest()->get();
                $row(['APPLICATIONS', $list->count()]);
                $row(['Reference', 'Name', 'Email', 'Phone', 'University', 'Course', 'Status', 'Date']);
                $list->each(fn ($a) => $row([$a->reference, $a->name, $a->email, $a->phone, $a->university?->name, $a->course?->name, $a->status?->label(), $a->created_at?->toDateTimeString()]));
            }
            fclose($out);
        }, "report-{$from->toDateString()}-to-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} defaults to the current month (booking timezone) */
    private function period(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        // "Today" in the booking timezone; the period itself is plain dates (app timezone).
        $today = CarbonImmutable::parse($this->scheduler->now()->toDateString());
        $from = isset($data['from']) ? CarbonImmutable::parse($data['from']) : $today->startOfMonth();
        $to = isset($data['to']) ? CarbonImmutable::parse($data['to']) : $today->endOfMonth()->startOfDay();
        // Keep queries bounded: at most two years.
        if ($from->diffInDays($to) > 731) {
            $from = $to->subDays(731);
        }

        return [$from, $to];
    }

    private function appointmentScope(Request $request): \Closure
    {
        $user = $request->user();

        return fn (): Builder => Appointment::query()
            ->when(! $user->can('appointments.manage'), fn ($q) => $q->whereHas('counsellor', fn ($c) => $c->where('user_id', $user->id)));
    }

    /** One point per day (per week beyond ~2 months, per month beyond ~7 months). */
    private function chart(CarbonImmutable $from, CarbonImmutable $to, callable $can, \Closure $appointments): array
    {
        $days = $from->diffInDays($to) + 1;
        $unit = $days <= 62 ? 'day' : ($days <= 210 ? 'week' : 'month');
        $bucket = fn ($date) => match ($unit) {
            'day' => CarbonImmutable::parse($date)->toDateString(),
            'week' => CarbonImmutable::parse($date)->startOfWeek()->toDateString(),
            'month' => CarbonImmutable::parse($date)->startOfMonth()->toDateString(),
        };
        $series = function (Builder $q) use ($from, $to, $bucket): Collection {
            return $q->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->pluck('created_at')
                ->countBy(fn ($d) => $bucket($d));
        };

        $data = [
            'enquiries' => $can('leads.manage') ? $series(Lead::query()) : collect(),
            'appointments' => $can('appointments.view') ? $series($appointments()) : collect(),
            'applications' => $can('applications.manage') ? $series(Application::query()) : collect(),
        ];

        // Stop at today: future days would only draw the lines down to zero.
        $end = $to->min(CarbonImmutable::parse($this->scheduler->now()->toDateString()));
        $points = [];
        for ($d = CarbonImmutable::parse($bucket($from)); $d->lte($end); $d = $d->add(1, $unit)) {
            $key = $d->toDateString();
            $points[] = ['date' => $key] + array_map(fn ($s) => $s[$key] ?? 0, $data);
        }

        return ['unit' => $unit, 'points' => $points];
    }

    private function sources(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $counts = Lead::query()->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source');

        $buckets = collect(array_unique(self::SOURCE_BUCKETS))->mapWithKeys(fn ($label) => [$label => 0])->all();
        foreach ($counts as $source => $total) {
            $value = $source instanceof LeadSource ? $source->value : (string) $source;
            $buckets[self::SOURCE_BUCKETS[$value] ?? 'Website/Other'] += (int) $total;
        }
        $sum = array_sum($buckets);

        return [
            'total' => $sum,
            'items' => collect($buckets)->map(fn ($n, $label) => ['label' => $label, 'count' => $n, 'percent' => $sum ? (int) round($n / $sum * 100) : 0])->values()->all(),
        ];
    }

    private function recentEnquiries(): Collection
    {
        return Lead::query()->with(['destination:id,name', 'contactMessages' => fn ($q) => $q->latest()->select(['id', 'lead_id', 'inquiry_type'])])
            ->latest()->limit(5)->get()
            ->map(fn (Lead $l) => [
                'id' => $l->id, 'name' => $l->name ?: $l->email, 'date' => $l->created_at?->toDateString(),
                'type' => match ($l->source) {
                    LeadSource::ContactForm => $l->contactMessages->first()?->inquiry_type ?? 'Contact Form',
                    LeadSource::DestinationEnquiry => $l->destination ? "Study in {$l->destination->name}" : 'Destination Enquiry',
                    LeadSource::ApplyNow => 'Application',
                    default => $l->source?->label(),
                },
                'status' => $l->status?->value, 'status_label' => $l->status?->label(),
            ]);
    }

    private function upcomingAppointments(\Closure $appointments): Collection
    {
        $now = $this->scheduler->now();

        return $appointments()->occupyingSlot()
            ->where(fn ($q) => $q->whereDate('appointment_date', '>', $now->toDateString())
                ->orWhere(fn ($w) => $w->whereDate('appointment_date', $now->toDateString())->where('start_time', '>=', $now->format('H:i:s'))))
            ->with('counsellor:id,name,photo')->orderBy('appointment_date')->orderBy('start_time')->limit(5)->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->id, 'name' => $a->name, 'date' => $a->appointment_date?->toDateString(), 'time' => substr((string) $a->start_time, 0, 5),
                'counsellor' => $a->counsellor ? ['name' => $a->counsellor->name, 'photo' => $this->seo->assetUrl($a->counsellor->photo)] : null,
                'status' => $a->status?->value, 'status_label' => $a->status?->label(),
            ]);
    }

    private function recentApplications(): Collection
    {
        return Application::query()->with(['university:id,name', 'course:id,name,degree_type'])->latest()->limit(5)->get()
            ->map(fn (Application $a) => [
                'id' => $a->id, 'name' => $a->name, 'university' => $a->university?->name, 'course' => $a->course?->degree_type ?: $a->course?->name,
                'status' => $a->status?->value, 'status_label' => $a->status?->label(),
            ]);
    }

    /** Latest events across modules, newest first. */
    private function activity(callable $can, \Closure $appointments): array
    {
        $events = collect();
        if ($can('applications.manage')) {
            Application::query()->latest()->limit(5)->get(['id', 'name', 'created_at'])
                ->each(fn ($a) => $events->push(['type' => 'application', 'text' => "New application received from {$a->name}", 'at' => $a->created_at, 'url' => "/admin/applications/{$a->id}"]));
        }
        if ($can('appointments.view')) {
            $appointments()->whereNotNull('confirmed_at')->latest('confirmed_at')->limit(5)->get(['id', 'name', 'confirmed_at'])
                ->each(fn ($a) => $events->push(['type' => 'appointment', 'text' => "Appointment confirmed for {$a->name}", 'at' => $a->confirmed_at, 'url' => "/admin/appointments/{$a->id}"]));
            $appointments()->latest()->limit(5)->get(['id', 'name', 'created_at'])
                ->each(fn ($a) => $events->push(['type' => 'appointment', 'text' => "New appointment booked by {$a->name}", 'at' => $a->created_at, 'url' => "/admin/appointments/{$a->id}"]));
        }
        if ($can('leads.manage')) {
            Lead::query()->latest()->limit(5)->get(['id', 'name', 'email', 'created_at'])
                ->each(fn ($l) => $events->push(['type' => 'enquiry', 'text' => 'New enquiry from '.($l->name ?: $l->email), 'at' => $l->created_at, 'url' => "/admin/leads/{$l->id}"]));
        }
        if ($can('contacts.manage')) {
            ContactMessage::query()->latest()->limit(5)->get(['id', 'name', 'created_at'])
                ->each(fn ($m) => $events->push(['type' => 'message', 'text' => "New message from {$m->name}", 'at' => $m->created_at, 'url' => "/admin/messages/{$m->id}"]));
        }
        Blog::query()->published()->latest('published_at')->limit(5)->get(['id', 'title', 'published_at'])
            ->each(fn ($b) => $events->push(['type' => 'blog', 'text' => "Blog post published: {$b->title}", 'at' => $b->published_at, 'url' => "/admin/content/blogs/{$b->id}"]));
        GalleryAlbum::query()->latest()->limit(5)->get(['id', 'title', 'created_at'])
            ->each(fn ($g) => $events->push(['type' => 'gallery', 'text' => "Gallery album added: {$g->title}", 'at' => $g->created_at, 'url' => "/admin/content/gallery-albums/{$g->id}"]));

        return $events->filter(fn ($e) => $e['at'] && $e['at'] <= now())->sortByDesc('at')->take(5)
            ->map(fn ($e) => ['type' => $e['type'], 'text' => $e['text'], 'at' => $e['at']->toIso8601String(), 'url' => $e['url']])->values()->all();
    }

    /** Items waiting for action (header bell). */
    private function notifications(callable $can, \Closure $appointments): array
    {
        $items = array_values(array_filter([
            $can('leads.manage') ? ['label' => 'New enquiries', 'count' => Lead::query()->where('status', LeadStatus::New)->count(), 'url' => '/admin/leads?status=new'] : null,
            $can('contacts.manage') ? ['label' => 'Unread messages', 'count' => ContactMessage::query()->where('status', ContactStatus::New)->count(), 'url' => '/admin/messages?status=new'] : null,
            $can('appointments.view') ? ['label' => 'Appointments to confirm', 'count' => $appointments()->where('status', AppointmentStatus::Pending)->count(), 'url' => '/admin/appointments?status=pending'] : null,
            $can('applications.manage') ? ['label' => 'New applications', 'count' => Application::query()->where('status', ApplicationStatus::New)->count(), 'url' => '/admin/applications?status=new'] : null,
        ]));

        return ['count' => array_sum(array_column($items, 'count')), 'items' => $items];
    }
}
