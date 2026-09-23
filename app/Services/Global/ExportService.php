<?php

namespace App\Services\Global;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ExportService
{
    /**
     * @var string[]
     */
    private array $columns;

    private array $relations;

    private array $customWith;

    private array $additionalQuery;

    private string|array $model;

    private array $filter;

    public function __construct(array $config, array $filter)
    {
        /**
         * Columns Configuration:
         *
         * Define the structure and data types of the columns associated with a 'cause'.
         * Each entry in the array represents a column in your dataset.
         * The key is the column name, and the value is the data type of that column.
         *
         * Supported data types include:
         * - 'text': for text-based columns.
         * - 'date': for date columns.
         * - Enum classes: for columns that should be an enum value (e.g., CauseStatusEnum::class).
         * - 'file': for file or binary data columns.
         * - 'bool': for boolean columns.
         *
         * Example:
         * To add a 'description' column of type 'text', add the following key-value pair:
         * 'description' => 'text',
         */
        $this->columns = $config['columns'];

        /**
         * Relations Configuration:
         *
         * Define the relationships between 'cause' and other entities.
         * Relationships are categorized into 'one' (one-to-one) and 'many' (one-to-many).
         *
         * For one-to-one relationships, use the 'one' key, and for one-to-many relationships, use the 'many' key.
         *
         * Each relationship should be an array where the key is the relationship name and the value is an array defining the structure of the related entity.
         *
         * For nested relations, the structure can be nested arrays.
         *
         * Example:
         * To add a one-to-one relationship with an entity 'category' which has a 'name' column, add the following:
         * 'one' => [
         *     'category_id' => ['category' => ['name' => 'text']]
         * ],
         * To add a one-to-many relationship with an entity 'tags' which has a 'label' column, add the following:
         * 'many' => [
         *     'tags' => ['label' => 'text']
         * ],
         */
        $this->relations = $config['relations'];

        $this->customWith = $config['customWith'] ?? [];

        /**
         * Additional Query Configuration:
         *
         * Define additional queries or helper queries that can be used in the 'with' method when retrieving the 'cause' entity.
         * Each query should be an array where the key is the query name and the value is a closure defining the structure of the query.
         *
         * For example:
         * To add a query named 'example_query' which performs a specific operation, add the following:
         * 'name_column' => function ($query) {
         *     // Define the structure of your query here
         *     $query->with{Aggregate}(['relations' => Closure()]);
         * },
         */
        $this->additionalQuery = $config['additionalQuery'] ?? [];

        /**
         * Model Configuration:
         *
         * Set the model for the entity based on the provided configuration.
         */
        $this->model = $config['model'];

        /**
         * Filter Configuration:
         *
         * Set the filter based on the provided configuration.
         */
        $this->filter = $filter;
    }

