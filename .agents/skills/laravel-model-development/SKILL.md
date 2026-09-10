---
name: laravel-model-development
description: Create, change, or review Eloquent models and their persistence contract in this project. Use whenever a model is created or structurally changed, and whenever name or description fields are added; those fields are translatable by default unless the user explicitly says otherwise.
---

# Laravel Model Development

Build models from the live repository conventions, including the complete schema, validation, serialization, factory, and test impact of each modeled field.

## Authority and References

- Follow the root `AGENTS.md` before this skill.
- Read the root `AGENTS.md`, the nearest live models, their migrations, requests, resources, factories, tests, and consumers before editing.
- Read [the canonical model example](references/model-example.md) before creating a model or normalizing its structure.
- For translated fields, also read `.claude/skills/translatable.md` and its required reference.
- For enum-backed fields, also apply `.agents/skills/laravel-enum-development/SKILL.md`.
- For who owns a write and where the transaction boundary sits, apply `.agents/skills/laravel-controller-development/SKILL.md`. This skill owns the relation-sync method itself; that skill owns the caller.
- For every new persisted business model, and whenever activity logging is changed or reviewed, apply `App\Trait\Global\LogsActivityOptions` and follow the activity-log rules in `.agents/skills/laravel-best-practices/SKILL.md`.
- Live project code outranks generic or stale templates. This repository has no `App\Models\BaseModel`; new normal models extend `Illuminate\Database\Eloquent\Model` unless their domain requires another existing base class.

## Default Translatable Contract

- Treat attributes named exactly `name` or `description` as translatable unless the user explicitly marks that attribute non-translatable.
- Apply that default end to end; adding `HasTranslations` and `$translatable` to the model alone is incomplete.
- Store translatable attributes in `mediumText` columns. Preserve required versus nullable behavior from the domain contract; translation does not decide nullability.
- Add `Spatie\Translatable\HasTranslations` and list every translated attribute exactly once in public `array $translatable`, following the canonical property order.
- Accept locale-keyed arrays in Form Requests. Use `TranslatableRequired` for required translated fields and `TranslatableNullable` for optional translated fields, after inspecting their current implementations.
- Derive required locales from `config/lang.php`; do not embed a different locale policy in a model or request.
- In normal CRUD Resources, follow the current contract: `translation_name` or `translation_description` contains the current-locale value, while `name` or `description` contains `getTranslations(...)`. Preserve an established endpoint contract when it deliberately differs.
- Factories, seeders, and tests must supply locale-keyed values for translated attributes rather than plain strings.
- Filters and uniqueness checks must query the translated JSON representation using the existing project helpers and rules.

## Activity Logging Contract

- Every new first-party Eloquent model that persists mutable business data must import and use `App\Trait\Global\LogsActivityOptions`. Treat activity logging as part of the model's default persistence contract, not an optional follow-up.
- The shared trait already boots Spatie's `LogsActivity` and defines `getActivitylogOptions()` with `logAll()`, `logOnlyDirty()`, the class-based log name, and `dontSubmitEmptyLogs()`. Do not add `Spatie\Activitylog\Traits\LogsActivity` or duplicate `getActivitylogOptions()` when those defaults are sufficient.
- Inspect attributes before enabling the default. Exclude passwords, tokens, OTP data, credentials, secrets, large payloads, and other inappropriate audit values through the shared trait's `logExceptAttributes` contract.
- Use Spatie's trait and a model-local `getActivitylogOptions()` only when the model needs a genuinely different allowlist, event set, or logging contract that the shared trait cannot express. Preserve dirty-only logging, empty-log suppression, the log name, and sensitive-field exclusions explicitly.
- Existing models that use Spatie's trait directly are legacy or custom contracts, not templates for a new normal model. Do not migrate them incidentally because changing the selected attributes can change the audit contract.
- Omit activity logging only for a demonstrated exception such as a read-only projection, package-owned or ephemeral model, or a model deliberately covered by another authoritative domain audit log. Confirm the exception from analogous code or an explicit requirement; do not infer it from the absence of a UI.

## Model Structure

