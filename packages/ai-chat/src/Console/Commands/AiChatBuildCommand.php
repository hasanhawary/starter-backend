<?php

namespace AiChat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class AiChatBuildCommand extends Command
{
    protected $signature = 'ai-chat:build';

    protected $description = 'Build the AI Chat JavaScript widget';

    public function handle(): void
    {
        $this->components->info('Building AI Chat widget...');

        $configPath = __DIR__.'/../../../vite.widget.config.js';

        $result = Process::path(base_path())
            ->run(['npx', 'vite', 'build', '--config', $configPath]);

        if ($result->successful()) {
            $this->components->info('Widget built to public/vendor/ai-chat/ai-chat-widget.min.js');
        } else {
            $this->components->error('Build failed. Make sure vite and terser are installed.');
            $this->line($result->errorOutput());
        }
    }
}
