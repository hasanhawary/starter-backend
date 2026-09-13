<?php

namespace Modules\Showcase\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

/**
 * What an entry in a record's timeline represents. `comment`, `decision` and
 * `risk` are written by people; `status_change` is written by the workflow and
 * is the only type that fills `from_status` / `to_status`.
 *
 * Notes are polymorphic, so the same set applies to every model using the
 * HasShowcaseNotes trait.
 */
enum ShowcaseNoteTypeEnum: string
{
    use EnumMethods;

    case Comment = 'comment';
    case Decision = 'decision';
    case Risk = 'risk';
    case StatusChange = 'status_change';

    public static function default(): string
    {
        return self::Comment->value;
    }

    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            self::Comment->value => '#64748B',
            self::Decision->value => '#2563EB',
            self::Risk->value => '#DC2626',
            self::StatusChange->value => '#0EA5E9',
        ];
    }
}
