<?php

namespace AiChat\Contracts;

use Laravel\Ai\Enums\Lab;

interface HasProviderOptions
{
    public function providerOptions(Lab|string $provider): array;
}
