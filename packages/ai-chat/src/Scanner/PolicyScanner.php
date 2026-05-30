<?php

namespace AiChat\Scanner;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class PolicyScanner
{
    public function scan(): array
    {
        $policies = [];
        $paths = $this->getPolicyPaths();

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::glob($path.'/**/*.php') as $file) {
                $class = $this->classFromFile($file);

                if ($class === null || ! class_exists($class)) {
                    continue;
                }

                $reflection = new ReflectionClass($class);

                if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                    continue;
                }

                if (! $this->isPolicyLike($reflection)) {
                    continue;
                }

                $policies[$class] = $this->analyzePolicy($reflection, $class);
            }
        }

        return $policies;
    }

    protected function getPolicyPaths(): array
    {
        return [app_path('Policies')];
    }

    protected function analyzePolicy(ReflectionClass $reflection, string $class): array
    {
        return [
            'class' => $class,
            'short_name' => $reflection->getShortName(),
            'methods' => $this->getPolicyMethods($reflection, $class),
            'corresponding_model' => $this->detectCorrespondingModel($reflection, $class),
            'file_path' => $reflection->getFileName(),
        ];
    }

    protected function getPolicyMethods(ReflectionClass $reflection, string $class): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            $methods[] = [
                'name' => $method->getName(),
                'parameters' => $this->getMethodParameters($method),
                'return_type' => $this->getReturnType($method),
            ];
        }

        return $methods;
    }

    protected function detectCorrespondingModel(ReflectionClass $reflection, string $class): ?string
    {
        $shortName = $reflection->getShortName();

        if (str_ends_with($shortName, 'Policy')) {
            $modelShortName = substr($shortName, 0, -6);
            $modelClass = 'App\\Models\\'.$modelShortName;

            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($method->getParameters() as $param) {
                $type = $param->getType();

                if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                    $typeName = $type->getName();

                    if (is_subclass_of($typeName, 'Illuminate\Database\Eloquent\Model')) {
                        return $typeName;
                    }
                }
            }
        }

        $gate = app('Illuminate\Contracts\Auth\Access\Gate');
        $policies = $gate->policies();

        foreach ($policies as $model => $policy) {
            if ($policy === $class) {
                return $model;
            }
        }

        return null;
    }

    protected function isPolicyLike(ReflectionClass $reflection): bool
    {
        $name = $reflection->getName();

        if (str_contains($name, 'Policy')) {
            return true;
        }

        if ($reflection->hasMethod('view') || $reflection->hasMethod('create') || $reflection->hasMethod('update') || $reflection->hasMethod('delete')) {
            return true;
        }

        $filename = $reflection->getFileName();

        if ($filename === false) {
            return false;
        }

        return str_contains($filename, '/Policies/');
    }

    protected function getMethodParameters(ReflectionMethod $method): array
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $typeName = null;

            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
            }

            $params[] = [
                'name' => $param->getName(),
                'type' => $typeName,
                'nullable' => $param->allowsNull(),
            ];
        }

        return $params;
    }

    protected function getReturnType(ReflectionMethod $method): ?string
    {
        $type = $method->getReturnType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    protected function classFromFile(string $file): ?string
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
