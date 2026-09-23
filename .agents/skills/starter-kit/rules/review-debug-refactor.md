# Review, Debugging, Minimal Changes, And Testing

Use this rule when implementing or simplifying code, reviewing changes, diagnosing bugs, refactoring, writing PHPUnit tests, or checking quality before finishing.

## Minimal Changes And Refactoring

- Inspect sibling files and follow the existing architecture; generic Laravel advice must not introduce a second style for the same concern.
- Every line, word, import, variable, query, wrapper, abstraction, and file must have a concrete need. Start with the smallest behavior-preserving change and refactor only what the task needs, one refactoring type at a time.
- Preserve public APIs, route names, request keys, response keys, and behavior unless explicitly asked to change them.
- Reuse and extend the existing controller/model/resource/request/service before creating a class. Do not create a file for stylistic purity, one method, or hypothetical reuse; create one only when framework-required or it has a real separate reusable responsibility. Explain why each new file is necessary.
- Use route model binding directly; do not re-query unless a scope/access constraint or transactional row lock requires it.
- Remove redundant authorization/query layers, but never remove validation, authorization, data-access boundaries, deterministic ordering, or tests as "boilerplate".
- Extract business logic to [services](services.md), query branches to [Pipeline filters](filters-performance.md), and reusable model/controller behavior to traits only when reused or clearly cross-cutting.
- Replace repeated magic strings with enums/constants/translations.
- Do not introduce repositories, DTOs, action classes, or API versioning by default; use them only when current code uses them or the user explicitly requests them.
- Verify each meaningful refactor with focused tests or direct reproduction.
- Remove debug leftovers (`dd()`, `dump()`, temporary debug responses), unused imports/variables, and commented-out code before finishing.

## Collections, Types, And Comments

- Use Collection methods where they improve clarity.
- Prefer Laravel helpers such as `Str`, `Arr`, `Number`, and `Uri` over raw PHP when appropriate.
- Use explicit return and parameter types; use PHPDoc for useful array shapes or exceptions.
- Comments should explain non-obvious behavior, not restate code. Avoid speculative abstractions.

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

## Code Review And Quality Checks

Present findings first, ordered by severity, with file and line references when possible. Use the canonical topics below for their detailed requirements rather than duplicating their anti-pattern lists:

| Review area | Canonical checks |
| --- | --- |
| Context and structure | [Architecture](architecture.md): sibling conventions, reuse, folder placement, and layer responsibilities; verify base classes in the corresponding API, validation, and model rules. |
| API contract | [Controllers and routes](api-controllers-routes.md): response envelope, translated messages, `wrapPaginate()` lists, protected `auth:sanctum` routes, delete/restore/force-delete/toggle-active patterns, and resource keys. |
| Validation | [Validation](validation.md): Form Requests, validated writes, custom rules and uploads. |
| Security and error handling | [Security and authorization](security-auth.md): middleware/Gate/Policy protection, SQL injection, secrets/logging, upload safety, password hashing/casts, mass assignment, and exception handling. |
| Business mutations | [Services](services.md): thin controllers, dependency injection instead of `new SomeService()`, transactions for multiple writes, after-commit side effects, and no HTTP responses, request access, validation, or authorization in services. |
| Data | [Models and database](models-database.md): fillable/casts/hidden fields, typed relation objects, migration indexes/FKs/delete behavior/timestamps/soft deletes, new migrations for production schema changes, idempotent seeders, and useful factories. |
| Queries and performance | [Filters and performance](filters-performance.md): reusable Pipeline filters, eager loading, N+1 avoidance, bounded lists/pagination, and no resource/loop queries or duplicated filter/search logic. |
| Background work and configuration | [Jobs, settings, and environment](services.md): queue heavy work and invalidate changed settings/config caches. |
| Verification and cleanup | [Testing](#testing): meaningful happy-path, validation, authentication and authorization coverage; focused verification; formatting; no debug artifacts or unused code. |

Severity:

- Critical: security hole, broken response shape, missing auth, raw SQL with user input, hardcoded secret, wrong base class.
- High: missing authorization, unvalidated writes, `$request->all()`, missing transaction for multi-write, N+1 in common path.
- Medium: missing test, missing index, inconsistent naming, missing translation, cache invalidation issue.
- Low: maintainability or small consistency improvements.

## Testing

- Use PHPUnit only; do not use Pest syntax. Prefer feature tests for API endpoints.
- Feature tests live in `tests/Feature/` and extend `Tests\TestCase`; unit tests live in `tests/Unit/`.
- Name tests `test_{behavior_description}`.
- Use factories and Faker; avoid raw inserts unless testing a database edge case.
- Prefer `assertModelExists()` and model assertions where suitable.
- Cover response envelopes, database effects, happy paths, unauthenticated, unauthorized, and authorized flows as applicable.
- Test required fields, invalid types, uniqueness, file rules, and relation existence. Follow the exact translated error/message assertions in [validation](validation.md#translated-validation-verification).
- Set up fakes for mail, notifications, events, queues, and HTTP clients after factory setup. For HTTP calls, use `Http::fake()` and `preventStrayRequests()`.

### PHPUnit Feature Test Template

Use PHPUnit method names and Laravel HTTP assertions. Cover response envelope, auth, validation, and database effects.

```php
<?php

namespace Tests\Feature\DataEntry;

use App\Models\User;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_authorized_user_can_create_product(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => ['ar' => 'منتج', 'en' => 'Product'],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'code' => 'PRD-001',
            'is_active' => true,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.code', 'PRD-001');

        $this->assertDatabaseHas('products', [
            'code' => 'PRD-001',
        ]);
    }

    public function test_product_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'code' => 'PRD-002',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['name']]);
    }
}
```

### Verification Commands

- Run focused tests after changes: `php artisan test --compact --filter=ProductTest`.
- For broad changes, run `php artisan test --compact` or `composer test`.
- Run `vendor/bin/pint` after PHP edits when feasible.
- If tests cannot run because the environment is incomplete, state what blocked verification.

## Pattern Learning

- If the user corrects a starter-kit convention, treat the correction as higher priority for the current work.
- If a correction should become permanent, update this skill only when the user asks or when the task is explicitly about skills/configuration.
- Do not silently rewrite project conventions across the codebase because one file differs; inspect more examples first.
- Prefer documenting confirmed conventions over broad generic rules.
