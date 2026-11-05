<?php

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends emails whose subject and body are edited in Admin > Email Templates.
 * Placeholders like {{name}} are replaced with HTML-escaped values. Mail is
 * queued (database queue drained by cron on shared hosting) and failures never
 * break the visitor's request.
 */
class TemplateMailer
{
    public function __construct(private readonly SettingService $settings) {}

    public function send(string $key, ?string $to, array $vars = []): bool
    {
        if (blank($to)) {
            return false;
        }

        $template = EmailTemplate::query()->where('key', $key)->where('is_active', true)->first();
        if (! $template) {
            return false;
        }

        $vars += [
            'site_name' => $this->settings->get('site_name', config('app.name')),
            'brand_color' => $this->brandColor(),
            'site_url' => rtrim(config('app.frontend_url'), '/'),
            'contact_phone' => $this->settings->get('contact_phone', ''),
            'contact_email' => $this->settings->get('contact_email', ''),
        ];

        try {
            Mail::to($to)->queue(new TemplatedMail(
                subjectLine: $this->render($template->subject, $vars, html: false),
                bodyHtml: $this->render($template->body, $vars, html: true),
                // Office notifications: reply straight to the student. Student emails: reply to the office.
                replyToAddress: str_starts_with($key, 'admin_') ? ($vars['email'] ?? null) : ($vars['contact_email'] ?: null),
            ));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Notify the office (Settings > Email > Admin notification email, falling back to the contact email). */
    public function toAdmin(string $key, array $vars = []): bool
    {
        return $this->send($key, $this->settings->get('admin_notification_email') ?: $this->settings->get('contact_email'), $vars);
    }

    public function render(string $text, array $vars, bool $html): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', function ($m) use ($vars, $html) {
            $value = (string) ($vars[$m[1]] ?? '');

            return $html ? nl2br(e($value)) : $value;
        }, $text);
    }

    /** Primary colour for email buttons; anything but a hex colour falls back to the default. */
    private function brandColor(): string
    {
        $color = (string) $this->settings->get('color_primary', '#1A3FA6');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#1A3FA6';
    }
}
