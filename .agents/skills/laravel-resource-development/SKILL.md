---
name: laravel-resource-development
description: Create, change, or review Laravel Eloquent API Resources and their JSON contracts in this project. Use for JsonResource classes, summary/detail projections, whenLoaded relations, translated or enum fields, permission action hints, resource sorting, and response-shape changes. Do not use for frontend models or MCP resources.
---

# Laravel API Resource Development

Build explicit API projections that match the live contract, remain query-free, and stay synchronized with their query, policy, and consumer.

## Authority and References

- Follow the root `AGENTS.md` before this skill.
- Read the root `AGENTS.md`, the nearest Resource, its model and casts, controller query, Policy, response helper, tests, and frontend consumer when the contract is externally consumed.
- Read [the canonical Resource shapes](references/resource-reference.md) before creating a Resource or materially changing its structure.
- The live repository is the secondary reference: inspect the existing resources under `app/Http/Resources`. This skill overrides them when they conflict.
- Also apply `.agents/skills/laravel-model-development/SKILL.md` for translatable model fields and `.agents/skills/laravel-enum-development/SKILL.md` for enum-backed fields. For media projections, read `.claude/skills/media.md` and inspect the owning model accessor.

## Select the Projection Deliberately

- Classify the target before copying a sibling: ordinary CRUD projection, compact reusable relation projection, aggregate summary/details projection, or specialized authentication/system payload.
- Use `Illuminate\Http\Resources\Json\JsonResource` directly for ordinary Resources. Add a module `BaseResource` only when the module genuinely needs configurable cross-module Resource class strings; do not add inheritance for visual uniformity alone.
- Use a summary/details context only when list and detail endpoints intentionally share one Resource with materially different payloads. Keep a simple CRUD Resource flat.
- `Modules/Delegation` is the newest aggregate structural reference, not a base module or a source of generic domain fields. Do not copy its statuses, registries, buttons, permissions, ownership, logs, or Strategy machinery unless the target domain independently requires them.
- Preserve an existing public key, type, nullability, omission rule, and nesting shape unless the task explicitly authorizes a contract change. Inspect every known consumer before renaming or reinterpreting a key.

## Resource Contract

- Implement `public function toArray(Request $request): array` and list API-safe keys explicitly. Do not return `parent::toArray()` or expose a model wholesale.
- Keep the transformation deterministic and side-effect free. A Resource must not execute queries, call `load()` or `loadMissing()`, mutate a model, perform state transitions, or resolve business decisions.
- Never read an Eloquent relation directly while building output. Every relation-derived value must be guarded by `whenLoaded()`, including relations configured in `$with` or otherwise expected to be eager-loaded.
- Do not add the raw `updated_at` key to a new Resource. Include `created_at` or another timestamp only when it belongs to the endpoint contract. A semantic field backed by `updated_at`, such as `last_updated_at`, is allowed only when that named business/API concept is explicitly required.
- Preserve an existing public `updated_at` key until the user explicitly authorizes its removal and affected consumers are checked; omitting it from new work must not become a silent breaking cleanup of legacy Resources.
- Order fields coherently: identity; translated values; scalar and foreign-key fields; raw enum plus display value; relations and counts; optional action/button metadata; intentional timestamps. Follow a stricter established order when preserving an existing contract.
- Treat nullable values, unloaded relations, empty objects, and empty arrays as different contracts. Keep relation keys stable through the required lazy fallback callbacks below.
- Keep pagination and the repository response envelope outside the Resource. Collection endpoints normally pass the Resource class to the established `fetchData()` flow; follow the nearest endpoint when another helper is intentionally used.

## Translatable and Enum Fields

- For normal editable CRUD Resources, expose `translation_{field}` as the current-locale value and `{field}` as `getTranslations('{field}')`. This is the repository default for `name` and `description`; preserve a deliberate legacy endpoint contract that uses different key semantics.
- Let an absent optional translation map serialize as the established empty-map form. Do not invent `['ar' => null, 'en' => null]` unless the existing consumer contract requires it.
- Expose an enum-backed value under its raw field key and the localized label under `display_{field}` using the enum's existing `resolve()` behavior. Do not replace the raw value with its display label.
- When a Resource change adds or renames a key used by table sorting, inspect the applicable `discovery.sorting` configuration and frontend sort field. `resourceSorting()` derives its response from Resource keys, so projection and sorting changes are one contract.

## Relations, Counts, and Media

