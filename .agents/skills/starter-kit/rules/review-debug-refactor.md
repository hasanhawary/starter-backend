# Review, Debugging, Refactoring, Anti-Patterns, And Quality Checklist

Use this rule when reviewing code, diagnosing bugs, refactoring, or doing final quality checks.

## Debugging Flow

1. Reproduce the exact symptom.
2. Classify the layer: route, auth, validation, controller, service, model, DB, queue, notification, export, or external service.
3. Read Laravel logs in `storage/logs/` for exceptions.
4. Check routes and middleware for 404/401/403.
5. Check request rules and payload for 422.
6. Check policies/permissions for 403.
7. Check migrations, casts, fillable, soft-deleted records, and relation names for DB/model errors.
8. Trace controller, service, model, resource, and response helper.
9. Apply one minimal fix.
10. Verify with focused tests or direct reproduction.
11. Remove debug code before finishing.

## Common Backend Starts

- 404: route prefix, route model binding name, module route loading.
- 401: Sanctum token, guard, middleware, token expiry.
- 403: `Gate::authorize()`, policy method, permission name, root/current-user protection.
- 422: Form Request rules vs payload key/type.
- 500: logs, missing class/namespace/import, DB schema mismatch, enum/cast issue.
- Empty list: Pipeline filters, `related()` scope, soft deletes, pagination, eager loading, auth ownership scope.

## Code Review Behavior

Findings come first and are ordered by severity. Include file and line references when possible.

Check:

- Base classes and folder placement.
- Response envelope and translation use.
- Form Request validation and `$request->validated()`.
- Authorization through `PermissionMiddleware` and/or `Gate`/Policy, matching the current domain pattern.
- Service boundaries.
- Fillable, casts, relations, and sensitive hidden fields.
- Pipeline filters and pagination.
- Transactions and after-commit side effects.
- N+1 risks and unbounded queries.
- SQL injection, hardcoded secrets, insecure uploads, over-broad mass assignment.
- Tests for happy path, validation, auth, and authorization.
- Debug artifacts and unused code.

Severity:

- Critical: security hole, broken response shape, missing auth, raw SQL with user input, hardcoded secret, wrong base class.
- High: missing authorization, unvalidated writes, `$request->all()`, missing transaction for multi-write, N+1 in common path.
- Medium: missing test, missing index, inconsistent naming, missing translation, cache invalidation issue.
- Low: maintainability or small consistency improvements.

## Refactoring Rules

- Refactor only what is necessary for the task.
- Preserve public API, route names, request keys, response keys, and behavior unless explicitly asked to change them.
- One refactoring type at a time.
- Extract business logic to services.
- Extract query branches to Pipeline filters.
- Extract reusable model/controller behavior to traits only when reused or clearly cross-cutting.
- Replace repeated magic strings with enums/constants/translations.
- Do not introduce repositories, DTOs, or action classes unless current code uses them or the user explicitly asks.
- Run focused verification after each meaningful refactor.

## Forbidden Patterns

- Fat controllers with business logic, transactions, relation syncing, and notifications inline.
- Inline validation in controllers.
- `$request->all()` for create/update.
- Raw `response()->json()` for normal API responses.
- Missing authorization through `PermissionMiddleware`, `Gate::authorize()`, or policy checks on protected operations.
- Services returning `JsonResponse` or calling `response()`, `request()`, or `Gate::authorize()`.
- `new SomeService()` in controllers; use dependency injection.
- `$guarded = []`.
- Raw SQL or `whereRaw()` using unsanitized user input.
- Querying inside loops or resources.
- Loading all records from list endpoints.
- Modifying production migrations instead of adding a new migration.
- Hardcoded user-facing messages.
- Hardcoded secrets or `.env` values.
- Plain password storage; use hashing/casts.
- Unvalidated uploads.
- Duplicated filter/search logic across controllers.
- API versioning, repository pattern, DTOs, or action classes by default.
- Form Request `authorize()` as the primary authorization layer unless nearby code already uses it.
- Pest syntax.
- Debug leftovers: `dd()`, `dump()`, temporary debug responses, unused imports, commented-out code.

## Quality Checklist

```text
Context
[ ] Inspected existing sibling files and followed local conventions.
[ ] Reused existing filters, services, traits, requests, resources, rules, helpers, and enums where possible.

Structure
[ ] Controller extends BaseController.
[ ] Request extends BaseFormRequest.
[ ] Model extends BaseModel or justified vendor/auth base.
[ ] Resource extends JsonResource.
[ ] Service created for non-trivial business logic.

API
[ ] Responses use successResponse()/failResponse().
[ ] Lists use wrapPaginate().
[ ] Routes are under auth:sanctum when protected.
[ ] Delete/restore/force-delete/toggle-active routes follow project pattern.
[ ] Resource keys match request/API contract needs.

Security
[ ] Form Request validates all input.
[ ] Writes use $request->validated().
[ ] Authorization is present through PermissionMiddleware and/or Gate/Policy, matching sibling controllers.
[ ] No raw SQL with user input.
[ ] No secrets or sensitive data exposed/logged.
[ ] File uploads validate type and size.

Data
[ ] Model has fillable, casts, hidden as needed.
[ ] Relations have correct types and return relation objects.
[ ] Migration has indexes/FKs/delete behavior/timestamps/soft deletes as appropriate.
[ ] Seeders are idempotent and factories are used where useful.

Performance
[ ] List endpoint uses Pipeline filters.
[ ] Relations are eager loaded where resources need them.
[ ] No unbounded table results.
[ ] Heavy work is queued.
[ ] Cache invalidation is handled when settings/config data changes.

Testing
[ ] PHPUnit tests added or updated for meaningful behavior.
[ ] Auth, authorization, validation, and happy path are covered when applicable.
[ ] Focused tests were run when feasible.
[ ] Pint/formatting was run when feasible.
```

## Pattern Learning

- If the user corrects a starter-kit convention, treat the correction as higher priority for the current work.
- If a correction should become permanent, update this skill only when the user asks or when the task is explicitly about skills/configuration.
- Do not silently rewrite project conventions across the codebase because one file differs; inspect more examples first.
- Prefer documenting confirmed conventions over broad generic rules.
