# Laravel Best Practices Inside Starter Kit

Use this rule for generic Laravel concerns that support the starter-kit rules. When this rule conflicts with a starter-kit-specific rule, follow the starter-kit rule.

## Consistency First

- Inspect sibling files before applying generic Laravel advice.
- Follow the codebase convention even when another Laravel pattern is valid.
- Do not introduce a second architecture style for the same concern.

## Database Performance

- Eager load with `with()` to prevent N+1 queries.
- Use `whenLoaded()` in resources to avoid accidental lazy loading.
- Select only needed columns when safe.
- Use `chunk()`, `chunkById()`, `lazy()`, or `cursor()` for large datasets.
- Add indexes for columns used in `WHERE`, `ORDER BY`, filters, joins, and foreign keys.
- Use `withCount()` instead of loading relations just to count them.
- Never query inside resources, views, loops, notifications, or mail templates unless preloaded.

## Advanced Queries

- Prefer `addSelect()` subqueries when only one related value is needed.
- Use conditional aggregates instead of multiple count queries when useful.
- Use `setRelation()` when an already-known parent relation prevents circular N+1 issues.
- Prefer simple indexed queries over one complex query when clearer and faster.
- Match compound indexes to common filter and sort order.

## Security

- Define `$fillable` or intentional guarded behavior on every model; in this starter use explicit `$fillable`.
- Authorize protected actions with `PermissionMiddleware`, `Gate::authorize()`, or Policies according to the starter domain pattern.
- Do not use raw SQL with user input; bind parameters if raw SQL is unavoidable.
- Validate MIME type, extension, and size for uploads.
- Never commit `.env`, tokens, passwords, or secrets.
- Use `config()` for secrets and environment-backed values.
- Hide sensitive fields with `$hidden` and avoid logging secrets.

## Caching

- Use `Cache::remember()` / `rememberForever()` instead of manual get/put.
- In this starter, settings cache uses brand-aware keys like `settings_{brand}`.
- Clear or forget cache after updates.
- Use locks (`Cache::lock()` or DB locks) for race-prone writes.
- Avoid caching user-specific sensitive data without explicit need.

## Eloquent

- Type-hint relationships with return types like `BelongsTo`, `HasMany`, `BelongsToMany`, `HasOne`.
- Use casts for booleans, dates, datetimes, arrays, decimals, passwords, and enums.
- Use `Attribute` objects for accessors/mutators.
- Use local scopes and starter scope traits for reusable query constraints.
- Do not hardcode table names when model methods or Eloquent queries can provide them.

## Validation

- Use Form Requests, not inline controller validation.
- In this starter, requests extend `BaseFormRequest`.
- Use `$request->validated()` only for writes.
- Prefer array validation syntax for new code unless sibling files use another style.
- Use `Rule::unique()->ignore(...)->withoutTrashed()` for soft-delete-aware unique rules.
- Use `Rule::exists(...)->withoutTrashed()` for soft-delete-aware foreign keys.

## Configuration

- Use `env()` only in config files.
- Use `config()` in application code.
- Keep `.env.example` updated for required variables.
- Prefer config, language files, and enums/constants over hardcoded strings.

## Testing

- Use PHPUnit only in this project.
- Prefer feature tests for API endpoints.
- Use factories and Faker.
- Cover happy path, validation failures, unauthenticated, unauthorized, and authorized flows.
- Use fakes for mail, notifications, events, queues, and HTTP after factory setup.
- Prefer focused tests first: `php artisan test --compact --filter=...`.

## Queues And Jobs

- Queue heavy work: email, SMS, notifications, exports, reports, and external calls.
- Set `tries`, `timeout`, and backoff/retry behavior.
- Serialize IDs or simple payloads; reload models in `handle()` when models can change.
- Make jobs idempotent so retries do not duplicate side effects.
- Implement `failed()` when status cleanup or admin notification is needed.
- Use `DB::afterCommit()` for side effects created inside transactions.

## Routing And Controllers

- Use route model binding.
- Use `apiResource()` for resourceful endpoints, plus explicit starter routes for delete/restore/force-delete/toggle-active.
- Keep controllers thin.
- Use Form Requests for validation and resources for output.
- Use named/domain route groups according to sibling route files.

## HTTP Client

- Set `timeout()` and `connectTimeout()` for external calls.
- Use `retry()` for retryable external APIs.
- Use `throw()` or explicit status checks.
- Use `Http::fake()` and `preventStrayRequests()` in tests when making HTTP calls.

## Events, Notifications, Mail

- Queue notifications and mailables when they are not immediate in-process work.
- Dispatch after commit when the notification depends on committed data.
- Use translated messages.
- Keep notification payloads consistent with existing templates and helper formats.

## Migrations

- Generate migrations with Artisan when creating them through commands.
- Add indexes in the migration.
- Keep migrations reversible by default.
- Do not modify migrations that may already have run in production; create a new migration.
- Do not mix schema changes and data changes unless sibling migrations do.

## Scheduling

- Use `withoutOverlapping()` on variable-duration tasks.
- Use `onOneServer()` when deployed on multiple servers.
- Keep scheduled tasks in the current project scheduling location, usually `routes/console.php`.

## Collections And Style

- Use Collection methods where they improve clarity.
- Prefer Laravel helpers such as `Str`, `Arr`, `Number`, and `Uri` over raw PHP when appropriate.
- Use explicit return types and parameter types.
- Use PHPDoc for useful array shapes or exceptions.
- Comments should explain non-obvious behavior, not restate simple assignments.

## Starter-Kit Overrides

- Use `successResponse()` / `failResponse()` instead of generic JSON responses.
- Use `wrapPaginate()` for lists.
- Use Pipeline filters instead of ad-hoc controller query chains.
- Use `BaseController`, `BaseFormRequest`, and `BaseModel` where applicable.
- Use `PermissionMiddleware` for simple action permissions and `Gate`/Policies for ownership/resource rules.
- Services may accept Form Requests because this starter uses that convention.
- Do not introduce repositories, DTOs, action classes, or API versioning by default.
