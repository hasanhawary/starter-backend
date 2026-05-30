<?php

namespace AiChat\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SafeQueryBuilder
{
    public static function build(Model $model, array $constraints = []): Builder
    {
        $query = $model->newQuery();

        if (isset($constraints['where'])) {
            foreach ($constraints['where'] as $column => $value) {
                if (static::isSafeColumn($model, $column)) {
                    $query->where($column, $value);
                }
            }
        }

        if (isset($constraints['date_from'])) {
            $dateColumn = $constraints['date_column'] ?? 'created_at';
            if (static::isSafeColumn($model, $dateColumn)) {
                $query->where($dateColumn, '>=', $constraints['date_from']);
            }
        }

        if (isset($constraints['date_to'])) {
            $dateColumn = $constraints['date_column'] ?? 'created_at';
            if (static::isSafeColumn($model, $dateColumn)) {
                $query->where($dateColumn, '<=', $constraints['date_to']);
            }
        }

        if (isset($constraints['limit'])) {
            $query->limit(min((int) $constraints['limit'], 100));
        }

        if (isset($constraints['order_by']) && static::isSafeColumn($model, $constraints['order_by'])) {
            $direction = ($constraints['order_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($constraints['order_by'], $direction);
        }

        return $query;
    }

    public static function isSafeColumn(Model $model, string $column): bool
    {
        if (str_contains($column, '.') || str_contains($column, ' ')) {
            return false;
        }

        $fillable = $model->getFillable();
        $dates = $model->getDates();

        return in_array($column, $fillable, true)
            || in_array($column, $dates, true)
            || $column === $model->getKeyName()
            || in_array($column, ['created_at', 'updated_at', 'deleted_at'], true);
    }
}
