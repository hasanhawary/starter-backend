<?php

use Illuminate\Support\Str;

if (! function_exists('resolveModel')) {
    /**
     * Resolve a model instance based on its name and optional module.
     *
     * @param string $name
     * @param string|null $module
     * @return object|null
     */
    function resolveModel(string $name, $module = null): ?object
    {
        $modelPath = ! empty($module) && $module !== 'none'
            ? 'Modules\\'.ucfirst(Str::camel($module)).'\\App\\Models'
            : 'App\\Models';

        $modelClass = $modelPath.'\\'.Str::studly(Str::singular($name));

        return class_exists($modelClass) ? app($modelClass) : null;
    }
}
