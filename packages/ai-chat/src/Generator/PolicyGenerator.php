<?php

namespace AiChat\Generator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PolicyGenerator
{
    public function __construct(
        private readonly StubManager $stubManager,
    ) {}

    public function generate(string $name, array $options = []): string
    {
        $outputPath = app_path('AI/Policies');
        File::ensureDirectoryExists($outputPath);

        $baseName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
        $className = Str::endsWith($baseName, 'Policy') ? $baseName : $baseName.'Policy';

        $filePath = $outputPath."/{$className}.php";

        $stub = $this->stubManager->get('policy');

        $description = $options['description'] ?? "AI policy for {$name}";
        $allowedActions = $this->buildAllowedActions($options['allowed_actions'] ?? ['read', 'search', 'count']);
        $blockedModels = $this->buildBlockedModels($options['blocked_models'] ?? []);
        $blockedFields = $this->buildBlockedFields($options['blocked_fields'] ?? []);
        $maxRecords = $options['max_records'] ?? 100;
        $requireAuth = isset($options['require_auth']) ? ($options['require_auth'] ? 'true' : 'false') : 'true';

        $content = $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Policies',
            'class' => $className,
            'name' => Str::snake($name),
            'description' => addslashes($description),
            'allowed_actions' => $allowedActions,
            'blocked_models' => $blockedModels,
            'blocked_fields' => $blockedFields,
            'max_records' => (string) $maxRecords,
            'require_auth' => $requireAuth,
        ]);

        File::put($filePath, $content);

        return $filePath;
    }

    protected function buildAllowedActions(array $actions): string
    {
        $items = array_map(fn (string $action) => "            '{$action}',", $actions);

        return "[\n".implode("\n", $items)."\n        ]";
    }

    protected function buildBlockedModels(array $models): string
    {
        $defaults = [
            "'App\\\\Models\\\\PersonalAccessToken'",
            "'App\\\\Models\\\\PasswordResetToken'",
        ];

        $all = array_merge($defaults, array_map(fn (string $m) => "'".addslashes($m)."'", $models));

        $items = array_map(fn (string $model) => "            {$model},", $all);

        return "[\n".implode("\n", $items)."\n        ]";
    }

    protected function buildBlockedFields(array $fields): string
    {
        $defaults = [
            "'password'",
            "'remember_token'",
            "'token'",
            "'secret'",
            "'api_key'",
            "'two_factor_secret'",
        ];

        $all = array_unique(array_merge($defaults, array_map(fn (string $f) => "'{$f}'", $fields)));

        $items = array_map(fn (string $field) => "            {$field},", $all);

        return "[\n".implode("\n", $items)."\n        ]";
    }
}
