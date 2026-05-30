<?php

namespace AiChat\Generator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ToolGenerator
{
    protected array $numericCastTypes = [
        'integer',
        'int',
        'float',
        'double',
        'decimal',
        'real',
    ];

    public function __construct(
        private readonly StubManager $stubManager,
    ) {}

    public function generate(array $projectMap): array
    {
        $generatedPaths = [];
        $outputPath = app_path('AI/Tools/Generated');

        File::ensureDirectoryExists($outputPath);

        $blockedModels = (new ManifestGenerator($this->stubManager))->buildBlockedModels();

        foreach ($projectMap['models'] ?? [] as $modelClass => $modelData) {
            if (in_array($modelClass, $blockedModels, true)) {
                continue;
            }

            $paths = $this->generateForModel($modelClass, $modelData);
            $generatedPaths = array_merge($generatedPaths, $paths);
        }

        return $generatedPaths;
    }

    public function generateForModel(string $modelClass, array $modelData): array
    {
        $generatedPaths = [];
        $outputPath = app_path('AI/Tools/Generated');

        File::ensureDirectoryExists($outputPath);

        $shortName = class_basename($modelClass);
        $toolName = Str::snake($shortName);

        $generatedPaths[] = $this->writeTool(
            $outputPath."/{$shortName}StatsTool.php",
            $this->buildStatsTool($shortName, $modelClass, $modelData, $toolName),
        );

        $generatedPaths[] = $this->writeTool(
            $outputPath."/{$shortName}LatestRecordsTool.php",
            $this->buildLatestRecordsTool($shortName, $modelClass, $modelData, $toolName),
        );

        $generatedPaths[] = $this->writeTool(
            $outputPath."/{$shortName}CountTool.php",
            $this->buildCountTool($shortName, $modelClass, $modelData, $toolName),
        );

        $generatedPaths[] = $this->writeTool(
            $outputPath."/{$shortName}SearchTool.php",
            $this->buildSearchTool($shortName, $modelClass, $modelData, $toolName),
        );

        if ($this->hasNumericFields($modelData)) {
            $generatedPaths[] = $this->writeTool(
                $outputPath."/{$shortName}SummaryTool.php",
                $this->buildSummaryTool($shortName, $modelClass, $modelData, $toolName),
            );
        }

        return array_filter($generatedPaths);
    }

