<?php

namespace Modules\Notification\Tools\Services\Notification;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Resolves notification variables against a model context.
 *
 * Variable values depend only on the variable and the model, never on the
 * template, so they can be resolved once and reused across every template.
 */
class VariableResolver
{
    /**
     * Cast types Eloquent resolves into a date object. `custom_datetime` and its
     * immutable twin are what `datetime:Y-m-d H:i:s` style casts report.
     *
     * @var array<int, string>
     */
    private const DATE_CASTS = [
        'date',
        'datetime',
        'custom_datetime',
        'immutable_date',
        'immutable_datetime',
        'immutable_custom_datetime',
        'timestamp',
    ];

    /**
     * Build a map of placeholder => value for all variables against the model.
     *
     * Both {{id}} and {{access_key}} placeholders are supported.
     *
     * @return array<string, string>
     */
    public function buildReplacements(Collection $variables, ?Model $model): array
    {
        $replacements = [];

        foreach ($variables as $variable) {
            $value = $this->resolveValue($variable, $model);

            $replacements['{{'.$variable->id.'}}'] = $value;
            $replacements['{{'.$variable->access_key.'}}'] = $value;
        }

        return $replacements;
    }

    /**
     * Resolve a variable value by navigating through its access key path.
     *
     * Returns an array of translations (locale => value).
     *
     * @return array<string, string>
     */
    public function resolveValue($variable, $context): array
    {
        if (empty($variable->access_key) || ! $context) {
            $fallback = $variable->name ?? '';

            return ['ar' => $fallback, 'en' => $fallback];
        }

        $keys = explode('.', $variable->access_key);
        $current = $context;

        // The model the last key was read from, and that key. Kept so the value
        // can be interpreted through the model's own casts instead of through a
        // hardcoded list of attribute names.
        $owner = null;
        $finalKey = null;

        foreach ($keys as $i => $key) {
            if (! $current) {
                return ['ar' => '', 'en' => ''];
            }

            // Handle collection (one-to-many). Deactivated rows — an unassigned team
            // member, a retired user — are no longer part of the record. The rest of
            // the path is read on every item, so a variable can reach through the row
            // that holds the link to the record it points at (claimants.participant.name).
            if ($current instanceof Collection) {
                $current = ActiveRelationFilter::apply($current)
                    ->pluck(implode('.', array_slice($keys, $i)))
                    ->flatten()
                    ->filter()
                    ->implode(', ');
                $owner = null;
                $finalKey = null;
                break;
            }

            // If it's the last key and the attribute is translatable on the model:
            if ($i === count($keys) - 1 && is_object($current) && method_exists($current, 'getTranslations') && method_exists($current, 'isTranslatableAttribute') && $current->isTranslatableAttribute($key)) {
                $translations = $current->getTranslations($key);

                return [
                    'ar' => $translations['ar'] ?? $translations['en'] ?? '',
                    'en' => $translations['en'] ?? $translations['ar'] ?? '',
                ];
            }

            // Handle object/model
            if (is_object($current)) {
                $owner = $current;
                $finalKey = $key;
                $current = $current->{$key} ?? null;
            } // Handle array
            elseif (is_array($current)) {
                $owner = null;
                $finalKey = $key;
                $current = $current[$key] ?? null;
            } else {
                break;
            }
        }

        // A translatable column read from a model that does not use HasTranslations
        // still holds the raw {"ar": "...", "en": "..."} payload.
        if ($translations = $this->decodeTranslations($current)) {
            return $translations;
        }

        // Status/type columns carry an enum value. The cast hands over the enum itself,
        // a plain column hands over its raw value and the variable names the enum that
        // labels it; either way the reader must see the label, not the stored value.
        if ($enumClass = $this->enumClassFor($variable, $current)) {
            return $this->enumTranslations($enumClass, $current);
        }

        // Boolean columns read as a state, never as 1/0. The wording comes from
        // the lang files, per attribute where one is defined and yes/no otherwise.
        if (is_bool($current)) {
            return $this->booleanTranslations($variable->access_key, $current);
        }

        // Handle date parsing/formatting if it's a date field
        if (is_string($current) && $this->isDateAttribute($variable, $owner, $finalKey)) {
            try {
                $current = Carbon::parse($current);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if ($current instanceof \DateTimeInterface) {
            $formatted = $current->format('Y-m-d');

            return ['ar' => $formatted, 'en' => $formatted];
        }

        if ($current instanceof Collection) {
            $formatted = ActiveRelationFilter::apply($current)->implode(', ');

            return ['ar' => $formatted, 'en' => $formatted];
        }

        $val = is_scalar($current) ? (string) $current : '';

        return ['ar' => $val, 'en' => $val];
    }

    /**
     * Whether the resolved value should be read as a date.
     *
     * The owning model already declares which of its attributes are dates, so its
     * casts are the authoritative answer and cover `start`, `end`, `due_date` and
     * anything added later without naming them here. Attributes that are dates by
     * convention but carry no cast on their model keep the suffix fallback.
     */
    private function isDateAttribute($variable, mixed $owner, ?string $finalKey): bool
    {
        if ($owner instanceof Model && $finalKey && $owner->hasCast($finalKey, self::DATE_CASTS)) {
            return true;
        }

        return str_ends_with($variable->access_key, '_date') || str_ends_with($variable->access_key, '_at');
    }

    /**
     * Label a boolean in both locales.
     *
     * `notifications.variable_boolean.<attribute>` gives an attribute its own
     * wording (finished → مكتملة / قيد التنفيذ); everything else falls back to
     * `notifications.variable_boolean.default` (نعم / لا).
     *
     * @return array<string, string>
     */
    private function booleanTranslations(string $accessKey, bool $value): array
    {
        $attribute = Str::afterLast($accessKey, '.');
        $state = $value ? 'true' : 'false';
        $locale = app()->getLocale();
        $labels = [];

        try {
            foreach (['ar', 'en'] as $lang) {
                app()->setLocale($lang);

                $key = "notifications.variable_boolean.{$attribute}.{$state}";

                if (! Lang::has($key)) {
                    $key = "notifications.variable_boolean.default.{$state}";
                }

                $labels[$lang] = (string) __($key);
            }
        } finally {
            app()->setLocale($locale);
        }

        return $labels;
    }

    /**
     * The enum that turns the resolved value into a label: the value's own class when the
     * model casts the column, otherwise the one declared on the variable.
     *
     * @return class-string|null
     */
    private function enumClassFor($variable, mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return get_class($value);
        }

        $enumClass = $variable->enum_class ?? null;

        if (! $enumClass || ! is_scalar($value) || $value === '') {
            return null;
        }

        return enum_exists($enumClass) && method_exists($enumClass, 'resolve') ? $enumClass : null;
    }

    /**
     * Label the value through its enum in both locales, using the same translation the
     * rest of the application shows for it.
     *
     * @param  class-string  $enumClass
     * @return array<string, string>
     */
    private function enumTranslations(string $enumClass, mixed $value): array
    {
        $locale = app()->getLocale();
        $labels = [];

        try {
            foreach (['ar', 'en'] as $lang) {
                app()->setLocale($lang);
                $labels[$lang] = (string) $enumClass::resolve($value);
            }
        } finally {
            app()->setLocale($locale);
        }

        return $labels;
    }

    /**
     * Decode a raw translations payload ({"ar": "...", "en": "..."}) into both locales.
     *
     * @return array<string, string>|null
     */
    private function decodeTranslations(mixed $value): ?array
    {
        if (! is_string($value) || ! str_starts_with(trim($value), '{')) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded) || ! array_intersect(['ar', 'en'], array_keys($decoded))) {
            return null;
        }

        return [
            'ar' => (string) ($decoded['ar'] ?? $decoded['en'] ?? ''),
            'en' => (string) ($decoded['en'] ?? $decoded['ar'] ?? ''),
        ];
    }
}
