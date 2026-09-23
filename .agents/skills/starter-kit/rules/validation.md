# Validation, Form Requests, And Translated Fields

Use this rule when creating or modifying Form Requests, validation rules, payload normalization, uploads, service input contracts, translated names, or localized validation messages.

## Validation Boundaries

- Extend `BaseFormRequest`; use Form Requests rather than inline controller validation.
- Controllers must never call `$request->validate()`, `Validator::make()`, or throw `ValidationException` to report request-field errors. They must not reproduce Form Request rules with manual presence, type, format, uniqueness, existence, or cross-field `if` checks.
- Keep controller conditionals only for authorization, HTTP orchestration, and persisted-state invariants that are not request validation. When a conditional can produce a field-level 422 response before mutation, express it in the Form Request through declarative rules or a focused custom rule.
- Keep declarative field checks in `rules()`. Do not use Form Request `after()` callbacks.
- Put cross-field or domain request validation in focused `ValidationRule` / `DataAwareRule` classes: one cohesive rule per concern, not many tiny classes.
- Services contain orchestration and business mutations only. Never put request/input/cross-field validation in services.
- Status strategy handlers follow the same boundary: put target-specific field and transition-prerequisite rules in the Form Request through the strategy's `validateRules()` contract, never as imperative checks or `ValidationException` throws inside `handle()`.
- Controllers pass validated data for writes. Existing service signatures may accept the validated Form Request, but services and their helpers consume only `$request->validated()` fields; never use `$request->all()` or raw input.
- Database constraints and locks may enforce integrity, but must not recreate request validation inside services.
- Prefer array notation for new rules unless nearby code consistently uses string rules.
- Use `$this->route('{model}')?->getKey()` or sibling route access style to ignore the current model on update.
- Use `Rule::unique(...)->ignore(...)->withoutTrashed()` for soft-delete-aware uniqueness and `Rule::exists(...)->withoutTrashed()` for soft-deletable foreign keys.
- Reuse existing rules in `app/Rules/`, subject to the explicit translated-name convention below.
- Validate uploads by MIME type, extension, and size; see [security and authorization](security-auth.md) for storage and sensitive-data handling.
- Follow [security and authorization](security-auth.md) for authorization placement, including the sibling-code exception for Form Request `authorize()`.

## Arabic-First Translated-Name Convention

Implement translated-name validation explicitly in each Form Request. This pattern is based on the stable request structure reviewed from `fikrah-gadd-v2` and adapted to this project's Arabic-first requirements.

### Workflow

1. Inspect the closest existing Form Request and its endpoint tests.
2. Define the parent translated field as an array.
3. Define Arabic and English rules explicitly instead of hiding language behavior inside a custom rule.
4. Add an explicit `messages()` method using `validation.*` templates and `validation.attributes.*` labels.
5. Add missing attribute aliases to `lang/ar/validation.php` and `lang/en/validation.php`.
6. Test the rules, exact error key, and exact localized message.

### Locale Rules

- Arabic is the only required translation.
- On create, require Arabic and make `name->ar` unique among non-deleted records.
- On partial update, use `required_with:name` and ignore the route-bound model in the Arabic uniqueness rule.
- English is optional and must not have `required` or `string`: `name.en => ['nullable', 'max:191']`.
- Apply the same rules to translated `display_name` and nested translated names.
- Keep parent rules such as `required`, `sometimes`, `array`, and existing uniqueness rules.
- Preserve existing duplicate-response behavior such as `UniqueCheck` and HTTP 433.
- Do not hardcode messages in request classes. Build them from `validation.*` and `validation.attributes.*` translations.
- Use explicit PHPUnit assertions and do not add a snapshot package; see [testing and verification](review-debug-refactor.md#testing).

### Canonical Create Request

Use this as the canonical request structure:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'array'],
        'name.ar' => [
            'required',
            'string',
            'max:191',
            Rule::unique('models', 'name->ar')
                ->whereNull('deleted_at')
                ->ignore($this->route('model')),
        ],
        'name.en' => ['nullable', 'max:191'],
    ];
}

