<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExceptionOccurred extends Mailable
{
    use Queueable, SerializesModels;

    public Exception $exception;

    public function __construct($exception)
    {
        $this->exception = $exception;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Exception Occurred');
    }

    public function build(): static
    {
        return $this->from(config('mail.from.address'))
            ->subject('Exception Occurred')
            ->view('emails.exception')
            ->with(['exception' => $this->exception]);
    }
}
