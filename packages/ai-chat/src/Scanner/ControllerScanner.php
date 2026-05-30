<?php

namespace AiChat\Scanner;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class ControllerScanner
{
    public function scan(): array
    {
        $controllers = [];
        $paths = $this->getControllerPaths();

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

                if (! $reflection->isSubclassOf(Controller::class) && ! $this->isControllerLike($reflection)) {
                    continue;
                }

                if ($reflection->isAbstract()) {
                    continue;
                }

                $controllers[$class] = $this->analyzeController($reflection, $class);
            }
        }

        return $controllers;
    }

    protected function getControllerPaths(): array
    {
        return [app_path('Http/Controllers')];
    }

    protected function analyzeController(ReflectionClass $reflection, string $class): array
    {
        return [
            'class' => $class,
            'short_name' => $reflection->getShortName(),
            'methods' => $this->getControllerMethods($reflection, $class),
            'middleware' => $this->detectMiddleware($reflection),
            'form_requests' => $this->detectFormRequests($reflection, $class),
            'file_path' => $reflection->getFileName(),
        ];
    }

    protected function getControllerMethods(ReflectionClass $reflection, string $class): array
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

    protected function detectMiddleware(ReflectionClass $reflection): array
    {
        $middleware = [];

        $defaultProperties = $reflection->getDefaultProperties();

        if (isset($defaultProperties['middleware']) && is_array($defaultProperties['middleware'])) {
            $middleware = $defaultProperties['middleware'];
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() === 'middleware' && $method->getDeclaringClass()->getName() !== Controller::class) {
                continue;
            }
        }

        return $middleware;
    }

    protected function detectFormRequests(ReflectionClass $reflection, string $class): array
    {
        $formRequests = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($method->getParameters() as $param) {
                $type = $param->getType();

                if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                    $typeName = $type->getName();

                    if (is_subclass_of($typeName, 'Illuminate\Foundation\Http\FormRequest')) {
                        $formRequests[$method->getName()][] = $typeName;
                    }
                }
            }
        }

        return $formRequests;
    }

    protected function isControllerLike(ReflectionClass $reflection): bool
    {
        $name = $reflection->getName();

        if (str_contains($name, 'Controller')) {
            return true;
        }

        $filename = $reflection->getFileName();

        if ($filename === false) {
            return false;
        }

        return str_contains($filename, 'Http/Controllers');
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
                'has_default' => $param->isDefaultValueAvailable(),
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
