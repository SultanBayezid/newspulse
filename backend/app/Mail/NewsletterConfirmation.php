<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class NewsletterConfirmation extends Mailable
{
    use Queueable;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'hello@newspulse.test'),
                config('mail.from.name', 'NewsPulse')
            ),
            subject: 'Confirm your newsletter subscription',
        );
    }

    public function content(): Content
    {
        $confirmUrl = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/newsletter/confirm/' . $this->subscriber->token;

        return new Content(
            markdown: 'mail.newsletter-confirmation',
            with: [
                'confirmUrl' => $confirmUrl,
                'name' => $this->subscriber->name ?: 'there',
            ],
        );
    }
}
