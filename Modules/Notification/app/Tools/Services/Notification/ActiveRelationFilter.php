<?php

namespace Modules\Notification\Tools\Services\Notification;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Keeps only the records of a relation that are still switched on.
 *
 * Assignments are not deleted in this application, they are deactivated: the
 * pivot row of a removed team member, of a replaced main user or of a revoked
 * actor stays behind with `is_active = 0`, and a user account is retired the
 * same way. A notification must speak about the people who hold the record
 * today, so every relation it reads — the ones filling a variable and the ones
 * picking the receivers — passes through here first.
 */
class ActiveRelationFilter
{
    /**
     * Drop the items whose pivot row, or whose own record, is deactivated.
     *
     * A record carrying no `is_active` column is kept: the flag is the only
     * thing read here, never the absence of one.
     *
     * @param  Collection<int, mixed>  $items
     * @return Collection<int, mixed>
     */
    public static function apply(Collection $items): Collection
    {
        return $items->filter(static fn ($item) => self::isActive($item))->values();
    }

    private static function isActive(mixed $item): bool
    {
        if (! $item instanceof Model) {
            return true;
        }

        return self::flagOf($item->pivot ?? null) !== false
            && self::flagOf($item) !== false;
    }

    /**
     * The `is_active` value of a record, or null when it carries no such column.
     */
    private static function flagOf(mixed $record): ?bool
    {
        if (! $record instanceof Model || ! array_key_exists('is_active', $record->getAttributes())) {
            return null;
        }

        $value = $record->is_active;

        return (bool) ($value instanceof BackedEnum ? $value->value : $value);
    }
}
