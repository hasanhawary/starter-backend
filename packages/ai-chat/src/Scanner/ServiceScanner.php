<?php

namespace AiChat\Scanner;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class ServiceScanner
{
    public function scan(): array
    {
        $services = [];
        $paths = $this->getServicePaths();

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

                if (! $this->isServiceLike($reflection)) {
                    continue;
                }

                $services[$class] = $this->analyzeService($reflection, $class);
            }
        }

        return $services;
    }

    protected function getServicePaths(): array
    {
        return [app_path('Services')];
    }

    protected function analyzeService(ReflectionClass $reflection, string $class): array
    {
        return [
            'class' => $class,
            'short_name' => $reflection->getShortName(),
            'public_methods' => $this->getPublicMethods($reflection, $class),
            'dependencies' => $this->getConstructorDependencies($reflection),
            'file_path' => $reflection->getFileName(),
        ];
    }

    protected function getPublicMethods(ReflectionClass $reflection, string $class): array
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

    protected function getConstructorDependencies(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            $typeName = null;

            if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();
            }

            $dependencies[] = [
                'name' => $param->getName(),
                'type' => $typeName,
                'nullable' => $param->allowsNull(),
                'has_default' => $param->isDefaultValueAvailable(),
            ];
        }

        return $dependencies;
    }

    protected function isServiceLike(ReflectionClass $reflection): bool
    {
        $name = $reflection->getName();

        if (str_contains($name, 'Service')) {
            return true;
        }

        $filename = $reflection->getFileName();

        if ($filename === false) {
            return false;
        }

        return str_contains($filename, '/Services/');
    }

    protected function getMethodParameters(ReflectionMethod $method): array
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $typeName = null;

            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
            } elseif ($type instanceof \ReflectionUnionType) {
                $typeName = implode('|', array_map(
                    fn (\ReflectionNamedType $t) => $t->getName(),
                    $type->getTypes(),
                ));
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
