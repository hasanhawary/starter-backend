<?php

namespace AiChat\MCP;

use Illuminate\Support\Facades\Validator;

class ToolInputValidator
{
    public function validate(array $arguments, array $schema): array
    {
        $rules = $this->buildRules($schema);
        $defaults = $this->extractDefaults($schema);

        $merged = $this->applyDefaults($arguments, $schema);

        $validator = Validator::make($merged, $rules);

        if ($validator->fails()) {
            throw new ToolValidationException($validator->errors()->all());
        }

        $validated = $validator->validated();

        return $this->enforceLimits($validated, $schema);
    }

    public function applyDefaults(array $arguments, array $schema): array
    {
        $properties = $schema['properties'] ?? [];

        foreach ($properties as $name => $definition) {
            if (! array_key_exists($name, $arguments) && array_key_exists('default', $definition)) {
                $arguments[$name] = $definition['default'];
            }
        }

        return $arguments;
    }

    public function enforceLimits(array $arguments, array $schema): array
    {
        $properties = $schema['properties'] ?? [];

        foreach ($properties as $name => $definition) {
            if (! array_key_exists($name, $arguments)) {
                continue;
            }

            $value = $arguments[$name];

            if (isset($definition['minimum']) && is_numeric($value) && $value < $definition['minimum']) {
                $arguments[$name] = $definition['minimum'];
            }

            if (isset($definition['maximum']) && is_numeric($value) && $value > $definition['maximum']) {
                $arguments[$name] = $definition['maximum'];
            }

            if (isset($definition['minLength']) && is_string($value) && mb_strlen($value) < $definition['minLength']) {
                $arguments[$name] = str_pad($value, $definition['minLength']);
            }

            if (isset($definition['maxLength']) && is_string($value) && mb_strlen($value) > $definition['maxLength']) {
                $arguments[$name] = mb_substr($value, 0, $definition['maxLength']);
            }

            if (isset($definition['minItems']) && is_array($value) && count($value) < $definition['minItems']) {
                continue;
            }

            if (isset($definition['maxItems']) && is_array($value) && count($value) > $definition['maxItems']) {
                $arguments[$name] = array_slice($value, 0, $definition['maxItems']);
            }
        }

        return $arguments;
    }

    protected function buildRules(array $schema): array
    {
        $rules = [];
        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];

        foreach ($properties as $name => $definition) {
            $fieldRules = [];

            if (in_array($name, $required)) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'sometimes';
            }

            $type = $definition['type'] ?? 'string';

            $fieldRules = array_merge($fieldRules, $this->typeToRules($type, $definition));

            if (isset($definition['enum'])) {
                $fieldRules[] = 'in:'.implode(',', $definition['enum']);
            }

            $rules[$name] = $fieldRules;
        }

        return $rules;
    }

    protected function typeToRules(string $type, array $definition): array
    {
        return match ($type) {
            'string' => array_filter([
                'string',
                isset($definition['minLength']) ? 'min:'.$definition['minLength'] : null,
                isset($definition['maxLength']) ? 'max:'.$definition['maxLength'] : null,
            ]),
            'integer' => array_filter([
                'integer',
                isset($definition['minimum']) ? 'min:'.$definition['minimum'] : null,
                isset($definition['maximum']) ? 'max:'.$definition['maximum'] : null,
            ]),
            'number' => array_filter([
                'numeric',
                isset($definition['minimum']) ? 'min:'.$definition['minimum'] : null,
                isset($definition['maximum']) ? 'max:'.$definition['maximum'] : null,
            ]),
            'boolean' => ['boolean'],
            'array' => array_filter([
                'array',
                isset($definition['maxItems']) ? 'max:'.$definition['maxItems'] : null,
                isset($definition['minItems']) ? 'min:'.$definition['minItems'] : null,
            ]),
            default => [],
        };
    }

    protected function extractDefaults(array $schema): array
    {
        $defaults = [];
        $properties = $schema['properties'] ?? [];

        foreach ($properties as $name => $definition) {
            if (array_key_exists('default', $definition)) {
                $defaults[$name] = $definition['default'];
            }
        }

        return $defaults;
    }
}
