<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Mail\TemplatedMail;
use App\Models\Appointment;
use App\Models\Counsellor;
use App\Models\Lead;
use App\Models\Service;
use Carbon\Carbon;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Counsellor $counsellor;

    private Service $service;

    /** Monday, a working day well inside the booking window. */
    private string $day = '2026-10-12';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();

        // Wednesday 12:00 in Asia/Dubai.
        $this->travelTo(Carbon::parse('2026-10-07 08:00:00', 'UTC'));

        $this->service = Service::query()->create(['title' => 'Career Counselling', 'slug' => 'career', 'status' => 'published', 'is_bookable' => true]);
        $this->counsellor = $this->makeCounsellor('Ayesha Khan');
    }

    private function makeCounsellor(string $name, array $attrs = []): Counsellor
    {
        $c = Counsellor::query()->create($attrs + ['name' => $name, 'status' => 'published', 'slot_duration' => 30]);
        $c->services()->sync([$this->service->id]);
        foreach ([1, 2, 3, 4, 5] as $day) {
            $c->availabilities()->create(['day_of_week' => $day, 'start_time' => '10:00', 'end_time' => '12:00']);
        }

        return $c;
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'service_id' => $this->service->id, 'counsellor_id' => $this->counsellor->id,
            'date' => $this->day, 'time' => '10:30', 'name' => 'Test Student', 'email' => 'student@example.com',
            'phone' => '+971 50 123 4567', 'mode' => 'online', 'agree' => true,
        ];
    }

    private function slots(string $date, ?Counsellor $c = null): array
    {
        return collect($this->getJson('/api/v1/appointments/slots?counsellor='.($c ?? $this->counsellor)->id."&date={$date}")
            ->assertOk()->json('data'))->pluck('start')->all();
    }

    public function test_slots_follow_availability_working_days_and_time_off(): void
    {
        $this->assertSame(['10:00', '10:30', '11:00', '11:30'], $this->slots($this->day));
        $this->assertSame([], $this->slots('2026-10-10'), 'Saturday is not a working day');
        $this->assertSame([], $this->slots('2026-10-07'), 'Inside the minimum notice period');
        $this->assertSame([], $this->slots('2026-12-20'), 'Beyond the advance booking window');

        $this->counsellor->timeOffs()->create(['date' => '2026-10-13', 'start_time' => '10:00', 'end_time' => '11:00']);
        $this->counsellor->timeOffs()->create(['date' => '2026-10-14']);
        $this->assertSame(['11:00', '11:30'], $this->slots('2026-10-13'));
        $this->assertSame([], $this->slots('2026-10-14'));

        $days = $this->getJson('/api/v1/appointments/availability?counsellor='.$this->counsellor->id.'&month=2026-10')->assertOk()->json('data');
        $this->assertArrayHasKey($this->day, $days);
        $this->assertArrayNotHasKey('2026-10-14', $days);
        $this->assertArrayNotHasKey('2026-10-10', $days);
    }

    public function test_booking_creates_lead_appointment_and_emails(): void
    {
        $res = $this->postJson('/api/v1/appointments', $this->payload())->assertCreated()
            ->assertJsonPath('data.status', 'pending')->assertJsonPath('data.date', $this->day);
        $this->assertSame(64, strlen($res->json('data.manage_token')));

        $appt = Appointment::query()->sole();
        $this->assertSame('10:30', substr($appt->start_time, 0, 5));
        $this->assertNotNull($appt->lead_id);
        $this->assertSame(1, Lead::query()->count());
        Mail::assertQueued(TemplatedMail::class, 2); // student + office

        $days = $this->getJson('/api/v1/appointments/availability?counsellor='.$this->counsellor->id.'&month=2026-10')->json('data');
        $this->assertSame(3, $days[$this->day], 'Booked slot is excluded from the month view');

        $this->assertNotContains('10:30', $this->slots($this->day));
    }

    public function test_double_booking_is_rejected_and_db_unique_index_backs_it(): void
    {
        $this->postJson('/api/v1/appointments', $this->payload())->assertCreated();
        $this->postJson('/api/v1/appointments', $this->payload(['email' => 'other@example.com']))
            ->assertUnprocessable()->assertJsonValidationErrors('time');

        // Simulates a race that slipped past the application check.
        $this->expectException(QueryException::class);
        Appointment::query()->create([
            'counsellor_id' => $this->counsellor->id, 'appointment_date' => $this->day, 'start_time' => '10:30:00', 'end_time' => '11:00:00',
            'duration_minutes' => 30, 'name' => 'Racer', 'email' => 'r@example.com', 'phone' => '123456', 'mode' => 'online',
            'status' => AppointmentStatus::Pending,
        ]);
    }

    public function test_invalid_slots_counsellors_and_bots_are_rejected(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->postJson('/api/v1/appointments', $this->payload(['time' => '10:15']))->assertJsonValidationErrors('time');
        $this->postJson('/api/v1/appointments', $this->payload(['date' => '2026-10-10']))->assertJsonValidationErrors('time');
        $this->postJson('/api/v1/appointments', $this->payload(['agree' => false]))->assertJsonValidationErrors('agree');
        $this->postJson('/api/v1/appointments', $this->payload(['website' => 'spam']))->assertJsonValidationErrors('website');

        $other = Service::query()->create(['title' => 'Visa', 'slug' => 'visa', 'status' => 'published', 'is_bookable' => true]);
        $this->postJson('/api/v1/appointments', $this->payload(['service_id' => $other->id]))->assertJsonValidationErrors('counsellor_id');

        $hidden = $this->makeCounsellor('Hidden', ['status' => 'draft']);
        $this->postJson('/api/v1/appointments', $this->payload(['counsellor_id' => $hidden->id]))->assertNotFound();

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_student_can_view_reschedule_and_cancel_with_token(): void
    {
        $token = $this->postJson('/api/v1/appointments', $this->payload())->json('data.manage_token');

        $this->getJson("/api/v1/appointments/manage/{$token}")->assertOk()->assertJsonPath('data.can_change', true)
            ->assertJsonPath('data.email', 's***@example.com'); // link may be forwarded
        $this->getJson('/api/v1/appointments/manage/'.str_repeat('x', 64))->assertNotFound();

        $this->postJson("/api/v1/appointments/manage/{$token}/reschedule", ['date' => '2026-10-13', 'time' => '11:00'])
            ->assertOk()->assertJsonPath('data.date', '2026-10-13');
        $this->assertSame(AppointmentStatus::Rescheduled, Appointment::query()->orderBy('id')->first()->status);
        $this->assertContains('10:30', $this->slots($this->day), 'Old slot is released');

        // The original token follows the chain to the new appointment.
        $this->postJson("/api/v1/appointments/manage/{$token}/cancel", ['reason' => 'Plans changed'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertContains('11:00', $this->slots('2026-10-13'));
    }

    public function test_student_cannot_change_inside_cancellation_notice(): void
    {
        $token = $this->postJson('/api/v1/appointments', $this->payload(['date' => '2026-10-08', 'time' => '10:00']))->json('data.manage_token');
        $this->travelTo(Carbon::parse('2026-10-07 20:00:00', 'UTC')); // 4h before (Port Moresby time)

        $this->getJson("/api/v1/appointments/manage/{$token}")->assertJsonPath('data.can_change', false);
        $this->postJson("/api/v1/appointments/manage/{$token}/cancel")->assertUnprocessable();
        $this->assertSame(AppointmentStatus::Pending, Appointment::query()->sole()->status);
    }

    public function test_counsellor_only_sees_and_manages_own_appointments(): void
    {
        $user = $this->userWithRole('counsellor');
        $this->counsellor->update(['user_id' => $user->id]);
        $other = $this->makeCounsellor('Sara Khan');

        $this->postJson('/api/v1/appointments', $this->payload())->assertCreated();
        $this->postJson('/api/v1/appointments', $this->payload(['counsellor_id' => $other->id, 'email' => 'b@example.com']))->assertCreated();
        [$mine, $theirs] = Appointment::query()->orderBy('id')->get();

        $this->actingAs($user)->getJson('/api/v1/admin/appointments?view=all')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
        $this->actingAs($user)->getJson("/api/v1/admin/appointments/{$theirs->id}")->assertNotFound();
        $this->actingAs($user)->putJson("/api/v1/admin/appointments/{$theirs->id}/status", ['status' => 'confirmed'])->assertNotFound();
        $this->actingAs($user)->putJson("/api/v1/admin/appointments/{$mine->id}/status", ['status' => 'confirmed'])->assertOk();
        $this->assertSame(AppointmentStatus::Confirmed, $mine->fresh()->status);

        // Counsellors cannot reach the rest of the CRM.
        foreach (['leads', 'applications', 'messages', 'counsellors', 'newsletter', 'email-templates'] as $path) {
            $this->actingAs($user)->getJson("/api/v1/admin/{$path}")->assertForbidden();
        }

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->getJson('/api/v1/admin/appointments?view=all')->assertJsonCount(2, 'data');
    }

    public function test_admin_status_rules_and_reschedule(): void
    {
        $admin = $this->userWithRole('admin');
        $this->postJson('/api/v1/appointments', $this->payload())->assertCreated();
        $appt = Appointment::query()->sole();

        $this->actingAs($admin)->putJson("/api/v1/admin/appointments/{$appt->id}/status", ['status' => 'cancelled'])->assertUnprocessable();
        $this->actingAs($admin)->postJson("/api/v1/admin/appointments/{$appt->id}/notes", ['body' => 'Called the student.'])->assertSuccessful();

        $this->actingAs($admin)->postJson("/api/v1/admin/appointments/{$appt->id}/reschedule", ['date' => '2026-10-13', 'time' => '10:00'])->assertOk();
        $this->assertSame(2, Appointment::query()->count());
        $this->actingAs($admin)->postJson("/api/v1/admin/appointments/{$appt->id}/cancel")->assertUnprocessable();
    }
}
