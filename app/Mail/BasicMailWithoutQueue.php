<?php

namespace App\Mail;

use App\Models\Central\Admin;
use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BasicMailWithoutQueue extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User|Admin|null $user, public array $data)
    {
    }

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
