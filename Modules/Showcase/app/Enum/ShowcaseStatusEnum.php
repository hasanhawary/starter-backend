<?php

namespace Modules\Showcase\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

/**
 * Lifecycle of a showcase record. Stored as a string column; `draft` is the
 * state every record starts in and `published` is the only state that puts a
 * value in `published_at`.
 */
enum ShowcaseStatusEnum: string
{
    use EnumMethods;

    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Archived = 'archived';

    public static function default(): string
    {
        return self::Draft->value;
    }

    /**
     * The states a record may still be edited from.
     *
     * @return array<int, string>
     */
    public static function editableValues(): array
    {
        return [self::Draft->value, self::InReview->value];
    }

    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            self::Draft->value => '#94A3B8',
            self::InReview->value => '#F59E0B',
            self::Published->value => '#16A34A',
            self::Archived->value => '#64748B',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function icons(): array
    {
        return [
            self::Draft->value => 'mdi-file-document-edit-outline',
            self::InReview->value => 'mdi-clock-outline',
            self::Published->value => 'mdi-check-decagram-outline',
            self::Archived->value => 'mdi-archive-outline',
        ];
    }
}
