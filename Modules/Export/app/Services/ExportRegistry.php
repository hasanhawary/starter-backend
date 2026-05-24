<?php

namespace Modules\Export\app\Services;

use Illuminate\Support\Str;

class ExportRegistry
{
    /**
     * Build the export class path based on the type name and configured namespace.
     */
    protected static function buildExportPath(string $type): string
    {
        $ns = (string) (config('export.namespace'));

        return $ns.'\\'.Str::studly($type).'Export';
    }

    /**
     * Resolve the export class string for a given type, or null if it doesn't exist.
     */
    public static function getClass(string $type): ?string
    {
        $class = static::buildExportPath($type);

        return class_exists($class) ? $class : null;
    }

    /**
     * Get the title for a given type without instantiating (static method approach).
     * Falls back to a translation key if the class doesn't exist.
     */
    public static function getTitle(string $type): string
    {
        $class = static::getClass($type);

        if ($class && method_exists($class, 'getTitle')) {
            return (new $class([]))->getTitle();
        }

        return __('export::pdf.'.Str::plural(Str::snake($type)).'_title');
    }
}