public function messages(): array
{
    return [
        'name.required' => __('validation.required', [
            'attribute' => __('validation.attributes.name'),
        ]),
        'name.array' => __('validation.array', [
            'attribute' => __('validation.attributes.name'),
        ]),
        'name.ar.required' => __('validation.required', [
            'attribute' => __('validation.attributes.name_ar'),
        ]),
        'name.ar.string' => __('validation.string', [
            'attribute' => __('validation.attributes.name_ar'),
        ]),
        'name.ar.max' => __('validation.max.string', [
            'attribute' => __('validation.attributes.name_ar'),
            'max' => 191,
        ]),
        'name.ar.unique' => __('validation.unique', [
            'attribute' => __('validation.attributes.name_ar'),
        ]),
        'name.en.max' => __('validation.max.string', [
            'attribute' => __('validation.attributes.name_en'),
            'max' => 191,
        ]),
    ];
}
```

Required validation attribute aliases:

```php
'name_ar' => 'Name in Arabic',
'name_en' => 'Name in English',
'display_name_ar' => 'Display Name in Arabic',
'display_name_en' => 'Display Name in English',
```

### Translated Validation Verification

Tests must verify:

```text
Arabic omitted       -> fails on name.ar
Duplicate Arabic     -> errors key name.ar; failed rule unique
English omitted      -> passes
English non-string within max:191 -> passes
Partial update name omitted -> passes
Localized message    -> matches the explicit messages() output
```

Use `assertSame()` for the message map or exact message text so changes are deliberate and reviewable.

### Partial Update Variation

Use `sometimes` on the parent and replace the Arabic `required` rule with `required_with:name`. Keep the remaining Arabic rules and `name.en` unchanged; the uniqueness rule ignores the route-bound model. Match the explicit message key to the actual rule:

```php
// rules()
'name' => ['sometimes', 'array'],
'name.ar' => [
    'required_with:name',
    'string',
    'max:191',
    Rule::unique('models', 'name->ar')
        ->whereNull('deleted_at')
        ->ignore($this->route('model')),
],
'name.en' => ['nullable', 'max:191'],

// messages(): use this instead of name.ar.required for the partial-update rule.
'name.ar.required_with' => __('validation.required_with', [
    'attribute' => __('validation.attributes.name_ar'),
    'values' => __('validation.attributes.name'),
]),
```

The English attribute aliases shown above belong in the English validation file; provide their Arabic translations in the Arabic file. Adapt aliases and message keys for `display_name` and nested translated names too. A rule-specific message key such as `name.ar.unique` maps to the response error field `name.ar`.

## Historical Translatable/Media CRUD Example

This preserved source example illustrates the existing `UniqueCheck`, media, soft-delete-aware uniqueness, and configured-language `TranslatableRequired` APIs. `TranslatableRequired` can require translations for configured languages; it does **not** define the Arabic-first convention for new or changed translated-name validation. Use the explicit locale rules above for `name`, `display_name`, and nested translated names, preserving duplicate-response behavior. Inspect existing contracts and tests before changing legacy fields such as `description`.

```php
<?php

namespace App\Http\Requests\DataEntry;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\DataEntry\ProductResource;
use App\Models\Product;
use App\Rules\TranslatableRequired;
use App\Rules\UniqueCheck;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProductRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                // UniqueCheck supports translated/resource-aware duplicate detection.
                new UniqueCheck(
                    Product::class,
                    ProductResource::class,
                    $this->route('product')?->id
                ),
                new TranslatableRequired('products', ['string', 'max:191'], 'product'),
            ],

            'description' => [
                'required',
                'array',
                new TranslatableRequired('products', ['string', 'max:191'], 'product'),
            ],

            'code' => [
                'required',
                Rule::unique('products', 'code')
                    ->withoutTrashed()
                    ->ignore($this->route('product')),
            ],
            'image' => ['sometimes', 'nullable', File::image()->max(20048)], // 20MB max
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
```

## Payload Normalization

Let `BaseFormRequest` handle empty-to-null conversion; do not duplicate it. Use `prepareForValidation()` only when the frontend/API payload shape needs further normalization.

```php
protected function prepareForValidation(): void
{
    parent::prepareForValidation();

    $phone = $this->input('phone');

    if (is_array($phone)) {
        $this->merge([
            'phone' => $phone['phone'] ?? null,
            'phone_code_id' => $phone['phone_code_id'] ?? null,
        ]);
    }

    $roles = $this->input('roles', []);
    if (! is_array($roles)) {
        $this->merge(['roles' => Arr::wrap($roles)]);
    }
}
```

## Known Custom Rules

- `StrongPassword`: password complexity.
- `UniqueCheck`: model uniqueness for translated/resource-aware values.
- `ValidLength`: length based on reference model columns.
- `CheckSamePassword`: new password must differ from current.
- `TranslatableRequired`: required translations for configured languages.
- `TranslatableNullable`: optional translations for configured languages.
- `TotalFileSize`: combined upload size limit.
