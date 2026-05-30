<?php

namespace AiChat\Console\Commands;

use AiChat\Generator\AgentGenerator;
use Illuminate\Console\Command;

class MakeAgentCommand extends Command
{
    protected $signature = 'ai-chat:make-agent {name : Agent name}';

    protected $description = 'Create a new AI agent';

    public function handle(): int
    {
        $name = $this->argument('name');

        $generator = app(AgentGenerator::class);

        try {
            $path = $generator->generate($name);
            $this->components->info("Agent created at {$path}");
        } catch (\Throwable $e) {
            $this->components->error("Failed to create agent: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
