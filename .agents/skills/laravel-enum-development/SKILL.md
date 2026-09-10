---
name: laravel-enum-development
description: Create, change, or review enum-backed Laravel fields in this project whenever the user identifies a database column as an enum. Enforces string database storage and the existing app/Enum, cast, validation, lookup, translation, and API conventions. Do not use for unconstrained free-form strings.
---

# Laravel Enum Development

When the user calls a column an enum, treat that as a closed application-level value set and apply this workflow to the complete affected backend contract.

## Authority and References

- Follow the root `AGENTS.md` before this skill.
- Read the root `AGENTS.md`, the nearest enums under `app/Enum`, and the model, migration, request, resource, tests, and consumers for the affected field before editing.
- When the owning Eloquent model is created or structurally changed, also apply `.agents/skills/laravel-model-development/SKILL.md`; this skill remains authoritative only for enum-specific decisions.
- When a schema change is involved, also read `.claude/skills/migrations.md`.
- Reuse an existing enum that represents the same domain concept. Do not create a second source of truth for an existing value set.

## Required Storage Contract

- Store every user-designated enum column as a database `string`; never use a database-native `enum` column.
- For a new field with no explicitly requested or established backing contract, create a string-backed PHP enum with stable snake_case values.
- Preserve an existing integer-backed enum or external numeric contract when compatibility requires it, but keep its database column as `string`.
- Choose nullability, length, default, index, and migration strategy from the actual field lifecycle and query paths. Do not add constraints or indexes speculatively.
- Express defaults through an enum case value or the enum's `default()` method. Do not duplicate raw default literals in the migration.
- Use `comment(EnumClass::commentFormat())` when the surrounding migrations use enum comments; do not introduce a different documentation mechanism for one field.
- Do not rewrite an already-shared migration. Add a reversible migration for an existing table.

## Laravel Enum Shape

- Place shared enums in `app/Enum/Global`; otherwise mirror the owning domain under `app/Enum/{Domain}`.
- Name enums `PascalCaseEnum`, cases in `PascalCase`, and new string-backed values in stable `snake_case`.
- Use the same `EnumMethods` trait as the nearest domain enum. The project default is `HasanHawary\LookupManager\Trait\EnumMethods`; some Cause-domain enums intentionally use `App\Trait\Global\EnumMethods`.
- Add `default()`, `keyName()`, `extra()`, icons, colors, or domain helper methods only when a current migration, lookup, display, or business rule needs them.
- Keep business workflows out of the enum unless the behavior is intrinsic to the value set and matches an established neighboring pattern.

Use this as the default shape when no closer domain convention differs:

```php
<?php

namespace App\Enum\Domain;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ExampleStatusEnum: string
{
    use EnumMethods;

    case Pending = 'pending';
    case Approved = 'approved';

    public static function default(): string
    {
        return self::Pending->value;
    }
}
```

The corresponding migration column remains a string:

```php
$table->string('status')
    ->default(ExampleStatusEnum::default())
    ->comment(ExampleStatusEnum::commentFormat());
```

## Complete Integration

- Add the enum cast to the owning Eloquent model using that model's existing `$casts` property or `casts()` method style.
- Validate writes with the enum validation rule used by the nearest request. The dominant project convention is `HasanHawary\LookupManager\Rules\Enum`; use Laravel's `Rule::enum()` only where that subsystem already establishes it.
- Keep the raw stored value stable in public contracts. Add or preserve a separate translated display field only when neighboring Resources expose one.
- When lookup or display labels are required, add matching keys to both `lang/ar/enums.php` and `lang/en/enums.php`. The default translation group is derived from the enum class name; add `keyName()` only when a different existing key is required.
- Never translate or localize the stored enum value itself.
- Search services, filters, factories, seeders, tests, Resources, frontend consumers, and raw string comparisons for the affected field. Replace literals only where required for the requested change; do not turn the task into an unrelated cleanup.

## Verification

- Confirm the migration uses `string` and contains no database-native enum definition for the field.
- Verify valid values persist and cast to the PHP enum, invalid input is rejected, and defaults/nullability behave as designed.
- Verify lookup and API representations when they are affected, including Arabic and English labels where applicable.
- Re-scan the enum name, column name, values, casts, validation, and consumers after editing to detect conflicting definitions or missed integration points.
- Run the smallest relevant tests and the backend-required formatter before completion.
