<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($recipients, public $message) {}

    public function handle(): void
    {
        if (config('services.sms.enable')) {
            Http::timeout(10)
                ->connectTimeout(5)
                ->retry([100, 500, 1000])
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.config('services.sms.token'),
                ])->post(config('services.sms.url'), [
                    'sender' => config('services.sms.sender'),
                    'recipients' => Arr::wrap($this->recipients),
                    'body' => $this->message,
                ])->throw();
        }
    }
}
