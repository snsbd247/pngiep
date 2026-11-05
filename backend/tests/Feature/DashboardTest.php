<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Appointment;
use App\Models\Counsellor;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        // Mid-April 2026 in Port Moresby.
        $this->travelTo(Carbon::parse('2026-04-15 02:00:00', 'UTC'));
    }

    private function lead(string $source, string $createdAt, array $attrs = []): Lead
    {
        $lead = Lead::query()->create($attrs + ['name' => 'Lead '.uniqid(), 'email' => uniqid().'@example.com', 'source' => $source, 'status' => 'new']);
        $lead->forceFill(['created_at' => $createdAt])->save();

        return $lead;
    }

    public function test_kpis_compare_with_the_previous_period_and_group_sources(): void
    {
        // April (current month): 4 leads; March: 2 leads.
        $this->lead('contact_form', '2026-04-02 10:00:00');
        $this->lead('contact_form', '2026-04-05 10:00:00');
        $this->lead('university_enquiry', '2026-04-08 10:00:00');
        $this->lead('newsletter', '2026-04-10 10:00:00');
        $this->lead('appointment', '2026-03-10 10:00:00');
        $this->lead('appointment', '2026-03-20 10:00:00');

        $res = $this->actingAs($this->userWithRole('admin'))->getJson('/api/v1/admin/dashboard')->assertOk();

        $res->assertJsonPath('data.period.from', '2026-04-01')->assertJsonPath('data.period.to', '2026-04-30')
            ->assertJsonPath('data.period.compare_label', 'vs last month')
            ->assertJsonPath('data.cards.0.key', 'enquiries')->assertJsonPath('data.cards.0.value', 4)
            ->assertJsonPath('data.cards.0.previous', 2);
        $this->assertEquals(100, $res->json('data.cards.0.change'));

        $sources = collect($res->json('data.sources.items'))->pluck('count', 'label');
        $this->assertSame(4, $res->json('data.sources.total'));
        $this->assertSame(2, $sources['Contact Form']);
        $this->assertSame(1, $sources['University Enquiry']);
        $this->assertSame(1, $sources['Website/Other']);

        $chart = collect($res->json('data.chart.points'));
        $this->assertSame('day', $res->json('data.chart.unit'));
        $this->assertCount(15, $chart, 'The current month is charted up to today (15 April)');
        $this->assertSame(1, $chart->firstWhere('date', '2026-04-02')['enquiries']);
        $this->assertCount(5, $res->json('data.recent_enquiries'));
        // Newest first; the type comes from the lead source (contact form without a message → "Contact Form").
        $this->assertSame(['Newsletter', 'University Enquiry', 'Contact Form', 'Contact Form', 'Appointment'], collect($res->json('data.recent_enquiries'))->pluck('type')->all());

        // Custom range
        $march = $this->getJson('/api/v1/admin/dashboard?from=2026-03-01&to=2026-03-31')->assertJsonPath('data.cards.0.value', 2);
        $this->assertCount(31, $march->json('data.chart.points'));
        $this->getJson('/api/v1/admin/dashboard?from=2026-04-10&to=2026-04-01')->assertUnprocessable();
    }

    public function test_counsellor_sees_only_own_appointments_and_no_crm_data(): void
    {
        $user = $this->userWithRole('counsellor');
        $mine = Counsellor::query()->create(['name' => 'Mine', 'status' => 'published', 'user_id' => $user->id]);
        $other = Counsellor::query()->create(['name' => 'Other', 'status' => 'published']);
        foreach ([$mine, $other] as $i => $c) {
            Appointment::query()->create([
                'counsellor_id' => $c->id, 'appointment_date' => '2026-04-20', 'start_time' => '10:00:00', 'end_time' => '10:30:00',
                'duration_minutes' => 30, 'name' => "Student {$i}", 'email' => "s{$i}@example.com", 'phone' => '123456', 'mode' => 'online', 'status' => 'pending',
            ]);
        }

        $res = $this->actingAs($user)->getJson('/api/v1/admin/dashboard')->assertOk()
            ->assertJsonPath('data.recent_enquiries', null)->assertJsonPath('data.sources', null);
        $this->assertSame(['appointments', 'success_stories'], collect($res->json('data.cards'))->pluck('key')->all());
        $this->assertSame(1, $res->json('data.cards.0.value'));
        $this->assertSame(['Student 0'], collect($res->json('data.upcoming_appointments'))->pluck('name')->all());
        $this->assertSame(1, $res->json('data.notifications.count'));
    }

    public function test_report_csv_and_search(): void
    {
        $admin = $this->userWithRole('admin');
        $this->lead('contact_form', '2026-04-03 09:00:00', ['name' => '=HYPERLINK("x")']);
        Application::query()->create(['reference' => 'APP-ABCDEFGH', 'name' => 'Grace Kila', 'email' => 'grace@example.com', 'phone' => '123']);

        $csv = $this->actingAs($admin)->get('/api/v1/admin/dashboard/report?from=2026-04-01&to=2026-04-30')->assertOk()->streamedContent();
        $this->assertStringContainsString('ENQUIRIES', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('APP-ABCDEFGH', $csv);

        $this->actingAs($admin)->getJson('/api/v1/admin/search?q=grace')->assertOk()
            ->assertJsonPath('data.0.label', 'Applications')->assertJsonPath('data.0.items.0.url', '/admin/applications/1');
        $this->actingAs($admin)->getJson('/api/v1/admin/search?q=g')->assertUnprocessable();
        // A counsellor's search never returns CRM data.
        $this->actingAs($this->userWithRole('counsellor'))->getJson('/api/v1/admin/search?q=grace')->assertOk()->assertJsonCount(0, 'data');
    }
}
