<?php

namespace Modules\Form\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

/**
 * Available internal URL sources for form fields.
 *
 * Used by the Form Builder when a field's option source is `internal-url`
 * (see FormOptionsEnum::InternalUrl). Each case's value is the resolved
 * relative URL exposed directly to the Form Builder.
 */
enum InternalUrlEnum: string
{
    use EnumMethods;

    case Countries = 'countries';
    case Specializations = 'specializations';

    /**
     * Expose each case's resolved URL as the enum "extra" payload, so the
     * Form Builder receives the actual endpoint alongside the option key.
     *
     * URLs are driven by `form.internal_urls`, keeping the host application's
     * endpoints out of the module's source.
     *
     * @return array<string, array{url: string}>
     */
    public static function extra(): array
    {
        $urls = (array) config('form.internal_urls', []);

        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [
                $case->value => ['url' => (string) ($urls[$case->value] ?? '')],
            ])
            ->all();
    }
}
