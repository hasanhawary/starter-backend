<?php

namespace Modules\Form\Tools\Form\Services;

use Illuminate\Support\Str;
use Modules\Form\app\Models\FormField;

class FormReferenceResolver
{
    /**
     * Resolve a stored submission value into its human readable display value.
     *
     * For option based fields (select, radio, checkbox) the stored `value` is a
     * key/id. This turns that key/id into the matching label using either the
     * inline options defined in the field scheme or, when the option source is a
     * model, the related record's name. Non option fields resolve to null.
     *
     * @return string|array<int, string>|null
     */
    public function resolve(?FormField $field, mixed $value): string|array|null
    {
        if (! $field) {
            return null;
        }

        $scheme = $field->scheme ?? [];
        $reference = $scheme['reference'] ?? null;

        if (! is_array($reference) || $value === null || $value === '') {
            return null;
        }

        if (! $this->hasOptions($scheme)) {
            return null;
        }

        $resolved = array_map(
            fn ($single): ?string => $this->resolveSingle($reference, $single),
            $this->normalizeValue($value)
        );

        if (collect($resolved)->every(fn ($item): bool => $item === null)) {
            return null;
        }

        return $this->wasMultiple($value, $resolved) ? $resolved : ($resolved[0] ?? null);
    }

    /**
     * Resolve a single key/id into its display value based on the reference type.
     */
    protected function resolveSingle(array $reference, mixed $single): ?string
    {
        // Inline static options take precedence when present.
        if (isset($reference['options']) && is_array($reference['options'])) {
            return $this->resolveFromInlineOptions($reference['options'], $single);
        }

        $type = $reference['type'] ?? null;
        $path = $reference['path'] ?? null;
        $module = $reference['module'] ?? null;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return match (true) {
            in_array($type, ['enum', 'help-enum'], true) => $this->resolveFromEnum($path, $module, $single),
            in_array($type, ['model', 'help-model'], true) => $this->resolveFromModel($path, $module, $reference, $single),
            default => null,
        };
    }

    /**
     * Match a value against the inline scheme options and return its label.
     */
    protected function resolveFromInlineOptions(array $options, mixed $single): ?string
    {
        foreach ($options as $option) {
            if (($option['value'] ?? null) == $single) {
                return $this->translate($option['label'] ?? null) ?? (string) $single;
            }
        }

        return (string) $single;
    }

    /**
     * Resolve an enum class from its path/module and turn the value into a label.
     */
    protected function resolveFromEnum(string $path, ?string $module, mixed $single): ?string
    {
        $enum = $this->resolveEnumClass($path, $module);

        if (! $enum || ! method_exists($enum, 'resolve')) {
            return (string) $single;
        }

        return (string) $enum::resolve($single);
    }

    /**
     * Resolve the referenced model record and return its display name.
     */
    protected function resolveFromModel(string $path, ?string $module, array $reference, mixed $single): ?string
    {
        $instance = resolveModel($path, $module);

        if (! $instance) {
            return (string) $single;
        }

        $column = $reference['column'] ?? $instance->getKeyName();

        $record = $instance->newQuery()->where($column, $single)->first();

        if (! $record) {
            return (string) $single;
        }

        return $this->translate($record->name) ?? (string) $single;
    }

    /**
     * Build the enum class FQCN from a name/path and optional module.
     */
    protected function resolveEnumClass(string $path, ?string $module): ?string
    {
        if (class_exists($path)) {
            return $path;
        }

        $name = collect(explode('.', $path))
            ->map(fn (string $part): string => ucfirst(Str::camel($part)))
            ->implode('\\');

        foreach ($this->enumNamespaces($module) as $namespace) {
            $class = trim($namespace, '\\')."\\{$name}Enum";

            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Candidate enum namespaces, scoped to a module when provided.
     *
     * @return array<int, string>
     */
    protected function enumNamespaces(?string $module): array
    {
        if (empty($module)) {
            $default = trim((string) config('form.default_enum_namespace', 'App\\Enum'), '\\');

            return $default === '' ? [] : [$default];
        }

        $moduleNamespace = trim((string) config('lookup.modules.namespace', 'Modules'), '\\');
        $enumNamespace = trim((string) config('lookup.modules.enum_namespace', 'app\\Enum'), '\\');

        if ($moduleNamespace === '' || $enumNamespace === '') {
            return [];
        }

        return [$moduleNamespace.'\\'.Str::studly($module).'\\'.$enumNamespace];
    }

    /**
     * Whether the field type supports options, driven by the validations config.
     */
    protected function hasOptions(array $scheme): bool
    {
        $inputType = $scheme['input_type'] ?? null;

        if ($inputType === null) {
            return false;
        }

        return in_array($inputType, $this->optionInputTypes(), true);
    }

    /**
     * Input types flagged with `has_options` in the validations config.
     *
     * @return array<int, string>
     */
    protected function optionInputTypes(): array
    {
        return collect(config('form.form_validations', []))
            ->filter(fn ($type): bool => ($type['has_options'] ?? null) === true)
            ->pluck('type')
            ->all();
    }

    /**
     * Normalise a stored value into a flat list of scalar keys/ids.
     *
     * @return array<int, mixed>
     */
    protected function normalizeValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values($decoded);
            }
        }

        return [$value];
    }

    /**
     * Translate a label that may be a translatable array or a plain string.
     */
    protected function translate(mixed $label): ?string
    {
        if (is_array($label)) {
            return $label[app()->getLocale()] ?? (reset($label) ?: null);
        }

        return is_string($label) && $label !== '' ? $label : null;
    }

    /**
     * Whether the original value represented multiple selections.
     */
    protected function wasMultiple(mixed $value, array $resolved): bool
    {
        return is_array($value) || count($resolved) > 1;
    }
}