- Wrap every Eloquent relation emitted or consulted by the Resource in `whenLoaded()` with a lazy closure. This applies to to-one, to-many, nested, polymorphic, pivot-facing, and default-eager-loaded relations; it is not limited to optional relations.
- A direct access such as `$this->creator`, `$this->roles`, or `$this->phoneCode?->phone_code` is allowed only inside that relation's `whenLoaded()` callback. `relationLoaded()` or a model `$with` declaration is not a substitute for guarding the output with `whenLoaded()`.
- Pass the third `whenLoaded()` argument as a plain value, matching the house shape: `whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by])`. Do not wrap it in a closure.
- For a to-one relation-shaped field with a local foreign key, the unloaded fallback is exactly `['id' => $this->foreign_key]`, written unconditionally. A null foreign key serializes as `['id' => null]`; do not guard it with a ternary, and do not expose other guessed relation data.
- For a to-many relation, the unloaded fallback is `[]`. Never query or load the relation merely to manufacture fallback IDs.
- For a scalar derived from a relation, use a type-compatible fallback such as `null` and expose the local foreign-key ID separately. Do not return an ID object under a key that is a scalar when loaded.
- Every guarded relation must have a matching `with()`, `load()`, `morphWith()`, or intentionally configured default eager load in the caller. If a nested Resource needs nested relations, eager-load the complete dotted graph before serialization.
- Use the related model's existing Resource instead of duplicating its shape. Use a compact shared Resource only when the endpoint intentionally exposes a compact relation projection.
- For a to-many relation, use the related Resource's `collection()` and the required `fn () => []` fallback. A parent that owns a picked list of children — a sector's cities, a city's locations — exposes it that way and eager-loads it on `show`, `store` and `update`; leave it unloaded on the index unless the table column exists.
- For a to-one relation, preserve `null` when the relation is loaded and absent; use the required ID-only callback only for the unloaded state.
- Obtain counts with `withCount()` or an equivalent aggregate query and serialize the count attribute. Never load a collection solely to count it in the Resource.
- Use the owning model's established media accessor or shared URL helper. Do not expose a raw storage path or construct a second URL convention unless that exact endpoint contract requires it.
- Polymorphic relations must use the owning domain's registry/resource mapping when one exists. Do not hardcode a growing model-to-Resource switch in the Resource.

## Policies and Workflow Presentation

- `Gate::allows()` may be used only to expose established client capability hints such as `actions.can_update`; this is the narrow exception to the backend-local blanket prohibition on authorization calls in Resources. It does not authorize the endpoint, which must still use its controller middleware or Policy.
- Add `actions` only when a consumer uses them. Back every flag with the same Policy ability that protects the operation, and ensure list serialization does not trigger per-row policy queries.
- Add workflow `buttons` only for an existing status/step presentation contract. Reuse its Context/Factory through the Strategy skill; never perform or select a transition inside the Resource.
- Derived-field helpers must transform already-loaded attributes and relations only, and relation-dependent helpers must be invoked from the applicable `whenLoaded()` callback. If visibility filtering, lookup, or aggregation needs a query, prepare it in a scope, query/service layer, or dedicated relation before Resource serialization.
- For new context-aware Resources, use the correctly spelled `summary` literal. Preserve an existing `summery` call chain only until it can be changed coherently across every caller.

## Verification

- Trace every output key to an attribute, accessor, cast, loaded relation, aggregate, or policy ability, and re-scan controllers and consumers after editing.
- Test exact keys and types, loaded-null versus unloaded-ID-fallback behavior, empty collection fallbacks, both translation modes/locales, raw and display enum values, every relation's unloaded/loaded behavior, summary versus details, and each permission-dependent action when applicable.
- Exercise the collection endpoint with realistic eager loading and check for hidden N+1 queries or lazy-loading violations.
- Re-scan the Resource for direct relation access outside a `whenLoaded()` callback, missing third-argument callbacks, eager fallback expressions, scalar values read through a relation, and helper methods that consume relations.
- Re-scan new and materially changed projections for an accidentally copied raw `updated_at` key. If one is retained for compatibility, verify the existing consumer and test contract rather than treating it as a default field.
- Verify `fetchData()` pagination and sorting metadata when Resource keys changed; keep response-envelope assertions at the endpoint level.
- Run the smallest affected PHPUnit tests, then apply the backend engineering Pint and EA/PhpStorm inspection gate to every changed PHP file.
