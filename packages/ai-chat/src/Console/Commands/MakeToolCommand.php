<?php

namespace AiChat\Console\Commands;

use AiChat\Generator\StubManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeToolCommand extends Command
{
    protected $signature = 'ai-chat:make-tool {name : Tool name} {--model= : Associated model}';

    protected $description = 'Create a new MCP tool';

    public function handle(): int
    {
        $name = $this->argument('name');
        $model = $this->option('model');

        $stubManager = app(StubManager::class);

        $baseName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
        $className = Str::endsWith($baseName, 'Tool') ? $baseName : $baseName.'Tool';
        $toolName = Str::snake($name);

        $outputPath = app_path('AI/Tools/Custom');
        File::ensureDirectoryExists($outputPath);

        $filePath = $outputPath."/{$className}.php";

        if (File::exists($filePath)) {
            $this->components->error("Tool [{$className}] already exists at {$filePath}");

            return self::FAILURE;
        }

        $modelClass = $model ? 'App\\Models\\'.Str::studly(Str::singular($model)) : '';
        $modelShort = $model ? Str::studly(Str::singular($model)) : '';
        $description = $model
            ? "Custom tool for {$modelShort} operations"
            : "Custom tool: {$toolName}";

        $stub = $stubManager->get('tool');

        $body = $modelClass && class_exists($modelClass)
            ? $this->buildModelBody($modelShort)
            : $this->buildDefaultBody();

        $content = $stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Tools\\Custom',
            'class' => $className,
            'name' => $toolName,
            'description' => addslashes($description),
            'model_class' => $modelClass ?: 'App\\Models\\Model',
            'short_name' => $modelShort ?: 'Model',
            'table' => $model ? Str::snake(Str::pluralStudly($model)) : 'table',
            'schema' => $this->buildSchema(),
            'body' => $body,
            'imports' => 'use AiChat\\Contracts\\ToolInterface;'."\n".'use AiChat\\MCP\\ToolResult;'."\n".'use AiChat\\Policies\\ChatContext;',
        ]);

        File::put($filePath, $content);

        $this->components->info("Tool [{$className}] created at {$filePath}");

        return self::SUCCESS;
    }

    protected function buildModelBody(string $modelShort): string
    {
        return "        \$limit = min((int) (\$arguments['limit'] ?? 10), 100);\n\n"
            ."        \$records = {$modelShort}::query()\n"
            ."            ->latest()\n"
            ."            ->limit(\$limit)\n"
            ."            ->get()\n"
            ."            ->toArray();\n\n"
            .'        return ToolResult::success($records);';
    }

    protected function buildDefaultBody(): string
    {
        return "        return ToolResult::success(['message' => 'Tool executed successfully']);";
    }

    protected function buildSchema(): string
    {
        return "        [\n            'type' => 'object',\n            'properties' => [\n"
            ."                'limit' => ['type' => 'integer', 'description' => 'Number of records (max 100)'],\n"
            ."            ],\n        ]";
    }
}
