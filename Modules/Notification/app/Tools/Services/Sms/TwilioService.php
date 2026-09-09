<?php

namespace Modules\Notification\Tools\Services\Sms;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Notification\app\Tools\Contracts\SmsServiceInterface;

class TwilioService implements SmsServiceInterface
{
    protected string $sid;

    protected string $token;

    protected string $messagingServiceSid;

    protected string $baseUrl;

    public function __construct()
    {
        $this->sid = config('notification.twilio.sid');
        $this->token = config('notification.twilio.token');
        $this->messagingServiceSid = config('notification.twilio.messaging_service_sid');
        $this->baseUrl = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
    }

    /**
     * @throws ConnectionException
     */
    public function send(string $to, string $message): bool
    {
        $response = Http::withBasicAuth($this->sid, $this->token)
            ->asForm()
            ->post($this->baseUrl, [
                'To' => $to,
                'MessagingServiceSid' => $this->messagingServiceSid,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Twilio error: '.($response->json('message') ?? $response->body())
            );
        }

        return true;
    }
}
