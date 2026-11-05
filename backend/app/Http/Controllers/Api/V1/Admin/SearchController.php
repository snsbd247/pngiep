<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Appointment;
use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Page;
use App\Models\University;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Header "Search anything": people, bookings and content the user is allowed to see. */
class SearchController extends Controller
{
    private const LIMIT = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q']);
        $like = '%'.addcslashes($q, '%_\\').'%';
        $user = $request->user();
        $groups = [];

        $add = function (string $label, $rows) use (&$groups) {
            if ($rows->isNotEmpty()) {
                $groups[] = ['label' => $label, 'items' => $rows->values()->all()];
            }
        };

        if ($user->can('leads.manage')) {
            $add('Leads', Lead::query()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like))
                ->latest()->limit(self::LIMIT)->get(['id', 'name', 'email'])
                ->map(fn ($l) => ['title' => $l->name ?: $l->email, 'subtitle' => $l->email, 'url' => "/admin/leads/{$l->id}"]));
        }
        if ($user->can('appointments.view')) {
            $add('Appointments', Appointment::query()
                ->when(! $user->can('appointments.manage'), fn ($q) => $q->whereHas('counsellor', fn ($c) => $c->where('user_id', $user->id)))
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('reference', 'like', $like))
                ->latest('appointment_date')->limit(self::LIMIT)->get(['id', 'name', 'reference', 'appointment_date'])
                ->map(fn ($a) => ['title' => $a->name, 'subtitle' => "{$a->reference} · {$a->appointment_date?->toDateString()}", 'url' => "/admin/appointments/{$a->id}"]));
        }
        if ($user->can('applications.manage')) {
            $add('Applications', Application::query()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('reference', 'like', $like))
                ->latest()->limit(self::LIMIT)->get(['id', 'name', 'reference'])
                ->map(fn ($a) => ['title' => $a->name, 'subtitle' => $a->reference, 'url' => "/admin/applications/{$a->id}"]));
        }
        if ($user->can('contacts.manage')) {
            $add('Messages', ContactMessage::query()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('subject', 'like', $like))
                ->latest()->limit(self::LIMIT)->get(['id', 'name', 'subject', 'inquiry_type'])
                ->map(fn ($m) => ['title' => $m->name, 'subtitle' => $m->subject ?: $m->inquiry_type, 'url' => "/admin/messages/{$m->id}"]));
        }

        $content = [
            ['Destinations', 'destinations.manage', Destination::class, 'name', 'destinations'],
            ['Universities', 'universities.manage', University::class, 'name', 'universities'],
            ['Courses', 'courses.manage', Course::class, 'name', 'courses'],
            ['Blog / Notice', 'blogs.manage', Blog::class, 'title', 'blogs'],
            ['Pages', 'pages.manage', Page::class, 'title', 'pages'],
        ];
        foreach ($content as [$label, $permission, $model, $column, $resource]) {
            if ($user->can($permission)) {
                $add($label, $model::query()->where($column, 'like', $like)->orderBy($column)->limit(self::LIMIT)->get(['id', $column, 'status'])
                    ->map(fn ($r) => ['title' => str_replace('*', '', (string) $r->{$column}), 'subtitle' => ucfirst((string) ($r->status?->value ?? $r->status)), 'url' => "/admin/content/{$resource}/{$r->id}"]));
            }
        }

        return ApiResponse::success($groups);
    }
}
