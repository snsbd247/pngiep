<?php

namespace App\Mail;

use App\Services\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** An email rendered from an admin-editable template inside the branded layout. */
class TemplatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** $replyTo: where replies go (the sender is a no-reply mailbox). */
    public function __construct(public string $subjectLine, public string $bodyHtml, public ?string $replyToAddress = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: filter_var($this->replyToAddress, FILTER_VALIDATE_EMAIL) ? [$this->replyToAddress] : [],
        );
    }

    public function content(): Content
    {
        $settings = app(SettingService::class);

        return new Content(view: 'emails.layout', with: [
            'body' => $this->bodyHtml,
            'siteName' => $settings->get('site_name', config('app.name')),
            'logo' => app(\App\Services\SeoService::class)->assetUrl($settings->get('site_logo')),
            'primary' => $settings->get('color_primary', '#1A3FA6'),
            'footer' => str_replace('{year}', date('Y'), (string) $settings->get('footer_copyright', '')),
            'address' => $settings->get('contact_address'),
        ]);
    }
}
