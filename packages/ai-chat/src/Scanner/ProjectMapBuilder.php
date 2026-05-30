<?php

namespace AiChat\Scanner;

use AiChat\Models\AiProjectMap;

class ProjectMapBuilder
{
    public function build(array $scanResults): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'models' => $scanResults['models'] ?? [],
            'routes' => $scanResults['routes'] ?? [],
            'controllers' => $scanResults['controllers'] ?? [],
            'services' => $scanResults['services'] ?? [],
            'policies' => $scanResults['policies'] ?? [],
            'migrations' => $scanResults['migrations'] ?? [],
            'summary' => [
                'models_count' => count($scanResults['models'] ?? []),
                'routes_count' => $scanResults['routes']['total'] ?? 0,
                'controllers_count' => count($scanResults['controllers'] ?? []),
                'services_count' => count($scanResults['services'] ?? []),
                'policies_count' => count($scanResults['policies'] ?? []),
                'migrations_count' => count($scanResults['migrations'] ?? []),
                'tables_count' => count($scanResults['migrations'] ?? []),
            ],
        ];
    }

    public function store(array $projectMap): AiProjectMap
    {
        $hash = $this->computeHash($projectMap);

        $existing = AiProjectMap::where('scan_hash', $hash)->first();

        if ($existing !== null) {
            return $existing;
        }

        return AiProjectMap::create([
            'scan_hash' => $hash,
            'project_map' => $projectMap,
            'models_count' => $projectMap['summary']['models_count'] ?? 0,
            'routes_count' => $projectMap['summary']['routes_count'] ?? 0,
            'controllers_count' => $projectMap['summary']['controllers_count'] ?? 0,
            'services_count' => $projectMap['summary']['services_count'] ?? 0,
            'policies_count' => $projectMap['summary']['policies_count'] ?? 0,
        ]);
    }

    public function getLatest(): ?AiProjectMap
    {
        return AiProjectMap::latest()->first();
    }

    public function getLatestData(): array
    {
        $latest = $this->getLatest();

        if ($latest === null) {
            return [];
        }

        return $latest->project_map;
    }

    public function toJson(): string
    {
        $data = $this->getLatestData();

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function computeHash(array $projectMap): string
    {
        $relevant = [
            $projectMap['summary'] ?? [],
            array_keys($projectMap['models'] ?? []),
        ];

        return md5(serialize($relevant));
    }
}
