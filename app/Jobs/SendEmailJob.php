<?php

namespace App\Jobs;

use App\Services\Global\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $users,
        public array $data,
        public ?array $types = [],
        protected NotificationService $notificationService = new NotificationService
    ) {}

    public function handle(): void
    {
        collect($this->users)->each(fn ($user) => $this->notificationService->resolve($user, $this->data, $this->types));
    }
}
