<?php
namespace App\Mail;

use App\Models\Central\Admin;
use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BasicMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User|Admin | null $user, public array $data)
    {
    }

    public function build(): self
    {
        $subject = parseKeyValueString($this->data['title']);

        if (isset($this->data['otp']) && $this->data['otp']) {
            $subject = parseKeyValueString($this->data['title'], 'passwords');
        }

        return $this->view('emails.basic_mail')
            ->with([
                'user_name' => $this->user?->name ?? '',
                'data'      => $this->data ?? []
            ])->subject($subject);

    }

    public function attachments(): array
    {
        return [];
    }
}