- Start from the canonical example, then inspect the nearest domain model. Preserve the example's ordering and exact section-comment labels for sections that the model actually needs.
- Order class content as follows: traits; permission metadata; `$translatable`; `$fillable`; optional model configuration such as `$hidden`, `$with`, `$attributes`, and casts; activity-log customization; accessors, mutators, and scopes; relations; relation synchronization methods; deletion guards and other narrowly scoped helpers.
- For normal CRUD models, use the established shared traits in this order: `CreatedByObserver`, `HasFactory`, `HasTranslations`, `LogsActivityOptions`, `SoftDeletes`. `LogsActivityOptions` is required by the activity logging contract above; include each remaining trait only when supported by the requested lifecycle.
- Pair `HasFactory` with the existing generic PHPDoc form and a real factory. Do not generate a factory annotation without the factory.
- Set `$inPermission`, `$basicOperations`, and `$specialOperations` from the exposed operations. Internal, pivot, and log models must not gain permissions merely by copying the example.
- Keep `$translatable` and every client-writable field in `$fillable`; never use `$guarded = []`.
- Add casts using the model's existing `$casts` property or `casts()` method style. Do not keep both forms in one model.
- Use the exact project section labels `Casts && Set Custom Attributes`, `Activity log methods`, and `Relations methods` when those sections exist. Omit empty sections.
- Use typed Eloquent relation return values and keep the `creator()` relation with other relations when `created_by` is present.
- Use `preventDeleteRelations()` only when deletion must be blocked by demonstrated related records; return the same array shape used by the nearest feature.
- Do not copy relationships, permission operations, traits, casts, or helpers that the model does not need.

## Relation Synchronization Methods

- The model that owns a relation owns its writes. Every `syncData`, `sync()`, attach/detach, replace-children, file, or participant synchronization lives in a named method on that model, never inline in a controller, request, or resource.
- Follow the live convention in `app/Models/Cause.php`: `syncFiles()` and `syncParticipants()`. Name each method `sync{Relation}()`, accept already-validated data with a safe default such as `array $items = []`, guard empty input, and return `void` unless a caller demonstrably needs a value.
- Keep the method self-contained for its own relation: unpack the payload, replace or merge the rows, and include that relation's own media storage when the inspected convention already does so. It must not authorize, validate, read the global request, dispatch notifications, or return an HTTP response.
- Do not open `DB::transaction()` inside the model method. The controller action owns the boundary in every case — it wraps one or two write lines directly, and it also wraps the sequence of service calls in a service-backed module. Services in this backend do not open transactions.
- Keep one sync path per relation. Reuse the existing method instead of adding a second variant, and when several models share the same rule put it in the established shared trait such as `Modules/IntellectualProperty/app/Traits/SyncIntellectualPropertyFiles.php`.
- A sync method is a persistence helper, not a service. When the operation grows into coordinated writes across several models, state transitions, notifications, or after-commit work, that orchestration belongs to a domain service that calls these sync methods; do not grow the model into a service.
- Place these methods after the relations, with the model's other narrowly scoped helpers. Do not invent a new section comment label for them.

## Parent and Child Selection

The space tree — sector -> city -> location -> department — is the live example of a child that is chosen from its parent's form. Follow it whenever a parent owns a list of children.

- The child's foreign key is **nullable**: a row waits without a parent until one takes it. Add an `ALTER` migration for an already-deployed table; editing the original `create` migration changes nothing that is already running.
- The child model carries the picker scope, named `scopeAvailableFor{Parent}(Builder $builder, int|string|null $parentId = null)`: rows whose parent id is `null`, plus the rows the given parent already owns so an edit form keeps its current selection.
- The parent model carries `sync{Children}(array $ids = [])`: guard the empty input, release the rows it owns that are missing from the list (`parent_id = null`), then claim the listed ones. Follow `Sector::syncCities()` and `City::syncLocations()`.
- The Form Request validates each id with the rule that reuses that same scope, so the lookup and the validation can never disagree.
- Never release a child the domain creates automatically. `Location::syncDepartments()` keeps the default legal department its observer creates, and a sync that would orphan such a row is a bug, not a preference.
- Expose the children on the parent's Resource through `whenLoaded(..., fn () => []).`

## Consistency and Verification

- Search the table schema, model, request, resource, factory, seeder, filters, services, routes, tests, and frontend consumers for every added or changed attribute.
- Confirm `name` and `description` have not been implemented as plain strings unless the user explicitly requested that exception.
- Confirm the activity-logging trait, sensitive exclusions, trait order, property order, section comments, casts, relationships, and permission metadata match the canonical example and nearest domain model.
- For a new logged model, test create and dirty-update activity, empty-update suppression, authenticated/system causers as applicable, and the absence of excluded sensitive values. Cover delete and restore events when the lifecycle supports them.
- Test translation persistence and retrieval, required and optional locales, validation failure, Resource shape, mass assignment, casts, relationships, and deletion behavior only where affected.
- For each relation sync method, test the full replace, the empty-input guard, and the relation state after the caller's transaction rolls back. Confirm no controller or service duplicates that relation write.
- Run the smallest relevant PHPUnit tests and the backend-required formatter, then review the full diff for conflicting or duplicated model definitions.
