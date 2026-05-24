<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait AdvancedFilter
{
    /**
     * Apply advanced filters to the given Eloquent query.
     *
     * Each filter item must have:
     *   - key:   column name (on the model's table) or a key defined in $relations['many']
     *   - value: single value or array of values
     *
     * Unknown keys are silently ignored to prevent SQL errors from arbitrary input.
     *
     * Usage in your class:
     *   $this->applyAdvancedFilter($query);
     *
     * Required properties on the using class:
     *   - $this->filter['advanced']   → array of filter objects/arrays
     *   - $this->relations['many']    → array of relation filter definitions
     *
     * Relation config options:
     *   - relation    : the Eloquent relation name to whereHas on (default: the key itself)
     *   - column      : the column to filter on inside the relation (default: 'id')
     *   - morph       : the polymorphic relation name on the related model
     *                   required when the column lives on a morph target, not on the relation itself
     *   - morph_types : array of morph model classes to scope the filter to
     *                   if omitted with morph, all morph types ('*') are targeted
     *
     * Examples:
     *
     *   'many' => [
     *       // minimal — relation name = key, column = 'id'
     *       'locations' => [],
     *
     *       // plain relation — column on the related table
     *       'actors' => [
     *           'column' => 'actor_id',
     *        ],
     *
     *       // nested relation — column on a nested related table
     *       'detection_status' => [
     *           'relation' => 'detections.logs',
     *           'column'   => 'detection_status_id',
     *        ],
     *
     *       // polymorphic — column lives on a specific morph target (e.g. event_detections)
     *       // use a model-scoped relation to narrow referenceable_type first,
     *       // then morph + morph_types to safely cross into the morph target
     *       'detection_types' => [
     *           'relation'    => 'secondaryDetectionReferences',  // scoped HasMany on the model
     *           'morph'       => 'referenceable',                 // morph relation on TicketReference
     *           'morph_types' => [EventDetection::class],         // only hit event_detections table
     *           'column'      => 'detection_type_id',
     *        ],
     *
     *       // polymorphic — column exists on ALL morph targets (use carefully)
     *       'tags' => [
     *            'relation' => 'secondaryReferences',
     *            'morph'    => 'referenceable',
     *            'column'   => 'tag_id',
     *        ],
     *   ],
     */
    public function applyAdvancedFilter(Builder $query): static
    {
        $advancedFilters = $this->filter['advanced'] ?? [];

        if (empty($advancedFilters)) {
            return $this;
        }

        $relationMap = $this->relations['many'] ?? [];
        $allowedColumns = $this->getAllowedColumns($query);
        $allowedKeys = array_merge(array_keys($relationMap), $allowedColumns);

        collect($advancedFilters)->each(function ($item) use ($query, $relationMap, $allowedKeys) {
            $key = data_get($item, 'key');
            $value = Arr::wrap(data_get($item, 'value'));

            // silently skip unknown keys
            if (! in_array($key, $allowedKeys, true)) {
                return;
            }

            // Apply resolver if defined for this key
            if (isset($this->resolvers[$key])) {
                $resolver = $this->resolvers[$key];
                $value = $resolver['enum']::{$resolver['method']}(data_get($item, 'value'));
                $value = Arr::wrap($value);
            }

            if (empty($value)) {
                return;
            }

            try {

                if (array_key_exists($key, $relationMap)) {
                    $relation = data_get($relationMap[$key], 'relation', $key);
                    $column = data_get($relationMap[$key], 'column', 'id');
                    $morph = data_get($relationMap[$key], 'morph');
                    $morphTypes = data_get($relationMap[$key], 'morph_types', []);

                    if ($morph && ! empty($morphTypes)) {
                        $query->whereHas($relation, fn ($q) => $q->whereHasMorph($morph, $morphTypes, fn ($q2) => $q2->whereIn($column, $value)
                        )
                        );
                    } else {
                        $query->whereHas($relation, fn ($q) => $q->whereIn($column, $value));
                    }

                } else {
                    $query->whereIn($key, $value);
                }

            } catch (\Exception $e) {
                abort(400, __('api.record_not_found'));
            }
        });

        return $this;
    }

    private function getAllowedColumns(Builder $query): array
    {
        return $query->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($query->getModel()->getTable());
    }
}
