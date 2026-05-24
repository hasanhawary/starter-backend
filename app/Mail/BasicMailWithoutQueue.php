<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BasicMailWithoutQueue extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ?Authenticatable $user, public array $data) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function build(): self
    {
        $subject = transWithParams($this->data['title']);

        return $this->view('emails.basic_mail')
            ->with([
                'data' => $this->data ?? [],
                'brand' => brandSettings(),
            ])->subject($subject);
    }

    public function attachments(): array
    {
        return [];
    }
}
