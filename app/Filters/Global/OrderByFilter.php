<?php

namespace App\Filters\Global;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OrderByFilter
{
    public function handle($request, Closure $next)
    {
        $query = $next($request);

        try {
            $model = $query->getModel();
            $table = $model->getTable();

            $sortColumn = $this->resolveSortColumn($table, request('sort_column', 'id'));
            $sortDirection = $this->resolveSortDirection(request('sort_direction'));

            return $query->orderBy($sortColumn, $sortDirection);

        } catch (QueryException|\Exception $e) {
            Log::error('OrderByFilter unexpected error: '.$e->getMessage());

            return $query->orderBy('id', 'desc'); // Fallback to default sorting
        }
    }

    protected function resolveSortColumn(string $table, ?string $requested): string
    {
        try {
            if (! $requested) {
                return 'id';
            }

            // Handle JSON dot notation like name.en => name->en
            if (str_starts_with($requested, 'name') && Schema::hasColumn($table, 'name')) {
                $jsonKey = explode('.', $requested)[1] ?? null;
                if ($jsonKey) {
                    return "name->$jsonKey";
                }
            }

            // Check if the column exists
            if (Schema::hasColumn($table, $requested)) {
                return $requested;
            }

            // Fallback for "name" — if "first_name" exists
            if ($requested === 'name' && Schema::hasColumn($table, 'first_name')) {
                return 'first_name';
            }

            return 'id';
        } catch (\Exception $e) {
            Log::warning('Failed to resolve sort column: '.$e->getMessage());

            return 'id';
        }
    }

    protected function resolveSortDirection(?string $direction = null): string
    {
        return $direction && in_array(strtolower($direction), ['asc', 'desc'])
            ? strtolower($direction)
            : 'desc';
    }
}
