---
name: laravel-form-request-development
description: Create, change, or review Laravel Form Requests and custom validation rules in this project. Use for translatable input, uploads, uniqueness, normalization, conditional validation, translated attributes, or extracting complex checks into reusable Rules.
---

# Laravel Form Request Development

Keep request validation complete, localized, reusable, and separate from authorization and domain orchestration.

## Authority and Inspection

- Follow the root `AGENTS.md`, and the owning model's contract.
- Read [the repository validation reference](references/form-request-reference.md) before creating a request or materially changing validation.
- Inspect the nearest Request, the shared `BaseFormRequest`, every existing custom Rule, translations, route binding, controller/service consumer, upload handling, model casts, and tests before writing rules.
- Search first for an existing Rule or validation helper that already expresses the required invariant. Reuse it when its semantics and response contract match; do not create a near-duplicate.

## Repository Rule Catalogue

Reach for the existing rule before writing validation by hand. These are the house answers, and a request that ignores them is inconsistent even when it works.

- **"unique"** means `App\Rules\UniqueCheck`, never Laravel's `unique` rule and never a `'unique'` entry inside `TranslatableRequired`. It understands translated values (a clash in a single language is enough), ignores the edited record through the route-bound id, and answers `433` with the trashed row in `data.item` when the duplicate is soft-deleted so the client can offer to restore it:
  `new UniqueCheck(Model::class, ModelResource::class, $this->route('model')?->id)`.
- **Uniqueness scoped to a parent** passes `wheres` and its own message key, so the same name may repeat under another parent:
  `new UniqueCheck(Department::class, DepartmentResource::class, $this->route('department')?->id, wheres: ['location_id' => $this->input('location_id')], messageKey: 'validation.already_exists_in_location')`.
  Resolve the parent as "the submitted one, or the record's current one" when the field is optional on update; reading `input()` alone silently checks the wrong bucket. Add the matching `already_exists_in_{parent}` key to both language files.
- **"exists"** means `App\Rules\ModelExists`, not `Rule::exists(...)->withoutTrashed()`. It queries through the model, so soft deletes and model configuration are honored automatically: `new ModelExists(Location::class)`, with `wheres` for a chained check such as "a city inside the sent sector".
- **Translated text** uses `TranslatableRequired` / `TranslatableNullable` as described below.
- **Choosing a child for its parent** — `city_ids` on a sector, `location_ids` on a city — validates each id with a dedicated rule that reuses the child's `availableFor{Parent}` scope and takes the edited parent from the route: `new CityAvailableForSector($this->route('sector')?->id)`. The picker lookup and the rule must run the same scope so a form can never offer a value the request will reject.
- Name every list field and its members in `attributes()`, including the `*` entry: `'city_ids' => __('attributes.city_ids')` and `'city_ids.*' => __('attributes.city_id')`.

## Request Responsibilities

- Put payload shape, scalar constraints, conditional presence, normalization, and validation composition in the Form Request.
- Use the nearest applicable `BaseFormRequest` so empty-value normalization and the established JSON error envelope are preserved. Do not duplicate the base class inside a module without a demonstrated module-specific contract.
- Return only validated data to controllers and services. A service must not read the global request, and a Request must not execute writes, state transitions, notifications, or other domain orchestration.
- Keep record-specific authorization in Policies/Gates. Use `authorize()` only when the request itself has a clear, non-duplicated authorization responsibility.
- Resolve route-bound create/update context explicitly and keep route parameter names aligned with route definitions.

## Translatable Fields

- When the model treats an input as translatable, validate the field as an array and use `App\Rules\TranslatableRequired` when it is required or `App\Rules\TranslatableNullable` when it is optional.
- Do not replace those rules with manually repeated `name.ar`, `name.en`, `description.ar`, or `description.en` rules unless an established endpoint intentionally has a different contract.
- Pass the correct table, inner scalar rules, and route parameter to the translatable Rule. Derive required locales from `config/lang.php`; do not hardcode a competing language policy.
- Apply the model skill's default: `name` and `description` are translatable unless the user explicitly says otherwise.

## Files and Images

- Validate the container and each uploaded item separately for multi-file payloads.
- Prefer a complete existing reusable upload rule or helper when it matches the required contract. A MIME-only helper such as `vImage()` is not a complete upload contract by itself; compose the correct presence/nullability, uploaded-file validation, allowed types, size limit, and `NotEmptyFile` protection.
- For general files, prefer the established fluent `Illuminate\Validation\Rules\File::types(...)->max(...)` shape where nearby code uses it, plus `NotEmptyFile` when empty uploads must be rejected.
- For images, inspect whether the target needs the existing `vImage()` contract, Laravel's image rule, SVG support, dimensions, or a stricter custom Rule. Do not silently broaden formats or size limits.
- If the same non-trivial image or file contract is repeated across endpoints, extract or extend one focused reusable Rule/ruleset. Do not introduce an abstraction for a single simple field.

## Custom Rules and Uniqueness

- Reach for Laravel's own rule before writing a class. A single-field date, size, or comparison check is `after_or_equal:today`, `min:1`, `required_if:...` — not a `ValidationRule`. Laravel translates the relative keywords, so `after_or_equal:today` already reads correctly in Arabic.
- Extract a dedicated Rule when validation has several branches, multiple related-record checks, reusable domain terminology, or enough query logic to obscure the Request. Keep small one-field conditions inline.
- A poor default message is not a reason to write a Rule. When one field carries two rules of the same name — `after_or_equal:today` next to `after_or_equal:start_date` — they share one message key, so write a single message covering both under `validation.custom.{field}.{rule}` in both language files.
- A custom Rule validates one invariant and produces localized failures. It must not mutate state or hide authorization.
- Before using Laravel's raw `unique` rule, inspect `UniqueCheck`, `UniqueWithTrashed`, and translatable uniqueness support. Select the existing rule whose live-record, soft-deleted-record, translated-value, ignore-id, scoped-where, response-code, and restore-payload behavior matches the endpoint.
- Do not issue duplicate database queries from several rules when one cohesive existing Rule can validate the invariant.

## Localization and Verification

- Implement `attributes()` using the owning translation namespace and add every client-visible field, including nested fields where useful, to both Arabic and English attribute files.
- Add localized custom messages only when the default translated Laravel message does not describe the constraint correctly. Keep placeholder keys and Arabic/English structures aligned.
- Test valid input, each meaningful invalid boundary, create/update differences, translatable required/nullable locales, uniqueness including trashed rows where applicable, upload type/size/empty cases, conditional branches, normalized values, and the response envelope.
- Finish with the backend PHP quality gate: focused tests, Pint on only authorized changed PHP files, and EA/PhpStorm inspections when available.
