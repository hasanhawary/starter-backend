# Services, Jobs, Settings, And Operations

Use this rule for business services, transactions, relation syncing, jobs, external HTTP calls, notifications, settings, helpers, exports, commands, schedules, environment configuration, and CI.

## Philosophy

Controllers stay thin. Extract non-trivial writes, relation syncing, notifications, exports, imports, settings logic, and multi-step operations into services.

## When To Use A Service

**Use a service when** the controller has complex logic such as:
- Relation syncing (roles, permissions, tags, categories).
- Multi-step writes requiring `DB::transaction()`.
- Side effects like notifications, credential emails, or event dispatches.
- Business operations that also require ownership or root-protection checks; keep authorization at the controller/Policy boundary described in `security-auth.md`.
- Settings, caching, or import/export orchestration.

**Do NOT use a service when** the controller is simple CRUD:
- `store` is just `Model::create($request->validated())`.
- `update` is just `$model->update($request->validated())`.
- No relation syncing, no notifications, no transactions needed.
- A basic data-entry resource (e.g., Country, City, Product, Category) with permission-middleware-only authorization.

For simple CRUD, the controller handles the write directly — no service class is needed. Adding a service to a simple CRUD controller adds unnecessary indirection with no benefit.

## Service Template

```php
class AdminService
{
    /**
     * @throws Throwable
     */
    public function store(AdminRequest $request): Admin
    {
        return DB::transaction(function () use ($request) {
            $admin = Admin::create($request->validated());
            $this->syncRelations($admin, $request);

            // Send notifications only after the database commit succeeds.
            DB::afterCommit(fn () => $this->sendCredentials($admin, $request));

            return $admin->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function update(Admin $admin, AdminRequest $request): Admin
    {
        return DB::transaction(function () use ($admin, $request) {
            $admin->update($request->validated());
            $this->syncRelations($admin, $request);

            DB::afterCommit(fn () => $this->sendCredentials($admin->refresh(), $request, isCreate: false));

            return $admin->refresh();
        });
    }

    public function syncRelations(Admin $admin, AdminRequest $request): void
    {
        $data = $request->validated();

        when(filled($data['roles'] ?? null), static fn () => $admin->syncRoles(Role::whereId($data['roles'])->pluck('name')));
        when(filled($data['permissions'] ?? null), static fn () => $admin->syncPermissions($data['permissions']));
    }
}
```

## Service Rules

- Services live in `app/Services/{Domain}/`.
- Inject services through constructor promotion in controllers.
- Accept Form Request objects or explicit typed values, following sibling services. Validate only at the request layer; consume `$request->validated()` or values derived from validated data, including in relation and credential helpers. See `validation.md`.
- Return Eloquent models, arrays, or domain results; never return HTTP responses.
- Keep one transaction boundary around the complete multi-step business write. CRUD services own it in this example; workflows with an existing controller-owned transaction, such as the status Strategy pattern, retain that boundary. Do not split an atomic write across layers or add redundant nested transactions.
- Dispatch side effects created inside transactions with `DB::afterCommit()`, including jobs, events, notifications, and mail that depend on committed data.
- Keep private/public helper methods for internal sub-operations following current service style.
- Do not call `Gate::authorize()` in services.
- Do not read from `request()` inside services.

## Settings And Cache

- Centralize settings in `SettingService` and existing helpers.
- Use `Cache::remember()` / `rememberForever()` instead of manual get/put. Use `Cache::lock()` or database locks for race-prone writes, and avoid caching user-specific sensitive data without an explicit need.
- Cache settings with brand-aware keys such as `settings_{brand}`.
- Flush or forget settings cache after updates, after commit when updates are transactional.
- Store environment-backed settings carefully and never expose secrets.

### Settings Service Example

```php
class SettingService
{
    protected string $cacheKeyPrefix = 'settings_';

    /**
     * Get all settings as nested associative array, cached.
     */
    public function all(): array
    {
        $brand = brandName();

        return Cache::rememberForever($this->cacheKeyPrefix.$brand, function () {
            $settings = Setting::all();
            $nested = [];

            foreach ($settings as $setting) {
                $keys = explode('.', $setting->group ?: 'general'); // group path
                $current = &$nested;

                foreach ($keys as $key) {
                    if (! isset($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }

                // Store the actual value + meta.
                $current[$setting->key] = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'is_multi_lang' => $setting->is_multi_lang,
                    'placeholder' => $setting->placeholder,
                    'label' => $setting->label,
                ];
            }

            return $nested;
        });
    }
}
```

## Helper Rules

Use existing helpers from `app/Helpers/App.php` before adding new helpers.

