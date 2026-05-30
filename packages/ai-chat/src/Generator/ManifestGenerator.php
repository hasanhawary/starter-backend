<?php

namespace AiChat\Generator;

use Illuminate\Support\Facades\File;

class ManifestGenerator
{
    public function __construct(
        private readonly StubManager $stubManager,
    ) {}

    public function generate(array $projectMap): string
    {
        $config = [
            'allowed_models' => $this->buildAllowedModels($projectMap),
            'blocked_models' => $this->buildBlockedModels(),
            'blocked_fields' => $this->buildBlockedFields(),
            'models' => $this->buildModelConfig($projectMap),
            'routes' => $this->buildRouteConfig($projectMap),
            'summary' => $projectMap['summary'] ?? [],
        ];

        $content = "<?php\n\nreturn [\n".$this->arrayToString($config, 1)."\n];\n";

        return $content;
    }

    public function publish(array $projectMap): void
    {
        $content = $this->generate($projectMap);
        $path = config_path('ai-project.php');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    public function buildAllowedModels(array $projectMap): array
    {
        $blocked = $this->buildBlockedModels();

        return array_values(array_filter(
            array_keys($projectMap['models'] ?? []),
            fn (string $model) => ! in_array($model, $blocked, true),
        ));
    }

    public function buildBlockedModels(): array
    {
        return [
            'Illuminate\\Database\\Eloquent\\Model',
            'App\\Models\\PersonalAccessToken',
            'App\\Models\\PasswordResetToken',
            'Laravel\\Sanctum\\PersonalAccessToken',
            'Illuminate\\Notifications\\DatabaseNotification',
        ];
    }

    public function buildBlockedFields(): array
    {
        return [
            'password',
            'remember_token',
            'token',
            'secret',
            'api_key',
            'access_token',
            'refresh_token',
            'private_key',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'otp_data',
        ];
    }

    protected function buildModelConfig(array $projectMap): array
    {
        $blocked = $this->buildBlockedModels();
        $blockedFields = $this->buildBlockedFields();
        $config = [];

        foreach ($projectMap['models'] ?? [] as $class => $model) {
            if (in_array($class, $blocked, true)) {
                continue;
            }

            $config[$class] = [
                'table' => $model['table'],
                'fillable' => array_values(array_filter(
                    $model['fillable'],
                    fn (string $field) => ! in_array($field, $blockedFields, true),
                )),
                'casts' => $model['casts'],
                'relationships' => array_map(fn (array $rel) => [
                    'method' => $rel['method'],
                    'type' => $rel['type'],
                    'related_model' => $rel['related_model'],
                ], $model['relationships']),
                'scopes' => array_map(fn (array $scope) => $scope['name'], $model['scopes']),
                'soft_deletes' => $model['soft_deletes'],
            ];
        }

        return $config;
    }

    protected function buildRouteConfig(array $projectMap): array
    {
        $routes = [];

        foreach ($projectMap['routes']['routes'] ?? [] as $route) {
            if ($route['controller'] === null) {
                continue;
            }

            $routes[] = [
                'uri' => $route['uri'],
                'methods' => $route['methods'],
                'controller' => $route['controller'],
                'action' => $route['action'],
                'name' => $route['name'],
            ];
        }

        return $routes;
    }

    protected function arrayToString(array $array, int $indent = 1): string
    {
        $spaces = str_repeat('    ', $indent);
        $lines = [];

        $isSequential = array_keys($array) === range(0, count($array) - 1);

        foreach ($array as $key => $value) {
            $keyStr = $isSequential ? '' : "'".addslashes((string) $key)."' => ";

            if (is_array($value)) {
                if (empty($value)) {
                    $lines[] = $spaces.$keyStr.'[],';
                } else {
                    $inner = $this->arrayToString($value, $indent + 1);
                    $lines[] = $spaces.$keyStr.'[';
                    $lines[] = $inner;
                    $lines[] = $spaces.'],';
                }
            } elseif (is_bool($value)) {
                $lines[] = $spaces.$keyStr.($value ? 'true' : 'false').',';
            } elseif (is_int($value) || is_float($value)) {
                $lines[] = $spaces.$keyStr.$value.',';
            } elseif ($value === null) {
                $lines[] = $spaces.$keyStr.'null,';
            } else {
                $lines[] = $spaces.$keyStr."'".addslashes((string) $value)."',";
            }
        }

        return implode("\n", $lines);
    }
}
