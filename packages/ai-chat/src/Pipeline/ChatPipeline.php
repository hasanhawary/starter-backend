<?php

namespace AiChat\Pipeline;

use AiChat\Pipeline\Steps\ApplyPolicies;
use AiChat\Pipeline\Steps\BuildPrompt;
use AiChat\Pipeline\Steps\ExecuteTools;
use AiChat\Pipeline\Steps\PersistResponse;
use AiChat\Pipeline\Steps\PlanStep;
use AiChat\Pipeline\Steps\ResolveAgent;
use AiChat\Pipeline\Steps\ResolveContext;
use AiChat\Pipeline\Steps\ResolveTools;
use AiChat\Pipeline\Steps\ResolveUser;
use AiChat\Pipeline\Steps\RetrieveKnowledge;
use AiChat\Pipeline\Steps\RetrieveMemory;
use AiChat\Pipeline\Steps\SendToProvider;
use AiChat\Pipeline\Steps\ValidateMessage;
use Illuminate\Pipeline\Pipeline;

class ChatPipeline
{
    protected array $steps = [
        ValidateMessage::class,
        ResolveUser::class,
        ResolveAgent::class,
        PlanStep::class,
        ApplyPolicies::class,
        ResolveContext::class,
        RetrieveKnowledge::class,
        RetrieveMemory::class,
        ResolveTools::class,
        BuildPrompt::class,
        SendToProvider::class,
        ExecuteTools::class,
        PersistResponse::class,
    ];

    public function process(ChatPayload $payload): ChatPayload
    {
        return app(Pipeline::class)
            ->send($payload)
            ->through($this->steps)
            ->thenReturn();
    }

    public static function make(): self
    {
        return new self;
    }

    public function withSteps(array $steps): self
    {
        $this->steps = $steps;

        return $this;
    }

    public function prependStep(string $step): self
    {
        array_unshift($this->steps, $step);

        return $this;
    }

    public function appendStep(string $step): self
    {
        $this->steps[] = $step;

        return $this;
    }

    public function removeStep(string $step): self
    {
        $this->steps = array_values(array_filter(
            $this->steps,
            fn (string $s) => $s !== $step,
        ));

        return $this;
    }
}
