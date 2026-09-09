<?php

namespace App\Trait\Global;

use Illuminate\Support\Facades\DB;

/**
 * Quoting for literals embedded in a model's `sortableExpressions()`.
 *
 * Those expressions are handed to `orderByRaw` without bindings, and escaping
 * is driver specific — MySQL treats a backslash as an escape character while
 * SQLite does not — so a morph class name such as `App\Models\User` has to be
 * quoted by the very connection that will run the query.
 */
trait QuotesSortLiterals
{
    /**
     * Quote a value for embedding in a raw sort expression.
     */
    protected function quoteSortLiteral(string $value): string
    {
        return DB::connection($this->getConnectionName())->getPdo()->quote($value);
    }
}
