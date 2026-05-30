<?php

namespace AiChat\Scanner;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class ModelScanner
{
    public function __construct(
        private readonly RelationScanner $relationScanner,
    ) {}

    public function scan(): array
    {
        $models = [];
        $paths = $this->getModelPaths();

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $modelFiles = File::glob($path.'/**/*.php');

            if (empty($modelFiles)) {
                $modelFiles = File::glob($path.'/*.php');
            }

            foreach ($modelFiles as $file) {
                $class = $this->classFromFile($file, $path);

                if ($class === null || ! class_exists($class)) {
                    continue;
                }

                $reflection = new ReflectionClass($class);

                if (! $reflection->isSubclassOf(Model::class) || $reflection->isAbstract()) {
                    continue;
                }

                $modelData = $this->analyzeModel($class, $reflection);

                if ($modelData !== null) {
                    $models[$class] = $modelData;
                }
            }
        }

        return $models;
    }

    protected function getModelPaths(): array
    {
        $paths = [app_path('Models')];

        $modulesPath = base_path('Modules');

        if (is_dir($modulesPath)) {
            foreach (File::directories($modulesPath) as $module) {
                $modelsPath = $module.'/Models';

                if (is_dir($modelsPath)) {
                    $paths[] = $modelsPath;
                }
            }
        }

        return $paths;
    }

    protected function analyzeModel(string $class, ReflectionClass $reflection): ?array
    {
        try {
            $instance = $reflection->newInstanceWithoutConstructor();
        } catch (\Throwable) {
            return null;
        }

        return [
            'class' => $class,
            'short_name' => $reflection->getShortName(),
            'table' => $this->resolveTable($instance, $reflection),
            'fillable' => $instance->getFillable(),
            'casts' => $instance->getCasts(),
            'hidden' => $instance->getHidden(),
            'relationships' => $this->relationScanner->scan($class),
            'scopes' => $this->detectScopes($reflection, $class),
            'traits' => $this->detectTraits($reflection),
            'policy' => $this->detectPolicy($class),
            'dates' => $instance->getDates(),
            'key_type' => $reflection->getProperty('keyType')->getDefaultValue() ?? 'int',
            'incrementing' => $reflection->getProperty('incrementing')->getDefaultValue() ?? true,
            'soft_deletes' => $this->usesSoftDeletes($reflection),
            'file_path' => $reflection->getFileName(),
        ];
    }

    protected function resolveTable(Model $instance, ReflectionClass $reflection): string
    {
        try {
            return $instance->getTable();
        } catch (\Throwable) {
            $shortName = $reflection->getShortName();

            return Str::snake(Str::pluralStudly($shortName));
        }
    }

    protected function detectScopes(ReflectionClass $reflection, string $class): array
    {
        $scopes = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $name = $method->getName();

            if (str_starts_with($name, 'scope') && $name !== 'scope') {
                $scopeName = lcfirst(substr($name, 5));

                $scopes[] = [
                    'name' => $scopeName,
                    'method' => $name,
                    'parameters' => $this->getMethodParameters($method),
                ];
            }
        }

        return $scopes;
    }

    protected function detectTraits(ReflectionClass $reflection): array
    {
        $traits = [];

        foreach ($reflection->getTraitNames() as $trait) {
            $shortName = (new ReflectionClass($trait))->getShortName();
            $traits[] = [
                'trait' => $trait,
                'short_name' => $shortName,
            ];
        }

        return $traits;
    }

    protected function detectPolicy(string $class): ?string
    {
        $policy = app('Illuminate\Contracts\Auth\Access\Gate')->getPolicyFor($class);

        if ($policy !== null) {
            return $policy::class;
        }

        $reflection = new ReflectionClass($class);
        $modelPath = $reflection->getFileName();

        if ($modelPath === false) {
            return null;
        }

        $shortName = $reflection->getShortName();
        $policyClass = 'App\\Policies\\'.$shortName.'Policy';

        if (class_exists($policyClass)) {
            return $policyClass;
        }

        return null;
    }

    protected function usesSoftDeletes(ReflectionClass $reflection): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', $reflection->getTraitNames(), true);
    }

    protected function getMethodParameters(ReflectionMethod $method): array
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            if ($param->getName() === 'query') {
                continue;
            }

            $type = $param->getType();
            $params[] = [
                'name' => $param->getName(),
                'type' => $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type,
                'nullable' => $param->allowsNull(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }

        return $params;
    }

    protected function classFromFile(string $file, string $basePath): ?string
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            return null;
        }

        if (! preg_match('/namespace\s+([^;]+);/i', $contents, $namespace)) {
            return null;
        }

        if (! preg_match('/class\s+(\w+)/i', $contents, $className)) {
            return null;
        }

        return $namespace[1].'\\'.$className[1];
    }
}