- Response: `successResponse()`, `failResponse()`, `abort403()`, `unKnownError()`.
- Pagination: `wrapPaginate()`.
- Translation/formatting: `resolveTrans()`, `transWithParams()`, `buildDelimiterMessage()`.
- Values: `resolveBool()`, `resolveArray()`, `resolveEmptyLang()`, `resolveEmptyToNull()`.
- Model/class helpers: `getModelKey()`, `detectModelPath()`, `getModelTranslatable()`, `resolveModel()`, `resolveClass()`.
- Auth/config helpers: `getAuthUser()`, `getAuthGuard()`, `shouldVerifyOtp()`, `brandName()`, `setting()`.

Only add a helper if it is cross-cutting and reusable. Domain-specific logic belongs in a service. Response helpers remain at the HTTP boundary; services return domain results.

## Domain Log Messages

- Store visit, permit, and visitor log messages as locale-neutral packed keys using `buildDelimiterMessage()`, deriving the key from the log enum with `Str::snake($logType->name)`.
- Human actions include the explicit actor parameter, normally `['name' => $actor->name]`; pass the actor into the service or strategy instead of resolving the request implicitly.
- Automatic system events use the same enum-derived packed key without inventing an actor. Add matching Arabic and English entries to the domain `logs.php` translation files.
- Resolve packed messages for presentation with `transWithParams($message, 'visitpermit::logs')`; do not store already translated prose in new visit or visitor logs.

## Queued Job: External HTTP Work

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class SendProductReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public array|string $recipients,
        public string $message,
    ) {}

    public function handle(): void
    {
        if (! config('services.sms.enable')) {
            return;
        }

        Http::timeout(10)
            ->connectTimeout(5)
            ->retry([100, 500, 1000])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.config('services.sms.token'),
            ])
            ->post(config('services.sms.url'), [
                'sender' => config('services.sms.sender'),
                'recipients' => Arr::wrap($this->recipients),
                'body' => $this->message,
            ])
            ->throw();
    }
}
```

## HTTP Client Rules

- Set `timeout()` and `connectTimeout()` for external calls.
- Use `retry()` for retryable external APIs and `throw()` or explicit status checks.
- Use `Http::fake()` and `Http::preventStrayRequests()` in tests that make HTTP calls; see `review-debug-refactor.md` for test conventions.

## Queued Job: Notification Resolver

```php
class SendProductNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $users,
        public array $data,
        public ?array $types = [],
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        collect($this->users)->each(
            fn ($user) => $notificationService->resolve($user, $this->data, $this->types)
        );
    }
}
```

## Job Rules

- Queue heavy work (email, SMS, notifications, exports, reports, and external calls) in jobs implementing `ShouldQueue`. Queue notifications and mailables when they are not immediate in-process work.
- Jobs serialize IDs or simple payloads; reload models in `handle()` when the model can change.
- Configure `tries`, `timeout`, and retry/backoff for external services.
- Implement `failed(Throwable $exception)` for permanent failures when cleanup, status updates, or admin notification are needed.
- Make jobs idempotent so retries do not duplicate side effects.
- Choose queues following the existing module pattern.

## Notifications

- Email, SMS, realtime, and in-app notifications use existing notification services, jobs, events, and model traits.
- Realtime notifications use Laravel Echo/Reverb channels following existing channel names.
- Use translated messages and keep payloads consistent with existing notification templates and packed parameter helpers.

## Exports And Reports

- Use installed export/report builder packages and module patterns before creating new infrastructure.
- Long-running exports run in queues.
- Track export status transitions where the module supports it.
- Use `ExportFileService`, export registry/tool classes, or existing report tools when present.

## Artisan Command Template

```php
class ProductReindex extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:reindex
        {--chunk=500 : Records processed per chunk}
        {--force : Run without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Reindex products for search and reports';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        if (! $this->option('force') && ! $this->confirm('Reindex products now?')) {
            return;
        }

        $this->warn('Product reindex started...');

        Product::query()->chunk((int) $this->option('chunk'), function ($products) {
            foreach ($products as $product) {
                // Keep command orchestration here; push real business logic to a service.
                app(ProductSearchService::class)->reindex($product);
            }
        });

        Artisan::call('cache:clear');
        $this->info('Product reindex completed.');
    }
}
```

## Scheduling

- Keep schedules in the current project scheduling location, usually `routes/console.php`.
- Use `withoutOverlapping()` for variable-duration tasks and `onOneServer()` when deployed on multiple servers.

### Schedule Route

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('products:reindex --force')
    ->dailyAt('02:00')
    ->withoutOverlapping();
```

## Environment And CI

- Use `config()` in application code; use `env()` only in config files.
- Keep `.env.example` updated when adding required environment variables.
- Never commit `.env` or hardcoded credentials.
- Do not add environment-specific branches when config values or feature flags are correct.
- Prefer config, language files, and enums/constants over hardcoded strings.
- Local/debug-only code must not leak into production paths.
- Queue drivers can differ by environment, but code should work with queued execution.
- CI checks should include Composer install, config validation, migrations as appropriate, PHPUnit, and Pint for PHP changes.
