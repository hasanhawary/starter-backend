<?php

namespace Modules\Notification\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for notification emails.
 *
 * Renders the shared notification email template with the resolved
 * (per-language) subject and body, plus an optional call-to-action button.
 *
 * Deliberately not a ShouldQueue mailable: EmailChannel only ever runs inside
 * SendNotificationJob, so the work is off the request thread already. Queueing
 * again would push the real send onto a second queue and let the channel log a
 * message as "sent" while it had only been enqueued.
 */
class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $body,
        public string $lang = 'ar',
        public ?string $actionUrl = null,
        public ?string $actionText = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notification::emails.notification',
            with: [
                'title' => $this->subjectLine,
                'body' => $this->body,
                'lang' => $this->lang,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                // Resolved at render time (not serialized into the queue payload)
                // so notification emails share the same brand theme as BasicMail.
                'brand' => brandSettings($this->lang),
            ],
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
