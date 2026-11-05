<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** In-app admin notification (header bell + Notifications page). */
class AdminAlert extends Notification
{
    use Queueable;

    public function __construct(public string $type, public string $title, public ?string $body = null, public ?string $url = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => $this->type, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
