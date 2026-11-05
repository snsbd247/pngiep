<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Counsellor;
use App\Models\Course;
use App\Models\Destination;
use App\Models\University;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the schema decisions later phases depend on.
 */
class SchemaTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(Counsellor $counsellor, array $overrides = []): Appointment
    {
        return Appointment::query()->create($overrides + [
            'counsellor_id' => $counsellor->id,
            'appointment_date' => '2030-01-07',
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'duration_minutes' => 30,
            'name' => 'Student',
            'email' => 'student@example.com',
            'phone' => '0123',
        ]);
    }

    public function test_database_prevents_double_booking_the_same_slot(): void
    {
        $counsellor = Counsellor::query()->create(['name' => 'Jane Doe']);
        $this->appointment($counsellor);

        $this->expectException(QueryException::class);
        $this->appointment($counsellor, ['email' => 'other@example.com']);
    }

    public function test_cancelled_appointments_free_their_slot(): void
    {
        $counsellor = Counsellor::query()->create(['name' => 'Jane Doe']);
        $first = $this->appointment($counsellor);
        $first->update(['status' => AppointmentStatus::Cancelled]);

        $second = $this->appointment($counsellor, ['email' => 'other@example.com']);

        $this->assertNull($first->fresh()->slot_lock);
        $this->assertSame(1, $second->fresh()->slot_lock);
        $this->assertStringStartsWith('APT-', $second->reference);
    }

    public function test_slugs_are_generated_and_unique(): void
    {
        $a = Destination::query()->create(['name' => 'United Kingdom']);
        $b = Destination::query()->create(['name' => 'United Kingdom']);

        $this->assertSame('united-kingdom', $a->slug);
        $this->assertSame('united-kingdom-2', $b->slug);
    }

    public function test_course_inherits_destination_from_university(): void
    {
        $uk = Destination::query()->create(['name' => 'UK']);
        $uni = University::query()->create(['name' => 'Test University', 'destination_id' => $uk->id]);
        $course = Course::query()->create(['name' => 'MSc Data Science', 'university_id' => $uni->id, 'study_level' => 'postgraduate']);

        $this->assertSame($uk->id, $course->destination_id);
        $this->assertSame('msc-data-science', $course->slug);
    }
}
