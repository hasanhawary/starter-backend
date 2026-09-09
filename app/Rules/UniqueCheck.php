<?php

namespace App\Rules;

use App\Exceptions\ModelAlreadyExistsException;
use App\Services\Global\QueryHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniqueCheck implements ValidationRule
{
    /**
     * @param  class-string  $modelClass
     * @param  class-string  $resourceClass
     * @param  array<string, mixed>  $wheres  Additional where conditions to scope uniqueness (e.g. ['court_id' => 1]).
     * @param  string|null  $messageKey  Translation key of the duplicate message, so a scoped uniqueness can say
     *                                   where the clash happened (e.g. `validation.already_exists_in_project`).
     * @param  string|null  $attributeKey  Attribute name used to resolve the human label, when the generic one
     *                                     (e.g. `name`) is too vague for the context (e.g. `project_task_name`).
     */
    public function __construct(
        protected string $modelClass,
        protected string $resourceClass,
        protected int|string|null $ignoreId = null,
        protected array $wheres = [],
        protected ?string $messageKey = null,
        protected ?string $attributeKey = null,
    ) {}

    /**
     * A live duplicate is a plain validation failure (422). A soft-deleted duplicate
     * is reported as 433 along with the trashed item, so the client can offer to restore it.
     *
     * A translatable value collides as soon as a single language is already taken —
     * the languages are never required to match together.
     *
     * @throws ModelAlreadyExistsException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->isBlank($value)) {
            return;
        }

        $model = $this->buildQuery($attribute, $value)->first();

        if (! $model) {
            return;
        }

        if (! $this->isTrashed($model)) {
            $this->reportDuplicate($attribute, $value, $model, $fail);

            return;
        }

        throw new ModelAlreadyExistsException([
            'item' => new $this->resourceClass($model),
            'type' => $this->modelClass,
        ], __('validation.already_exists_deleted'), 433);
    }

    /**
     * Report the live duplicate. For a translatable value, pinpoint the exact
     * language whose value is already taken (e.g. "الاسم العربي" vs "الاسم الانجليزي")
     * instead of blaming the whole attribute, so the client knows which field to fix.
     */
    protected function reportDuplicate(string $attribute, mixed $value, Model $model, Closure $fail): void
    {
        $languages = is_array($value)
            ? $this->collidingLanguages($model, $attribute, $value)
            : [];

        $messageKey = $this->messageKey ?? 'validation.already_exists';

        if ($languages === []) {
            $fail(__($messageKey, ['attribute' => $this->translatedAttribute($attribute)]));

            return;
        }

        foreach ($languages as $language) {
            $fail(__($messageKey, [
                'attribute' => $this->translatedLanguageAttribute($attribute, $language),
            ]));
        }
    }

    /**
     * The languages whose submitted value equals the value already stored on the
     * matched record (case-insensitively), i.e. the ones actually causing the clash.
     *
     * @param  array<string, mixed>  $value
     * @return list<string>
     */
    protected function collidingLanguages(Model $model, string $attribute, array $value): array
    {
        $stored = $this->storedTranslations($model, $attribute);
        $languages = [];

        foreach (config('app.supported_languages', ['ar', 'en']) as $language) {
            $submitted = $value[$language] ?? null;

            if ($submitted === null || $submitted === '') {
                continue;
            }

            $existing = $stored[$language] ?? null;

            if ($existing !== null && mb_strtolower((string) $existing) === mb_strtolower((string) $submitted)) {
                $languages[] = $language;
            }
        }

        return $languages;
    }

    /**
     * The per-language translations stored on the matched record.
     *
     * @return array<string, mixed>
     */
    protected function storedTranslations(Model $model, string $attribute): array
    {
        $raw = $model->getRawOriginal($attribute) ?? $model->getAttributeValue($attribute);

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function buildQuery(string $attribute, mixed $value): Builder
    {
        $query = $this->modelClass::query();

        if ($this->supportsSoftDeletes()) {
            /** Live rows win over trashed ones, so a real conflict is never masked by a deleted duplicate. */
            $deletedAt = $query->getModel()->getQualifiedDeletedAtColumn();

            $query->withTrashed()->orderByRaw("$deletedAt is null desc");
        }

        $query->when(
            filled($this->ignoreId),
            fn (Builder $q) => $q->whereKeyNot($this->ignoreId)
        );

        $query->when(
            $this->wheres !== [],
            fn (Builder $q) => $q->where($this->wheres)
        );

        $query->where(function (Builder $q) use ($attribute, $value) {
            if (is_array($value)) {
                QueryHelper::applyJsonSearch($q, $attribute, $value, true);
            } else {
                $q->where($attribute, $value);
            }
        });

        return $query;
    }

    /**
     * Whether there is nothing left to compare. A translatable value carrying no
     * filled language would otherwise leave the uniqueness query unconstrained
     * and match every row.
     */
    protected function isBlank(mixed $value): bool
    {
        return is_array($value)
            ? array_filter($value, static fn ($item) => $item !== null && $item !== '') === []
            : blank($value);
    }

    protected function supportsSoftDeletes(): bool
    {
        return in_array(
            SoftDeletes::class,
            class_uses_recursive($this->modelClass),
            true
        );
    }

    /**
     * Whether the matched record is soft-deleted.
     */
    protected function isTrashed(Model $model): bool
    {
        return $this->supportsSoftDeletes() && $model->trashed();
    }

    /**
     * Resolve the human, localized label for the given attribute, falling back
     * to the raw attribute name when no translation is defined.
     */
    protected function translatedAttribute(string $attribute): string
    {
        $attribute = $this->attributeKey ?? $attribute;

        foreach (["attributes.$attribute", "validation.attributes.$attribute"] as $key) {
            $label = trans($key);

            if (is_array($label)) {
                $label = $label[app()->getLocale()] ?? reset($label);
            }

            if (is_string($label) && $label !== $key) {
                return $label;
            }
        }

        return $attribute;
    }

    /**
     * Resolve the localized label for a single language of a translatable attribute,
     * following the `{attribute}_{language}` convention (e.g. `name_ar`). Falls back
     * to the generic attribute label when no per-language label is defined.
     */
    protected function translatedLanguageAttribute(string $attribute, string $language): string
    {
        $name = ($this->attributeKey ?? $attribute)."_{$language}";

        foreach (["attributes.$name", "validation.attributes.$name"] as $key) {
            $label = trans($key);

            if (is_string($label) && $label !== $key) {
                return $label;
            }
        }

        return $this->translatedAttribute($attribute);
    }
}
