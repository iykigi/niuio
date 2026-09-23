<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A single, generic in-dashboard + optional-email notification shape
 * used for every hosting event (SSL installed, domain connected, backup
 * completed, storage warnings, deployment failures, maintenance
 * announcements). Kept generic on purpose: the dashboard's notification
 * bell renders every one of these identically off `title`/`body`/`url`,
 * so adding a new event type never requires a new Blade partial.
 */
class HostingActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $level = 'info', // info | success | warning | danger
        public ?string $url = null,
        public bool $alsoEmail = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->alsoEmail ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'level' => $this->level,
            'url' => $this->url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->when($this->url, fn ($mail) => $mail->action('View in Portway', $this->url));
    }
}
