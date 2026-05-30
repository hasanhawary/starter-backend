<?php

namespace AiChat\Support;

class JsonSchemaBuilder
{
    private array $properties = [];

    private array $required = [];

    public function addString(string $name, string $description, bool $required = false): self
    {
        $this->properties[$name] = [
            'type' => 'string',
            'description' => $description,
        ];

        if ($required) {
            $this->required[] = $name;
        }

        return $this;
    }

    public function addInteger(string $name, string $description, ?int $minimum = null, ?int $maximum = null, bool $required = false): self
    {
        $this->properties[$name] = array_filter([
            'type' => 'integer',
            'description' => $description,
            'minimum' => $minimum,
            'maximum' => $maximum,
        ]);

        if ($required) {
            $this->required[] = $name;
        }

        return $this;
    }

    public function addEnum(string $name, string $description, array $values, bool $required = false): self
    {
        $this->properties[$name] = [
            'type' => 'string',
            'description' => $description,
            'enum' => $values,
        ];

        if ($required) {
            $this->required[] = $name;
        }

        return $this;
    }

    public function addDateRange(string $name = 'date_range', bool $required = false): self
    {
        $this->properties['start_date'] = [
            'type' => 'string',
            'description' => 'Start date in Y-m-d format',
        ];

        $this->properties['end_date'] = [
            'type' => 'string',
            'description' => 'End date in Y-m-d format',
        ];

        return $this;
    }

    public function build(): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $this->properties,
        ];

        if (! empty($this->required)) {
            $schema['required'] = $this->required;
        }

        return $schema;
    }
}
