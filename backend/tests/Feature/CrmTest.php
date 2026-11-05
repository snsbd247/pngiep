<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\ContactMessage;
use App\Models\Counsellor;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\NewsletterSubscriber;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();
    }

    private function contact(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/contact', $overrides + [
            'name' => 'Ann Lee', 'email' => 'ann@example.com', 'phone' => '0501234567', 'inquiry_type' => 'Visa Assistance',
            'message' => 'I need help with my UK student visa.', 'agree' => true,
        ]);
    }

    public function test_contact_form_creates_message_lead_and_emails(): void
    {
        $this->contact()->assertCreated();

        $message = ContactMessage::query()->sole();
        $this->assertSame('Visa Assistance', $message->inquiry_type);
        $this->assertNotNull($message->lead_id);
        Mail::assertQueued(TemplatedMail::class, 2);
        // The sender is a no-reply mailbox: students reply to the office, the office replies to the student.
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $m) => $m->hasTo('ann@example.com') && $m->hasReplyTo(app(\App\Services\SettingService::class)->get('contact_email')));
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $m) => ! $m->hasTo('ann@example.com') && $m->hasReplyTo('ann@example.com'));

        $this->contact(['message' => 'short'])->assertJsonValidationErrors('message');
        $this->contact(['website' => 'http://spam'])->assertJsonValidationErrors('website');
        $this->contact(['inquiry_type' => 'Hacking'])->assertJsonValidationErrors('inquiry_type');
    }

    public function test_open_leads_are_reused_per_email(): void
    {
        $this->contact()->assertCreated();
        $this->contact(['email' => 'ANN@example.com', 'message' => 'Following up on my visa question.'])->assertCreated();
        $this->assertSame(1, Lead::query()->count());
        $this->assertCount(1, Lead::query()->sole()->meta['history'] ?? []);

        Lead::query()->sole()->update(['status' => 'converted']);
        $this->contact()->assertCreated();
        $this->assertSame(2, Lead::query()->count());
    }

    public function test_newsletter_signup_creates_lead_once(): void
    {
        $this->postJson('/api/v1/newsletter', ['email' => 'news@example.com'])->assertOk();
        $this->postJson('/api/v1/newsletter', ['email' => 'news@example.com'])->assertOk();
        $this->assertSame(1, NewsletterSubscriber::query()->count());
        $this->assertSame(1, Lead::query()->where('email', 'news@example.com')->count());
    }

    public function test_admin_manages_leads_and_messages(): void
    {
        $admin = $this->userWithRole('admin');
        $this->contact()->assertCreated();
        $lead = Lead::query()->sole();
        $message = ContactMessage::query()->sole();

        $this->actingAs($admin)->getJson('/api/v1/admin/leads')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($admin)->getJson("/api/v1/admin/leads/{$lead->id}")->assertOk()->assertJsonCount(1, 'data.contact_messages');
        $this->actingAs($admin)->putJson("/api/v1/admin/leads/{$lead->id}", ['status' => 'contacted', 'assigned_to' => $admin->id])->assertOk();
        $this->assertNotNull($lead->fresh()->last_contacted_at);
        $this->actingAs($admin)->postJson("/api/v1/admin/leads/{$lead->id}/notes", ['body' => 'Sent brochure'])->assertSuccessful();

        $this->actingAs($admin)->getJson("/api/v1/admin/messages/{$message->id}")->assertOk();
        $this->assertSame('read', $message->fresh()->status->value);

        Mail::fake();
        $this->actingAs($admin)->postJson("/api/v1/admin/messages/{$message->id}/reply", ['reply' => 'Hi Ann, we can help. <script>x</script>'])->assertOk();
        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $m) => $m->hasTo('ann@example.com') && ! str_contains($m->bodyHtml, '<script>'));
        $this->assertSame('replied', $message->fresh()->status->value);
    }

    public function test_marketing_role_permissions(): void
    {
        $editor = $this->userWithRole('content-manager');
        foreach (['leads', 'applications', 'messages', 'appointments', 'counsellors', 'email-templates'] as $path) {
            $this->actingAs($editor)->getJson("/api/v1/admin/{$path}")->assertForbidden();
        }
    }

    public function test_counsellor_crud_validates_availability(): void
    {
        $admin = $this->userWithRole('admin');
        $base = ['name' => 'New Counsellor', 'status' => 'published', 'slot_duration' => 30];

        $this->actingAs($admin)->postJson('/api/v1/admin/counsellors', $base + ['availabilities' => [
            ['day_of_week' => 0, 'start_time' => '10:00', 'end_time' => '12:00'],
            ['day_of_week' => 0, 'start_time' => '11:00', 'end_time' => '13:00'],
        ]])->assertUnprocessable();
        $this->actingAs($admin)->postJson('/api/v1/admin/counsellors', $base + ['availabilities' => [
            ['day_of_week' => 1, 'start_time' => '12:00', 'end_time' => '10:00'],
        ]])->assertUnprocessable();
        $this->actingAs($admin)->postJson('/api/v1/admin/counsellors', ['slot_duration' => 7] + $base)->assertUnprocessable();

        // Only users with the counsellor role can be linked.
        $this->actingAs($admin)->postJson('/api/v1/admin/counsellors', $base + ['user_id' => $admin->id])->assertJsonValidationErrors('user_id');

        $id = $this->actingAs($admin)->postJson('/api/v1/admin/counsellors', $base + [
            'user_id' => $this->userWithRole('counsellor')->id,
            'availabilities' => [['day_of_week' => 0, 'start_time' => '10:00', 'end_time' => '12:00'], ['day_of_week' => 0, 'start_time' => '13:00', 'end_time' => '15:00']],
            'time_offs' => [['date' => '2026-12-25', 'reason' => 'Holiday']],
        ])->assertCreated()->json('data.id');

        $c = Counsellor::query()->findOrFail($id);
        $this->assertSame(2, $c->availabilities()->count());
        $this->assertSame(1, $c->timeOffs()->count());
    }

    public function test_email_templates_are_edit_only_and_sanitised(): void
    {
        $admin = $this->userWithRole('admin');
        $tpl = EmailTemplate::query()->where('key', 'contact_received')->firstOrFail();

        $this->actingAs($admin)->postJson('/api/v1/admin/email-templates', ['key' => 'x', 'name' => 'x', 'subject' => 'x', 'body' => 'x'])->assertStatus(405);
        $this->actingAs($admin)->deleteJson("/api/v1/admin/email-templates/{$tpl->id}")->assertStatus(405);
        $this->actingAs($admin)->putJson("/api/v1/admin/email-templates/{$tpl->id}", [
            'name' => $tpl->name, 'subject' => 'Thanks {{name}}', 'body' => '<p>Hello {{name}}</p><script>alert(1)</script>', 'is_active' => true,
        ])->assertOk();

        $tpl->refresh();
        $this->assertSame('Thanks {{name}}', $tpl->subject);
        $this->assertStringNotContainsString('<script', $tpl->body);
        $this->assertSame('contact_received', $tpl->key);
    }

    public function test_newsletter_export_escapes_formulas(): void
    {
        NewsletterSubscriber::query()->create(['email' => 'a@example.com', 'name' => '=HYPERLINK("x")', 'status' => 'subscribed']);
        $csv = $this->actingAs($this->userWithRole('admin'))->get('/api/v1/admin/newsletter/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
