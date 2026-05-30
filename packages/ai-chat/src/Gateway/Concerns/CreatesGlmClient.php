<?php

namespace AiChat\Gateway\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Providers\Provider;

trait CreatesGlmClient
{
    protected function client(Provider $provider, ?int $timeout = null): PendingRequest
    {
        return Http::baseUrl($this->baseUrl($provider))
            ->withToken($provider->providerCredentials()['key'])
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout($timeout ?? 60)
            ->throw();
    }

    protected function baseUrl(Provider $provider): string
    {
        return rtrim($provider->additionalConfiguration()['url'] ?? 'https://open.bigmodel.cn/api/paas/v4', '/');
    }
}
