<?php

namespace AiChat\Scanner;

use Illuminate\Support\Facades\File;

class MigrationScanner
{
    public function scan(): array
    {
        $migrations = [];
        $paths = $this->getMigrationPaths();

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::glob($path.'/*.php') as $file) {
                $migration = $this->parseMigration($file);

                if ($migration !== null) {
                    $migrations[$migration['table']] = $migration;
                }
            }
        }

        return $migrations;
    }

    protected function getMigrationPaths(): array
    {
        $paths = [database_path('migrations')];

        $modulesPath = base_path('Modules');

        if (is_dir($modulesPath)) {
            foreach (File::directories($modulesPath) as $module) {
                $migrationPath = $module.'/Database/Migrations';

                if (is_dir($migrationPath)) {
                    $paths[] = $migrationPath;
                }
            }
        }

        return $paths;
    }

    protected function parseMigration(string $file): ?array
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            return null;
        }

        $tableName = $this->extractTableName($contents);

        if ($tableName === null) {
            return null;
        }

        $columns = $this->extractColumns($contents);
        $indexes = $this->extractIndexes($contents);
        $foreignKeys = $this->extractForeignKeys($contents);

        return [
            'table' => $tableName,
            'file' => basename($file),
            'file_path' => $file,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }

    protected function extractTableName(string $contents): ?string
    {
        if (preg_match("/Schema::create\s*\(\s*['\"]([^'\"]+)['\"]/", $contents, $matches)) {
            return $matches[1];
        }

        if (preg_match("/Schema::table\s*\(\s*['\"]([^'\"]+)['\"]/", $contents, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function extractColumns(string $contents): array
    {
        $columns = [];
        $columnPatterns = [
            '/\$table->(\w+)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*([^)]+))?\)/',
        ];

        foreach ($columnPatterns as $pattern) {
            if (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $type = $match[1];
                    $name = $match[2];
                    $length = isset($match[3]) ? trim($match[3]) : null;

                    if ($type === 'timestamps' || $type === 'softDeletes' || $type === 'rememberToken' || $type === 'uuid') {
                        continue;
                    }

                    $column = [
                        'name' => $name,
                        'type' => $type,
                    ];

                    if ($length !== null && $length !== '') {
                        $column['length'] = $length;
                    }

                    $column['nullable'] = str_contains($contents, '->nullable()');
                    $column['unique'] = str_contains($contents, '->unique()');
                    $column['default'] = $this->extractDefaultValue($contents, $name);
                    $column['unsigned'] = str_contains($contents, '->unsigned()');

                    $columns[$name] = $column;
                }
            }
        }

        return $columns;
    }

    protected function extractDefaultValue(string $contents, string $columnName): mixed
    {
        $pattern = "/\$table->\w+\s*\(\s*['\"]".preg_quote($columnName, '/')."['\"].*?->default\s*\(\s*([^)]+)\)/s";

        if (preg_match($pattern, $contents, $matches)) {
            $value = trim($matches[1]);

            if (str_starts_with($value, "'") || str_starts_with($value, '"')) {
                return trim($value, "'\"");
            }

            if (strtolower($value) === 'true') {
                return true;
            }

            if (strtolower($value) === 'false') {
                return false;
            }

            if (strtolower($value) === 'null') {
                return null;
            }

            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }

            return $value;
        }

        return null;
    }

    protected function extractIndexes(string $contents): array
    {
        $indexes = [];

        if (preg_match_all('/\$table->index\s*\(\s*([^)]+)\)/', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columns = $this->parseIndexColumns($match[1]);
                $indexes[] = [
                    'type' => 'index',
                    'columns' => $columns,
                ];
            }
        }

        if (preg_match_all('/\$table->unique\s*\(\s*([^)]+)\)/', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columns = $this->parseIndexColumns($match[1]);
                $indexes[] = [
                    'type' => 'unique',
                    'columns' => $columns,
                ];
            }
        }

        return $indexes;
    }

    protected function extractForeignKeys(string $contents): array
    {
        $foreignKeys = [];

        if (preg_match_all('/\$table->foreign\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->references\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->on\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $foreignKey = [
                    'column' => $match[1],
                    'references' => $match[2],
                    'on' => $match[3],
                ];

                if (preg_match('/\$table->foreign\s*\(\s*[\'"]'.preg_quote($match[1], '/').'[\'"]\s*\).*?->onDelete\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/s', $contents, $onDelete)) {
                    $foreignKey['on_delete'] = $onDelete[1];
                }

                if (preg_match('/\$table->foreign\s*\(\s*[\'"]'.preg_quote($match[1], '/').'[\'"]\s*\).*?->onUpdate\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/s', $contents, $onUpdate)) {
                    $foreignKey['on_update'] = $onUpdate[1];
                }

                $foreignKeys[] = $foreignKey;
            }
        }

        if (preg_match_all('/\$table->foreignId\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->constrained\s*\(\s*(?:[\'"]([^\'"]+)[\'"])?\s*\)/', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $foreignKeys[] = [
                    'column' => $match[1],
                    'references' => 'id',
                    'on' => $match[2] ?? $this->guessTableFromForeignKey($match[1]),
                ];
            }
        }

        return $foreignKeys;
    }

    protected function parseIndexColumns(string $raw): array
    {
        $columns = [];

        if (preg_match_all("/['\"]([^'\"]+)['\"]/", $raw, $matches)) {
            $columns = $matches[1];
        }

        if (preg_match('/\[(.+)\]/', $raw, $arrayMatch)) {
            if (preg_match_all("/['\"]([^'\"]+)['\"]/", $arrayMatch[1], $matches)) {
                $columns = $matches[1];
            }
        }

        return $columns;
    }

    protected function guessTableFromForeignKey(string $column): string
    {
        if (str_ends_with($column, '_id')) {
            $base = substr($column, 0, -3);

            return str_replace('_', '', ucwords($base, '_')).'s';
        }

        return $column;
    }
}
