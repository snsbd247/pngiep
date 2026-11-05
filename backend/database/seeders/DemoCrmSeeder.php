<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\AppointmentStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Application;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Counsellor;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Service;
use App\Models\University;
use App\Services\AppointmentScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * LOCAL PREVIEW ONLY: fictitious enquiries, appointments, applications and messages spread
 * over the last two months, so the admin dashboard and CRM screens can be reviewed with data.
 * DatabaseSeeder runs it only in the local environment.
 */
class DemoCrmSeeder extends Seeder
{
    private const FIRST = ['James', 'Maria', 'Peter', 'Lucy', 'Daniel', 'Thomas', 'John', 'Ruth', 'David', 'Kevin', 'Sera', 'Michael', 'Anna', 'Jacob', 'Grace', 'Joseph', 'Mary', 'Paul', 'Esther', 'Samuel'];

    private const LAST = ['Kila', 'Tom', 'Tau', 'Kone', 'Laka', 'Peter', 'Balo', 'Maino', 'Sape', 'Pala', 'Vera', 'Moi', 'Wari', 'Bage', 'Mone', 'Kaupa', 'Yamb', 'Tomi', 'Kawa', 'Gure'];

    public function run(): void
    {
        if (! app()->environment('local') || Lead::query()->exists()) {
            return;
        }

        mt_srand(2026);
        $today = CarbonImmutable::parse(app(AppointmentScheduler::class)->now()->toDateString());
        $person = fn (int $i) => self::FIRST[$i % 20].' '.self::LAST[($i * 7) % 20];
        $sources = array_merge(array_fill(0, 7, 'contact_form'), array_fill(0, 5, 'appointment'), array_fill(0, 4, 'apply_now'), array_fill(0, 2, 'university_enquiry'), ['course_enquiry', 'course_enquiry', 'destination_enquiry']);
        $statuses = ['new', 'new', 'contacted', 'follow_up', 'interested', 'converted', 'closed'];
        $destinations = Destination::query()->pluck('id')->all();
        $universities = University::query()->with('courses:id,university_id')->get();

        // Enquiries: a few every day over the last 60 days, trending upward.
        $plan = [];
        for ($daysAgo = 59; $daysAgo >= 0; $daysAgo--) {
            $n = 1 + intdiv(60 - $daysAgo, 20) + mt_rand(0, 2);
            for ($k = 0; $k < $n; $k++) {
                $plan[] = $daysAgo;
            }
        }
        foreach ($plan as $i => $daysAgo) {
            $at = $today->subDays($daysAgo)->setTime(mt_rand(8, 17), mt_rand(0, 59));
            if ($at->isFuture()) {
                $at = CarbonImmutable::now()->subMinutes(mt_rand(5, 300));
            }
            $name = $person($i);
            $source = $sources[$i % count($sources)];
            $lead = Lead::query()->create([
                'name' => $name, 'email' => strtolower(str_replace(' ', '.', $name))."{$i}@example.com", 'phone' => '+675 7'.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                'source' => $source, 'status' => $daysAgo < 3 ? LeadStatus::New->value : $statuses[$i % count($statuses)],
                'destination_id' => $destinations ? $destinations[$i % count($destinations)] : null,
            ]);
            $lead->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

            if ($source === LeadSource::ContactForm->value) {
                $msg = ContactMessage::query()->create([
                    'lead_id' => $lead->id, 'name' => $name, 'email' => $lead->email, 'phone' => $lead->phone,
                    'inquiry_type' => ['Study in India', 'Visa Information', 'Scholarship', 'University Admission'][$i % 4],
                    'subject' => 'Enquiry', 'message' => 'I would like more information about studying abroad.', 'status' => $daysAgo < 5 ? 'new' : 'read',
                ]);
                $msg->forceFill(['created_at' => $at])->save();
            }
            if ($source === LeadSource::ApplyNow->value && $universities->isNotEmpty()) {
                $u = $universities[$i % $universities->count()];
                $app = Application::query()->create([
                    'reference' => 'APP-'.strtoupper(substr(md5((string) $i), 0, 8)), 'lead_id' => $lead->id, 'name' => $name, 'email' => $lead->email, 'phone' => $lead->phone,
                    'destination_id' => $u->destination_id, 'university_id' => $u->id, 'course_id' => $u->courses->first()?->id,
                    'status' => [ApplicationStatus::UnderReview, ApplicationStatus::DocumentsPending, ApplicationStatus::Submitted, ApplicationStatus::Processing, ApplicationStatus::Contacted, ApplicationStatus::New][$i % 6]->value,
                ]);
                $app->forceFill(['created_at' => $at])->save();
            }
        }

        // Appointments: past (completed) and upcoming on working days during office hours.
        $counsellors = Counsellor::query()->with('services:id')->get();
        $service = Service::query()->value('id');
        $slot = 0;
        for ($offset = -40; $offset <= 21 && $counsellors->isNotEmpty(); $offset++) {
            $day = $today->addDays($offset);
            if ($day->isWeekend() || mt_rand(0, 2) === 0) {
                continue;
            }
            foreach ([['10:00', '10:30'], ['14:00', '14:30']] as $j => [$start, $end]) {
                $i = $slot++;
                $past = $offset < 0;
                $status = $past ? ($i % 7 === 0 ? AppointmentStatus::NoShow : AppointmentStatus::Completed) : ($i % 3 === 0 ? AppointmentStatus::Pending : AppointmentStatus::Confirmed);
                // Booked a few days before the appointment, and never in the future.
                $created = ($past ? $day : $today)->subDays(mt_rand(1, 10))->setTime(mt_rand(8, 17), mt_rand(0, 59));
                $appt = Appointment::query()->create([
                    'counsellor_id' => $counsellors[($i + $j) % $counsellors->count()]->id, 'service_id' => $service,
                    'appointment_date' => $day->toDateString(), 'start_time' => "{$start}:00", 'end_time' => "{$end}:00", 'duration_minutes' => 30,
                    'name' => $person($i + 50), 'email' => 'student'.$i.'@example.com', 'phone' => '+675 7'.mt_rand(100, 999).' '.mt_rand(1000, 9999),
                    'mode' => ['online', 'in_person', 'phone'][$i % 3], 'status' => $status->value,
                    'confirmed_at' => $status === AppointmentStatus::Pending ? null : $created->addHours(3),
                ]);
                $appt->forceFill(['created_at' => $created])->save();
            }
        }
    }
}
