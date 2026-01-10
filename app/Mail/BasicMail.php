<?php

namespace App\Mail;

use App\Models\Central\Admin;
use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BasicMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User|Admin|null $user, public array $data)
    {
    }

    public function build(): self
    {
        $subject = transWithParams($this->data['title']);

        return $this->view('emails.basic_mail')
            ->with([
                'data' => $this->data ?? [],
                'brand' => mailBrand($this->data['brand'] ?? null),
            ])->subject($subject);

    }

    public function attachments(): array
    {
        return [];
    }
}