    /**.
     * @return array
     */
    public function array(): array
    {
        $columns = array_merge(array_keys($this->columns), array_keys($this->relations['one']));
        $relations = $this->resolveRelationKeys($this->resolveOneRelation($this->relations['one']));
        $relations = array_merge($relations, $this->resolveRelationKeys($this->applyFilter($this->relations['many']['concat'])));
        $relations = array_merge($relations, $this->resolveRelationKeys($this->relations['many']['list']));
        $relations = array_merge($relations, $this->customWith);
        $countRelations = $this->relations['many']['count'];

        $data = [];

        $query = $this->model::select($columns);

        if ($countRelations !== []) {
            $query->withCount(...$countRelations);
        }

        if ($relations !== []) {
            $query->with(...Arr::flatten($relations));
        }

        if (! empty($this->additionalQuery)) {
            collect($this->additionalQuery)->each(fn ($item) => $item($query));
        }

        if (isset($this->filter['conditions'])) {
            collect($this->filter['conditions'])->each(function ($item) use ($query) {
                if (array_key_exists(key($item), $this->relations['many']['concat'])) {
                    $query->whereHas(key($item), fn ($q) => $q->whereIn('id', Arr::wrap(current($item))));
                } else {
                    $query->whereIn(key($item), Arr::wrap(current($item)));
                }
            });
        }

        $startDate = isset($this->filter['start_date']) ? Carbon::parse($this->filter['start_date'])->format('Y-m-d') : null;
        $endDate = isset($this->filter['end_date']) ? Carbon::parse($this->filter['end_date'])->format('Y-m-d') : null;

        if (method_exists($this->model, 'scopeApplyPeriodFilter')) {
            // Models with a period scope match on all their own date columns
            // (and related logs where available) - same behaviour as the list filter.
            $query->applyPeriodFilter($startDate, $endDate);
        } else {
            if ($startDate) {
                $query->whereDate($this->filter['date_column'], '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate($this->filter['date_column'], '<=', $endDate);
            }
        }
        if (isset($this->filter['type'])) {
            $query->where('type', $this->filter['type']);
        }

        $query->chunk(100, function ($dataChunk) use (&$data) {
            $dataChunk->each(function ($item) use (&$data) {
                $data[] = $item;
            });
        });

        return $data;
    }

    /**
     * @param  mixed  $object
     */
    public function map($object): array
    {
        $result = [];

        foreach ($this->columns as $column => $type) {
            $result[$column] = $this->resolveValue($object, $column, $type);
        }

        foreach ($this->relations['one'] as $relation) {
            $result += $this->handleOneRelation($object, $relation);
        }

        foreach ($this->applyFilter($this->relations['many']['concat']) as $relation => $details) {
            $result[$relation] = $object->$relation->pluck(key($details))->join(', ');
        }

        $result = Arr::where(Arr::dot($result), function ($value, $key) {
            $strPart = Str::of($key)->explode('.');

            return ($strPart->last() === 'id' && $strPart->count() === 1) || $strPart->last() !== 'id';
        });

        foreach ($this->relations['many']['list'] as $relationName => $details) {
            $flattenedData = $this->flattenArray($this->handleManyRelation($object, $relationName, $details));
            $finalResult = collect($flattenedData)->reject(function ($value, $key) {
                return str_ends_with($key, '_id') || is_array($value);
            })->map(function ($value, $key) {
                return handleTrans($key).': '.(is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value).PHP_EOL;
            })->implode(str_repeat('-', 20).PHP_EOL);

            $result[$relationName] = $finalResult;
        }

        foreach ($this->relations['many']['count'] as $relationName) {
            $result[$relationName] = $object->{Str::snake($relationName).'_count'};
        }

        foreach ($this->additionalQuery as $key => $value) {
            $result[$key] = $object->$key ?? '';
        }

        return $result;
    }

    public function headings(): array
    {
        $this->columns = $this->applyFilter($this->columns);
        $this->relations['one'] = $this->applyFilter($this->relations['one']);
        //        $this->relations['many']['list'] = $this->applyFilter($this->relations['many']['list'], 'many');
        $this->relations['many']['count'] = $this->applyFilter($this->relations['many']['count'], 'many');
        $this->additionalQuery = $this->applyFilter($this->additionalQuery, 'many');

        $columnHeadings = array_keys($this->columns);
        $oneRelationHeadings = array_keys($this->resolveOneRelation($this->relations['one']));
        $concatRelationHeadings = array_keys(array_map(fn ($relation) => array_keys($relation), $this->applyFilter($this->relations['many']['concat'])));
        $manyRelationHeadings = array_keys(array_map(fn ($relation) => array_keys($relation), $this->relations['many']['list']));
        $countRelationHeadings = $this->relations['many']['count'];
        $additionalHeading = array_keys($this->additionalQuery);

        $columns = array_merge($columnHeadings, $oneRelationHeadings, $concatRelationHeadings, $manyRelationHeadings, $countRelationHeadings, $additionalHeading);

        return Arr::map($columns, fn ($item) => handleTrans($item));
    }

    private function resolveValue(mixed $object, string $column, mixed $type): mixed
    {
        if (empty($object)) {
            return '';
        }

        $value = $object->$column ?? '';

        return match ($type) {
            'date' => Carbon::parse($value)->toDateString(),
            'datetime' => Carbon::parse($value)->toDateTimeString(),
            'array' => is_array($value) ? implode(' , ', array_filter($value)) : $value,
            'int' => (int) $value,
            'text' => $value,
            'bool', 'boolean' => $value ? __('api.yes') : __('api.no'),
            default => (class_exists($type) && method_exists($type, 'transValue')) ? $type::transValue($value) : $value
        };
    }

    private function handleOneRelation(mixed $parentEntity, array $relationMappings): array
    {
        $extractedData = [];
        foreach ($relationMappings as $relationName => $columnMappings) {
            $relatedEntity = $parentEntity->$relationName;

            foreach ($columnMappings as $columnName => $columnType) {
                if (is_array($columnType)) {
                    $extractedData[$relationName][$columnName] = $this->handleOneRelation($relatedEntity, $columnType);
                } else {
                    $extractedData[$relationName][$columnName] = $this->resolveValue($relatedEntity, $columnName, $columnType);
                }
            }
        }

        return $extractedData;
    }

    private function handleManyRelation(mixed $object, string $relationName, array $details): array
    {
        $relatedDataSet = [];
        foreach ($object->$relationName as $relatedObject) {
            $relatedData = [];
            foreach ($details as $relatedColumn => $type) {
                if (is_array($type)) {
                    foreach ($type as $nestedRelation => $nestedDetails) {
                        $relatedData[$nestedRelation] = Arr::first($this->handleNestedRelation($relatedObject, $nestedRelation, $nestedDetails));
                    }
                } else {
                    $relatedData[$relatedColumn] = $this->resolveValue($relatedObject, $relatedColumn, $type);
                }
            }
            $relatedDataSet[] = $relatedData;
        }

        return $relatedDataSet;
    }

    /**
     * @param  Cause  $relatedObject
     */
    private function handleNestedRelation(mixed $relatedObject, ?string $nestedRelation, ?array $nestedDetails): array
    {
        if (is_array($relatedObject->$nestedRelation) && count($relatedObject->$nestedRelation) > 0 && is_object($relatedObject->$nestedRelation[0])) {
            return $this->handleManyRelation($relatedObject, $nestedRelation, $nestedDetails);
        }

        return $this->handleOneRelation($relatedObject, [$nestedRelation => $nestedDetails]);
    }

    private function resolveOneRelation(array $relations): array
    {
        return collect($relations)->mapWithKeys(fn ($relation) => $relation)->toArray();
    }

    private function resolveRelationKeys(array $relations): array
    {
        $selectedRelations = [];
        collect($relations)->each(function ($relationValue, $relationName) use (&$selectedRelations) {
            $selectedRelations[$relationName] = $this->guessRelationPattern($relationName, $relationValue);
        });

        return array_values($selectedRelations);
    }

    private function guessRelationPattern(string $relation, array $columns, ?string $parent = null): array|string
    {
        $nested = [];
        $normalRelation = collect($columns)->filter(fn ($r) => ! is_array($r))->toArray();
        $nestedRelations = collect($columns)->filter(fn ($r) => is_array($r));
        $patternColumns = implode(',', array_keys($normalRelation));

        if ($nestedRelations->isNotEmpty()) {
            $nestedRelations->each(function ($nestedRelation) use (&$nested, $relation) {
                collect($nestedRelation)->each(function ($relationValue, $relationName) use (&$nested, $relation) {
                    $nested[] = $this->guessRelationPattern($relationName, $relationValue, $relation);
                });
            });
        }

        $basicRelation = "$relation:$patternColumns";
        if ($parent) {
            $nested = ! empty($nested) ? $this->resolveNestedPattern($nested, $parent) : "$relation:$patternColumns";

            if (! is_array($nested) && $basicRelation === $nested) {
                $basicRelation = [];
                $nested = "$parent.$nested";
            } else {
                $basicRelation = "$parent.$basicRelation";
            }
        }

        if (is_string($nested)) {
            $basicRelation = $nested;
        }

        return is_array($nested) && ! empty($nested) ? Arr::flatten($nested) : $basicRelation;
    }

    private function resolveNestedPattern(?array $nested = [], ?string $parent = null): array
    {
        return $parent && ! empty($nested) ? array_map(function ($item) use ($parent) {
            return is_array($item) ? $this->resolveNestedPattern($item, $parent) : "$parent.$item";
        }, $nested) : $nested;
    }

    private function flattenArray(array $array, ?string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if ($prefix) {
                if ($key === 'date') {
                    $newKey = '1_date';
                } elseif (str_ends_with($key, 'date')) {
                    $newKey = ++$prefix.'_'.$key;
                } else {
                    $newKey = $prefix.'_'.$key;
                }
            } else {
                $newKey = $key;
            }

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    private function applyFilter(array $nativeArray, string $type = 'column'): array
    {
        if (! ($this->filter['apply_filter'] ?? true)) {
            return $nativeArray;
        }

        if (! isArrayIndex($nativeArray)) {
            if ($type === 'many') {
                return Arr::only($nativeArray, $this->filter['related'] ?? []);
            }

            return Arr::only($nativeArray, $this->filter['columns'] ?? []);
        }

        if ($type === 'many') {
            foreach ($nativeArray as $key => $value) {
                if (! in_array($value, $this->filter['related'] ?? [], true)) {
                    unset($nativeArray[$key]);
                }
            }
        }

        return $nativeArray;
    }
}
