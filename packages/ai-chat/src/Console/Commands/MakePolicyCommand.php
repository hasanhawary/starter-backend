<?php

namespace AiChat\Console\Commands;

use AiChat\Generator\PolicyGenerator;
use Illuminate\Console\Command;

class MakePolicyCommand extends Command
{
    protected $signature = 'ai-chat:make-policy {name : Policy name}';

    protected $description = 'Create a new chat policy';

    public function handle(): int
    {
        $name = $this->argument('name');

        $generator = app(PolicyGenerator::class);

        try {
            $path = $generator->generate($name);
            $this->components->info("Policy created at {$path}");
        } catch (\Throwable $e) {
            $this->components->error("Failed to create policy: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
