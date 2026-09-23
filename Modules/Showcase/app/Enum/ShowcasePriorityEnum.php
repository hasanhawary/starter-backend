<?php

namespace Modules\Showcase\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

/**
 * How urgently a showcase record needs attention. Ordered low to critical, which
 * is also the order `weights()` sorts listings by.
 */
enum ShowcasePriorityEnum: string
{
    use EnumMethods;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public static function default(): string
    {
        return self::Medium->value;
    }

    /**
     * Numeric weight per case, so a listing can sort by urgency rather than by
     * the alphabetical order of the stored value.
     *
     * @return array<string, int>
     */
    public static function weights(): array
    {
        return [
            self::Low->value => 1,
            self::Medium->value => 2,
            self::High->value => 3,
            self::Critical->value => 4,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            self::Low->value => '#0EA5E9',
            self::Medium->value => '#22C55E',
            self::High->value => '#F97316',
            self::Critical->value => '#DC2626',
        ];
    }
}
