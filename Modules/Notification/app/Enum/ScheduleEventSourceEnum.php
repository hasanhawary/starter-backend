<?php

namespace Modules\Notification\app\Enum;

use Modules\Notification\app\Models\ScheduleEvent;

/**
 * The lookup the calendar's `type` column is sorted by.
 *
 * A schedule event displays the source it was raised for (`قضية`, `عقد`, ...)
 * as its type, which `source_type` stores as the source model's class name.
 * Ordering by that raw value sorts by the English class name instead of by the
 * label the listing shows, so the same CASE mapping the sortable enums use is
 * built here, with the label resolved exactly as the resource resolves it.
 *
 * It is a class rather than a PHP enum because the set of source models is
 * whatever the notification events point at, so it is read from the stored
 * values instead of being declared and kept in sync by hand.
 */
final class ScheduleEventSourceEnum
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function getList(): array
    {
        return ScheduleEvent::query()
            ->whereNotNull('source_type')
            ->distinct()
            ->pluck('source_type')
            ->map(fn (string $sourceType): array => [
                'value' => $sourceType,
                'label' => resolveTrans(class_basename($sourceType)),
            ])
            ->all();
    }
}
