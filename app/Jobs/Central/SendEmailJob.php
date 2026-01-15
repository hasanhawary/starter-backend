<?php

namespace App\Jobs\Central;

use App\Services\Global\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected NotificationService $notificationService;

    public function __construct(public $users, public array $data, public ?array $types = [])
    {
        $this->notificationService = new NotificationService();
    }

    public function handle(): void
    {
        // Send email notification to each user in the array
        collect($this->users)->each(fn($user) =>  $this->notificationService->resolve($user, $this->data, $this->types));
    }
}
