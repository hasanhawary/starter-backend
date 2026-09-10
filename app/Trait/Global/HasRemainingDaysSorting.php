<?php

namespace App\Trait\Global;

trait HasRemainingDaysSorting
{
    /**
     * Sort keys a listing exposes that no column holds, mapped to the SQL
     * expression they are computed from.
     *
     * `remaining_days` mirrors the resource's calculateRemainingDays(): the
     * whole days left until the deadline, floored at zero so overdue and
     * undated records sort together at the bottom of an ascending listing.
     *
     * @return array<string, string>
     */
    public function sortableExpressions(): array
    {
        $deadline = '`'.$this->getTable().'`.`deadline`';

        return [
            'remaining_days' => "COALESCE(GREATEST(CEIL(TIMESTAMPDIFF(SECOND, NOW(), $deadline) / 86400), 0), 0)",
        ];
    }
}