    protected function buildStatsTool(string $shortName, string $modelClass, array $modelData, string $toolName): string
    {
        $stub = $this->stubManager->get('tool');

        return $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Tools\\Generated',
            'class_name' => "{$shortName}StatsTool",
            'tool_name' => "{$toolName}_stats",
            'tool_description' => "Get statistical overview of {$shortName} records",
            'model_import' => "use {$modelClass};",
            'schema' => $this->buildStatsSchema(),
            'authorize_logic' => 'return $context->action === \'read\';',
            'execute_logic' => $this->buildStatsBody($shortName, $modelClass, $modelData),
        ]);
    }

    protected function buildLatestRecordsTool(string $shortName, string $modelClass, array $modelData, string $toolName): string
    {
        $stub = $this->stubManager->get('tool');

        return $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Tools\\Generated',
            'class_name' => "{$shortName}LatestRecordsTool",
            'tool_name' => "{$toolName}_latest",
            'tool_description' => "Get latest {$shortName} records",
            'model_import' => "use {$modelClass};",
            'schema' => $this->buildLatestRecordsSchema(),
            'authorize_logic' => 'return $context->action === \'read\';',
            'execute_logic' => $this->buildLatestRecordsBody($shortName, $modelClass, $modelData),
        ]);
    }

    protected function buildCountTool(string $shortName, string $modelClass, array $modelData, string $toolName): string
    {
        $stub = $this->stubManager->get('tool');

        return $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Tools\\Generated',
            'class_name' => "{$shortName}CountTool",
            'tool_name' => "{$toolName}_count",
            'tool_description' => "Count {$shortName} records with optional filters",
            'model_import' => "use {$modelClass};",
            'schema' => $this->buildCountSchema($modelData),
            'authorize_logic' => 'return $context->action === \'read\';',
            'execute_logic' => $this->buildCountBody($shortName, $modelClass, $modelData),
        ]);
    }

    protected function buildSearchTool(string $shortName, string $modelClass, array $modelData, string $toolName): string
    {
        $stub = $this->stubManager->get('tool');

        return $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Tools\\Generated',
            'class_name' => "{$shortName}SearchTool",
            'tool_name' => "{$toolName}_search",
            'tool_description' => "Search {$shortName} records by fillable fields",
            'model_import' => "use {$modelClass};",
            'schema' => $this->buildSearchSchema($modelData),
            'authorize_logic' => 'return $context->action === \'read\';',
            'execute_logic' => $this->buildSearchBody($shortName, $modelClass, $modelData),
        ]);
    }

    protected function buildSummaryTool(string $shortName, string $modelClass, array $modelData, string $toolName): string
    {
        $stub = $this->stubManager->get('tool');

        return $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Tools\\Generated',
            'class_name' => "{$shortName}SummaryTool",
            'tool_name' => "{$toolName}_summary",
            'tool_description' => "Get numeric summary (avg, min, max, sum) for {$shortName} records",
            'model_import' => "use {$modelClass};",
            'schema' => $this->buildSummarySchema($modelData),
            'authorize_logic' => 'return $context->action === \'read\';',
            'execute_logic' => $this->buildSummaryBody($shortName, $modelClass, $modelData),
        ]);
    }

    protected function buildStatsBody(string $shortName, string $modelClass, array $modelData): string
    {
        $softDeletes = $modelData['soft_deletes'] ?? false;
        $body = "        \$total = {$shortName}::count();\n";
        $body .= "        \$stats = ['total' => \$total];\n\n";

        if ($softDeletes) {
            $body .= "        \$stats['trashed'] = {$shortName}::onlyTrashed()->count();\n";
        }

        $body .= "\n        if ({$shortName}::usesTimestamps()) {\n";
        $body .= "            \$stats['latest_created'] = {$shortName}::latest()->value('created_at');\n";
        $body .= "            \$stats['oldest_created'] = {$shortName}::oldest()->value('created_at');\n";
        $body .= "        }\n\n";
        $body .= '        return ToolResult::success($stats);';

        return $body;
    }

    protected function buildLatestRecordsBody(string $shortName, string $modelClass, array $modelData): string
    {
        $body = "        \$limit = min((int) (\$arguments['limit'] ?? 10), 100);\n\n";
        $body .= "        \$records = {$shortName}::query()\n";
        $body .= "            ->latest()\n";
        $body .= "            ->limit(\$limit)\n";
        $body .= "            ->get()\n";
        $body .= "            ->toArray();\n\n";
        $body .= '        return ToolResult::success($records);';

        return $body;
    }

    protected function buildCountBody(string $shortName, string $modelClass, array $modelData): string
    {
        $body = "        \$query = {$shortName}::query();\n\n";
        $body .= "        if (!empty(\$arguments['filters'])) {\n";
        $body .= "            foreach (\$arguments['filters'] as \$column => \$value) {\n";
        $body .= "                if (SafeQueryBuilder::isSafeColumn(new {$shortName}, \$column)) {\n";
        $body .= "                    \$query->where(\$column, \$value);\n";
        $body .= "                }\n";
        $body .= "            }\n";
        $body .= "        }\n\n";
        $body .= "        return ToolResult::success(['count' => \$query->count()]);";

        return $body;
    }

    protected function buildSearchBody(string $shortName, string $modelClass, array $modelData): string
    {
        $stringFields = array_values(array_filter(
            $modelData['fillable'] ?? [],
            fn (string $field) => ! in_array($field, ['password', 'remember_token', 'token', 'secret'], true),
        ));

        $body = "        \$limit = min((int) (\$arguments['limit'] ?? 10), 100);\n";
        $body .= "        \$search = \$arguments['search'] ?? '';\n\n";
        $body .= "        \$query = {$shortName}::query();\n\n";

        if (! empty($stringFields)) {
            $body .= "        if (\$search !== '') {\n";
            $body .= "            \$query->where(function (\$q) use (\$search) {\n";

            foreach ($stringFields as $field) {
                $body .= "                \$q->orWhere('{$field}', 'LIKE', \"%\$search%\");\n";
            }

            $body .= "            });\n";
            $body .= "        }\n\n";
        }

        $body .= "        \$records = \$query->latest()->limit(\$limit)->get()->toArray();\n\n";
        $body .= '        return ToolResult::success($records);';

        return $body;
    }

    protected function buildSummaryBody(string $shortName, string $modelClass, array $modelData): string
    {
        $numericFields = $this->getNumericFields($modelData);
        $body = "        \$summary = [\n";

        foreach ($numericFields as $field) {
            $body .= "            '{$field}' => [\n";
            $body .= "                'avg' => {$shortName}::avg('{$field}'),\n";
            $body .= "                'min' => {$shortName}::min('{$field}'),\n";
            $body .= "                'max' => {$shortName}::max('{$field}'),\n";
            $body .= "                'sum' => {$shortName}::sum('{$field}'),\n";
            $body .= "            ],\n";
        }

        $body .= "        ];\n\n";
        $body .= '        return ToolResult::success($summary);';

        return $body;
    }

    protected function buildStatsSchema(): string
    {
        return "        [\n            'type' => 'object',\n            'properties' => [],\n        ]";
    }

    protected function buildLatestRecordsSchema(): string
    {
        return "        [\n            'type' => 'object',\n            'properties' => [\n"
            ."                'limit' => ['type' => 'integer', 'description' => 'Number of records (max 100)'],\n"
            ."            ],\n        ]";
    }

    protected function buildCountSchema(array $modelData): string
    {
        return "        [\n            'type' => 'object',\n            'properties' => [\n"
            ."                'filters' => ['type' => 'object', 'description' => 'Key-value filters on fillable columns'],\n"
            ."            ],\n        ]";
    }

    protected function buildSearchSchema(array $modelData): string
    {
        return "        [\n            'type' => 'object',\n            'properties' => [\n"
            ."                'search' => ['type' => 'string', 'description' => 'Search term'],\n"
            ."                'limit' => ['type' => 'integer', 'description' => 'Max results (default 10, max 100)'],\n"
            ."            ],\n        ]";
    }

    protected function buildSummarySchema(array $modelData): string
    {
        return "        [\n            'type' => 'object',\n            'properties' => [],\n        ]";
    }

    protected function buildToolImports(): string
    {
        return 'use AiChat\\AiChat\\Contracts\\ToolInterface;'
            ."\nuse AiChat\\AiChat\\MCP\\ToolResult;"
            ."\nuse AiChat\\AiChat\\Policies\\ChatContext;"
            ."\nuse AiChat\\AiChat\\Support\\SafeQueryBuilder;";
    }

    protected function hasNumericFields(array $modelData): bool
    {
        return count($this->getNumericFields($modelData)) > 0;
    }

    protected function getNumericFields(array $modelData): array
    {
        $numeric = [];

        foreach ($modelData['casts'] ?? [] as $field => $cast) {
            $castLower = strtolower($cast);

            if (in_array($castLower, $this->numericCastTypes, true) || str_starts_with($castLower, 'decimal')) {
                $numeric[] = $field;
            }
        }

        foreach ($modelData['fillable'] ?? [] as $field) {
            if (str_ends_with($field, '_count') || str_ends_with($field, '_amount') || str_ends_with($field, '_total') || str_ends_with($field, '_price')) {
                if (! in_array($field, $numeric, true)) {
                    $numeric[] = $field;
                }
            }
        }

        return $numeric;
    }

    protected function writeTool(string $path, string $content): ?string
    {
        try {
            File::put($path, $content);

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}
