<?php

namespace AiChat\MCP;

use AiChat\Support\SensitiveDataSanitizer;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ToolOutputNormalizer
{
    private int $maxCollectionItems;

    public function __construct()
    {
        $this->maxCollectionItems = (int) config('ai-chat.context.max_tool_result_items', 10);
    }

    public function normalize(mixed $output): array
    {
        $converted = $this->convertToArray($output);

        $converted = $this->limitCollections($converted);

        return SensitiveDataSanitizer::sanitize($converted);
    }

    protected function convertToArray(mixed $output): array
    {
        if ($output === null) {
            return [];
        }

        if (is_array($output)) {
            return array_map(fn ($item) => $this->convertValue($item), $output);
        }

        if ($output instanceof Model) {
            return $output->toArray();
        }

        if ($output instanceof Collection) {
            return $output->toArray();
        }

        if ($output instanceof Arrayable) {
            return $output->toArray();
        }

        if ($output instanceof ToolResult) {
            return $this->convertToArray($output->data);
        }

        return ['result' => $output];
    }

    protected function convertValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->toArray();
        }

        if ($value instanceof Collection) {
            return $value->toArray();
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->convertValue($item), $value);
        }

        return $value;
    }

    protected function limitCollections(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value) && array_is_list($value) && count($value) > $this->maxCollectionItems) {
                return array_slice($value, 0, $this->maxCollectionItems);
            }

            if (is_array($value)) {
                return $this->limitCollections($value);
            }

            return $value;
        }, $data);
    }
}
