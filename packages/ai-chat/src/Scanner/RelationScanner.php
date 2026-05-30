<?php

namespace AiChat\Scanner;

use ReflectionClass;
use ReflectionMethod;

class RelationScanner
{
    protected array $relationTypes = [
        'hasOne',
        'hasMany',
        'belongsTo',
        'belongsToMany',
        'morphOne',
        'morphMany',
        'morphTo',
        'morphToMany',
        'morphedByMany',
        'hasOneThrough',
        'hasManyThrough',
    ];

    public function scan(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        $reflection = new ReflectionClass($modelClass);
        $relations = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $modelClass) {
                continue;
            }

            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            $relationType = $this->detectRelationType($method);

            if ($relationType === null) {
                continue;
            }

            $relations[] = [
                'method' => $method->getName(),
                'type' => $relationType,
                'related_model' => $this->detectRelatedModel($method, $modelClass),
                'foreign_key' => $this->detectForeignKey($method),
            ];
        }

        return $relations;
    }

    protected function detectRelationType(ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();

        if ($returnType === null) {
            return $this->detectFromSource($method);
        }

        $typeName = $returnType instanceof \ReflectionNamedType
            ? $returnType->getName()
            : (string) $returnType;

        $relationMap = [
            'Illuminate\Database\Eloquent\Relations\HasOne' => 'hasOne',
            'Illuminate\Database\Eloquent\Relations\HasMany' => 'hasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsTo' => 'belongsTo',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany' => 'belongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphOne' => 'morphOne',
            'Illuminate\Database\Eloquent\Relations\MorphMany' => 'morphMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo' => 'morphTo',
            'Illuminate\Database\Eloquent\Relations\MorphToMany' => 'morphToMany',
            'Illuminate\Database\Eloquent\Relations\MorphedByMany' => 'morphedByMany',
            'Illuminate\Database\Eloquent\Relations\HasOneThrough' => 'hasOneThrough',
            'Illuminate\Database\Eloquent\Relations\HasManyThrough' => 'hasManyThrough',
        ];

        return $relationMap[$typeName] ?? null;
    }

    protected function detectFromSource(ReflectionMethod $method): ?string
    {
        $filename = $method->getFileName();

        if ($filename === false) {
            return null;
        }

        $source = file_get_contents($filename);

        if ($source === false) {
            return null;
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($startLine === false || $endLine === false) {
            return null;
        }

        $lines = explode("\n", $source);
        $methodBody = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        foreach ($this->relationTypes as $type) {
            if (preg_match('/\$this->'.$type.'\s*\(/', $methodBody)) {
                return $type;
            }
        }

        return null;
    }

    protected function detectRelatedModel(ReflectionMethod $method, string $modelClass): ?string
    {
        $filename = $method->getFileName();

        if ($filename === false) {
            return null;
        }

        $source = file_get_contents($filename);

        if ($source === false) {
            return null;
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($startLine === false || $endLine === false) {
            return null;
        }

        $lines = explode("\n", $source);
        $methodBody = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        foreach ($this->relationTypes as $type) {
            if (preg_match('/\$this->'.$type.'\s*\(\s*([^,\)]+)/', $methodBody, $matches)) {
                $firstArg = trim($matches[1]);

                if (preg_match('/^([A-Z][a-zA-Z0-9_]*)::class$/', $firstArg, $classMatch)) {
                    $className = $classMatch[1];
                    $namespace = $this->getNamespaceFromFile($filename);
                    $fullyQualified = $namespace ? $namespace.'\\'.$className : $className;

                    if (class_exists($fullyQualified)) {
                        return $fullyQualified;
                    }
                }

                if (preg_match('/^self::class$/', $firstArg)) {
                    return $modelClass;
                }

                if (preg_match('/^__CLASS__$/', $firstArg)) {
                    return $modelClass;
                }

                return $firstArg;
            }
        }

        return null;
    }

    protected function detectForeignKey(ReflectionMethod $method): ?string
    {
        $filename = $method->getFileName();

        if ($filename === false) {
            return null;
        }

        $source = file_get_contents($filename);

        if ($source === false) {
            return null;
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($startLine === false || $endLine === false) {
            return null;
        }

        $lines = explode("\n", $source);
        $methodBody = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        $foreignKeyPatterns = [
            '/,\s*[\'"]([^\'"]+)_id[\'"]\s*\)/',
            '/,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/',
            '/foreign_key\s*[\'"]([^\'"]+)[\'"]/',
        ];

        foreach ($foreignKeyPatterns as $pattern) {
            if (preg_match($pattern, $methodBody, $matches)) {
                return end($matches);
            }
        }

        return null;
    }

    protected function getNamespaceFromFile(string $filename): ?string
    {
        $contents = file_get_contents($filename);

        if ($contents === false) {
            return null;
        }

        if (preg_match('/namespace\s+([^;]+);/i', $contents, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
