<?php

namespace AiChat\Providers;

use AiChat\Gateway\GlmGateway;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Contracts\Gateway\TextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Providers\Concerns\GeneratesText;
use Laravel\Ai\Providers\Concerns\HasTextGateway;
use Laravel\Ai\Providers\Concerns\StreamsText;
use Laravel\Ai\Providers\Provider;

class GlmProvider extends Provider implements TextProvider
{
    use GeneratesText;
    use HasTextGateway;
    use StreamsText;

    public function __construct(protected array $config, protected Dispatcher $events)
    {
        //
    }

    public function textGateway(): TextGateway
    {
        return $this->textGateway ??= new GlmGateway($this->events);
    }

    public function defaultTextModel(): string
    {
        return $this->config['models']['text']['default'] ?? 'glm-5.1';
    }

    public function cheapestTextModel(): string
    {
        return $this->config['models']['text']['cheapest'] ?? 'glm-4-flash';
    }

    public function smartestTextModel(): string
    {
        return $this->config['models']['text']['smartest'] ?? 'glm-5.1';
    }
}
