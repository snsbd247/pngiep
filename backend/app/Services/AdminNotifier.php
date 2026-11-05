<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends in-app notifications to the staff allowed to act on them, respecting each user's
 * Notification Settings. Never breaks the request that triggered it.
 */
class AdminNotifier
{
    /** type => [label, permission that receives it] */
    public const TYPES = [
        'enquiry' => ['New enquiries', 'leads.manage'],
        'appointment' => ['Appointments', 'appointments.manage'],
        'application' => ['Applications', 'applications.manage'],
        'message' => ['Contact messages', 'contacts.manage'],
        'story' => ['Success story submissions', 'success_stories.manage'],
    ];

    public function notify(string $type, string $title, ?string $body = null, ?string $url = null, ?Collection $extraRecipients = null): void
    {
        try {
            $permission = self::TYPES[$type][1];
            $users = User::query()->where('is_active', true)->with('role.permissions')->get()
                ->filter(fn (User $u) => $u->can($permission))
                ->merge($extraRecipients ?? collect())
                ->unique('id')
                ->filter(fn (User $u) => $u->is_active && ($u->notification_preferences[$type] ?? true));

            if ($users->isNotEmpty()) {
                Notification::send($users, new AdminAlert($type, $title, $body, $url));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Appointment events also reach the counsellor's own login, even without appointments.manage. */
    public function appointment(Appointment $appointment, string $title, ?string $body = null): void
    {
        $counsellorUser = $appointment->counsellor?->user;
        $this->notify('appointment', $title, $body, "/admin/appointments/{$appointment->id}", $counsellorUser ? collect([$counsellorUser]) : null);
    }
}
