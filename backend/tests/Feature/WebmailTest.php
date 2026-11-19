<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebmailTest extends TestCase
{
    use RefreshDatabase;

    /** Mailboxes that "exist" on the fake cPanel. */
    private array $mailboxes = ['existing@pngiep.com'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();

        config(['services.cpanel' => [
            'url' => 'https://cpanel.test:2083', 'user' => 'pngiep', 'token' => 'TESTTOKEN',
            'webmail_url' => 'https://cpanel.test:2096', 'mail_domain' => 'pngiep.com',
            'quota_mb' => 1024, 'verify_ssl' => true, 'timeout' => 5,
        ]]);

        Http::fake(function (Request $request) {
            $this->assertSame('cpanel pngiep:TESTTOKEN', $request->header('Authorization')[0]);
            $path = parse_url($request->url(), PHP_URL_PATH);
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

            return match ($path) {
                '/execute/Email/list_pops' => Http::response(['status' => 1, 'data' => array_map(fn ($e) => ['email' => $e], $this->mailboxes)]),
                '/execute/Email/add_pop' => $q['email'] === 'fail'
                    ? Http::response(['status' => 0, 'errors' => ['Quota exceeded.']])
                    : Http::response(['status' => 1, 'data' => "{$q['email']}+{$q['domain']}"]),
                '/execute/Email/delete_pop' => Http::response(['status' => 1, 'data' => null]),
                '/execute/Session/create_webmail_session_for_mail_user' => Http::response(['status' => 1, 'data' => ['session' => 'SESSION123', 'token' => '/cpsess999']]),
                default => Http::response(['status' => 0, 'errors' => ['unknown']], 404),
            };
        });
    }

    private function payload(string $email, array $extra = []): array
    {
        return $extra + [
            'name' => 'Staff', 'email' => $email, 'role_id' => Role::query()->where('slug', 'counsellor')->value('id'),
            'is_active' => true, 'password' => 'Secret123', 'password_confirmation' => 'Secret123', 'mailbox' => true,
        ];
    }

    public function test_email_status_check(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->getJson('/api/v1/admin/mailboxes/status?email=new@pngiep.com')->assertOk()->assertJsonPath('data.status', 'available');
        $this->actingAs($admin)->getJson('/api/v1/admin/mailboxes/status?email=existing@pngiep.com')->assertJsonPath('data.status', 'exists');
        $this->actingAs($admin)->getJson('/api/v1/admin/mailboxes/status?email=someone@gmail.com')->assertJsonPath('data.status', 'external');
    }

    public function test_new_address_creates_a_mailbox_and_returns_the_password_once(): void
    {
        $res = $this->actingAs($this->userWithRole('admin'))->postJson('/api/v1/admin/users', $this->payload('New.Staff@pngiep.com'))
            ->assertCreated()->assertJsonPath('meta.mailbox.status', 'created');

        $this->assertNotEmpty($res->json('meta.mailbox.password'));
        $user = User::query()->where('email', 'New.Staff@pngiep.com')->firstOrFail();
        $this->assertSame('new.staff@pngiep.com', $user->mailbox);
        $this->assertTrue($user->mailbox_created);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/Email/add_pop') && str_contains($r->url(), 'quota=1024'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'mailbox_created', 'subject_id' => $user->id]);
    }

    public function test_existing_mailbox_is_only_linked(): void
    {
        $this->actingAs($this->userWithRole('admin'))->postJson('/api/v1/admin/users', $this->payload('existing@pngiep.com'))
            ->assertCreated()->assertJsonPath('meta.mailbox.status', 'linked')->assertJsonMissingPath('meta.mailbox.password');

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'add_pop'));
        $this->assertSame('existing@pngiep.com', User::query()->where('email', 'existing@pngiep.com')->value('mailbox'));
    }

    public function test_external_addresses_and_cpanel_errors_do_not_block_saving_the_user(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson('/api/v1/admin/users', $this->payload('someone@gmail.com'))
            ->assertCreated()->assertJsonPath('meta.mailbox.status', 'external');

        $this->actingAs($admin)->postJson('/api/v1/admin/users', $this->payload('fail@pngiep.com'))
            ->assertCreated()->assertJsonPath('meta.mailbox.status', 'error')->assertJsonPath('meta.mailbox.error', 'Quota exceeded.');

        $this->assertNull(User::query()->where('email', 'fail@pngiep.com')->value('mailbox'));
    }

    public function test_deleting_a_user_only_unlinks_and_delete_flag_removes_the_mailbox(): void
    {
        $admin = $this->userWithRole('admin');
        $a = $this->userWithRole('counsellor', ['email' => 'a@pngiep.com']);
        $a->forceFill(['mailbox' => 'a@pngiep.com', 'mailbox_linked_at' => now()])->save();
        $b = $this->userWithRole('counsellor', ['email' => 'b@pngiep.com']);
        $b->forceFill(['mailbox' => 'b@pngiep.com', 'mailbox_linked_at' => now()])->save();

        $this->actingAs($admin)->deleteJson("/api/v1/admin/users/{$a->id}")->assertOk();
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'delete_pop'));
        $this->assertNull($a->fresh()?->mailbox ?? User::withTrashed()->find($a->id)->mailbox);

        $this->actingAs($admin)->deleteJson("/api/v1/admin/users/{$b->id}/mailbox?delete=1")->assertOk();
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'delete_pop') && str_contains($r->url(), 'email=b'));
        $this->assertNull($b->fresh()->mailbox);
    }

    public function test_webmail_opens_only_the_users_own_mailbox(): void
    {
        $staff = $this->userWithRole('counsellor', ['email' => 'staff@pngiep.com']);
        $staff->forceFill(['mailbox' => 'staff@pngiep.com', 'mailbox_linked_at' => now()])->save();

        $this->actingAs($staff)->postJson('/api/v1/admin/webmail/session')->assertOk()
            ->assertJsonPath('data.url', 'https://cpanel.test:2096/cpsess999/login')
            ->assertJsonPath('data.session', 'SESSION123');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'create_webmail_session_for_mail_user') && str_contains($r->url(), 'login=staff'));

        $this->actingAs($this->userWithRole('counsellor'))->postJson('/api/v1/admin/webmail/session')->assertNotFound();
        $this->actingAs($staff)->getJson('/api/v1/admin/mailboxes/status?email=x@pngiep.com')->assertForbidden();
    }

    public function test_feature_is_off_without_configuration(): void
    {
        config(['services.cpanel.token' => null]);

        $this->actingAs($this->userWithRole('admin'))->getJson('/api/v1/admin/mailboxes/status?email=new@pngiep.com')
            ->assertOk()->assertJsonPath('data.status', 'unconfigured');
        Http::assertNothingSent();
    }
}
