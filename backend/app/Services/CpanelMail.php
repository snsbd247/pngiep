<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Staff mailboxes on the site's cPanel account, through cPanel UAPI with an API token
 * (Authorization: cpanel USER:TOKEN). Only addresses on the configured mail domain are
 * managed; everything else is treated as an external address.
 */
class CpanelMail
{
    public const UNCONFIGURED = 'unconfigured';

    public const EXTERNAL = 'external';

    public const AVAILABLE = 'available';

    public const EXISTS = 'exists';

    public function enabled(): bool
    {
        $c = config('services.cpanel');

        return filled($c['url']) && filled($c['user']) && filled($c['token']) && filled($c['mail_domain']);
    }

    public function domain(): ?string
    {
        return ($d = config('services.cpanel.mail_domain')) ? Str::lower($d) : null;
    }

    /** True when the address is on the managed mail domain. */
    public function manages(string $email): bool
    {
        return $this->domain() !== null && Str::lower(Str::after($email, '@')) === $this->domain();
    }

    /** One of the class constants: what saving a user with this address will do. */
    public function status(string $email): string
    {
        if (! $this->enabled()) {
            return self::UNCONFIGURED;
        }
        if (! $this->manages($email)) {
            return self::EXTERNAL;
        }

        return $this->exists($email) ? self::EXISTS : self::AVAILABLE;
    }

    public function exists(string $email): bool
    {
        $email = Str::lower($email);
        $pops = $this->call('Email', 'list_pops', ['regex' => '^'.preg_quote(Str::before($email, '@'), null).'@']);

        return collect($pops)->contains(fn ($p) => Str::lower($p['email'] ?? '') === $email);
    }

    public function create(string $email, string $password): void
    {
        $this->call('Email', 'add_pop', [
            'email' => Str::before($email, '@'),
            'domain' => $this->domain(),
            'password' => $password,
            'quota' => config('services.cpanel.quota_mb'),
            'send_welcome_email' => 0,
        ]);
    }

    public function delete(string $email): void
    {
        $this->call('Email', 'delete_pop', ['email' => Str::before($email, '@'), 'domain' => $this->domain()]);
    }

    /**
     * One-time Webmail login for the mailbox (no mailbox password needed).
     *
     * @return array{url: string, session: string}
     */
    public function webmailSession(string $email): array
    {
        $data = $this->call('Session', 'create_webmail_session_for_mail_user', [
            'login' => Str::before($email, '@'),
            'domain' => $this->domain(),
        ]);

        if (empty($data['session']) || empty($data['token'])) {
            throw new RuntimeException('cPanel did not return a Webmail session.');
        }

        $base = rtrim(config('services.cpanel.webmail_url') ?: Str::replaceLast(':2083', ':2096', config('services.cpanel.url')), '/');

        return ['url' => $base.'/'.ltrim($data['token'], '/').'/login', 'session' => $data['session']];
    }

    /** A password cPanel accepts (letters, digits and symbols). */
    public function generatePassword(): string
    {
        return Str::password(20);
    }

    /** Call a UAPI function; returns its `data` or throws with cPanel's error message. */
    private function call(string $module, string $function, array $params = []): mixed
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Mailbox management is not configured.');
        }

        $c = config('services.cpanel');

        try {
            $response = Http::withHeaders(['Authorization' => "cpanel {$c['user']}:{$c['token']}"])
                ->withOptions(['verify' => $c['verify_ssl']])
                ->timeout($c['timeout'])
                ->acceptJson()
                ->get(rtrim($c['url'], '/')."/execute/{$module}/{$function}", $params);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not reach the mail server (cPanel).');
        }

        $body = $response->json();

        if (! $response->successful() || ! is_array($body) || (int) ($body['status'] ?? 0) !== 1) {
            $error = is_array($body) ? implode(' ', (array) ($body['errors'] ?? [])) : '';

            throw new RuntimeException($error !== '' ? $error : "cPanel request failed (HTTP {$response->status()}).");
        }

        return $body['data'] ?? null;
    }
}
