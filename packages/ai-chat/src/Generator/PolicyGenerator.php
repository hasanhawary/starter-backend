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

        $allowedActions = $options['allowed_actions'] ?? ['read', 'search', 'count'];
        $blockedModels = $options['blocked_models'] ?? [];
        $blockedFields = $options['blocked_fields'] ?? [];
        $maxRecords = $options['max_records'] ?? 100;
        $requireAuth = isset($options['require_auth']) ? (bool) $options['require_auth'] : true;

        $policyLogic = $this->buildPolicyLogic($allowedActions, $blockedModels, $blockedFields, $maxRecords, $requireAuth);

        $content = $this->stubManager->replace($stub, [
            'namespace' => 'App\\AI\\Policies',
            'class_name' => $className,
            'policy_logic' => $policyLogic,
        ]);

        File::put($filePath, $content);

        return $filePath;
    }

    protected function buildPolicyLogic(array $allowedActions, array $blockedModels, array $blockedFields, int $maxRecords, bool $requireAuth): string
    {
        $allBlockedModels = array_merge(
            ['App\\Models\\PersonalAccessToken', 'App\\Models\\PasswordResetToken'],
            $blockedModels,
        );

        $allBlockedFields = array_unique(array_merge(
            ['password', 'remember_token', 'token', 'secret', 'api_key', 'two_factor_secret'],
            $blockedFields,
        ));

        $actionsLiteral = implode(', ', array_map(fn (string $a) => "'{$a}'", $allowedActions));
        $modelsLiteral = implode(', ', array_map(fn (string $m) => "'".addslashes($m)."'", $allBlockedModels));
        $fieldsLiteral = implode(', ', array_map(fn (string $f) => "'{$f}'", $allBlockedFields));

        $lines = [];

        if ($requireAuth) {
            $lines[] = '        if ($context->user === null) {';
            $lines[] = "            return PolicyResult::denied('Authentication is required.', self::class);";
            $lines[] = '        }';
            $lines[] = '';
        }

        $lines[] = "        if (! in_array(\$context->action, [{$actionsLiteral}], true)) {";
        $lines[] = '            return PolicyResult::denied("The action [{$context->action}] is not allowed by this policy.", self::class);';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = "        if (isset(\$context->payload['model']) && in_array(\$context->payload['model'], [{$modelsLiteral}], true)) {";
        $lines[] = "            return PolicyResult::denied('Access to this model is blocked by policy.', self::class);";
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = "        if (isset(\$context->payload['fields'])) {";
        $lines[] = "            \$blocked = array_intersect(\$context->payload['fields'], [{$fieldsLiteral}]);";
        $lines[] = '            if (! empty($blocked)) {';
        $lines[] = "                return PolicyResult::denied('Access to certain fields is blocked by policy.', self::class);";
        $lines[] = '            }';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = "        return PolicyResult::allowed('Action permitted by policy.', self::class);";

        return implode("\n", $lines);
    }
}
