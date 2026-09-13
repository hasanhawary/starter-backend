<?php

namespace Modules\Showcase\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

/**
 * Who may read a showcase record: only its owner, every authenticated user, or
 * everyone. Enforced by ShowcaseScopes::scopeVisibleTo().
 */
enum ShowcaseVisibilityEnum: string
{
    use EnumMethods;

    case Private = 'private';
    case Internal = 'internal';
    case Public = 'public';

    public static function default(): string
    {
        return self::Internal->value;
    }
}
