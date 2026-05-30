<?php

namespace AiChat\MCP;

use AiChat\Contracts\ToolInterface;
use ReflectionClass;

class ToolDiscovery
{
    public function discoverIn(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $classes = [];

        foreach (glob($path.'/*.php') as $file) {
            $className = $this->classFromFile($file);

            if ($className && $this->validateClass($className)) {
                $classes[] = $className;
            }
        }

        foreach (glob($path.'/*', GLOB_ONLYDIR) as $subDir) {
            $classes = array_merge($classes, $this->discoverIn($subDir));
        }

        return $classes;
    }

    public function discoverInMultiple(array $paths): array
    {
        $classes = [];

        foreach ($paths as $path) {
            $classes = array_merge($classes, $this->discoverIn($path));
        }

        return array_unique($classes);
    }

    public function validateClass(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
            return false;
        }

        return $reflection->implementsInterface(ToolInterface::class);
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
