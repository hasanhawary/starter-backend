# Jobs, Notifications, Settings, Environment, And Commands

Use this rule for queued jobs, notifications, settings/cache, exports, reports, commands, schedules, environment variables, and CI/deployment checks.

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

- Jobs implement `ShouldQueue` for heavy work.
- Jobs serialize IDs or simple payloads; reload models in `handle()` when the model can change.
- Configure `tries`, `timeout`, and retry/backoff for external services.
- Implement `failed(Throwable $exception)` for permanent failures when cleanup/status updates are needed.
- Make jobs idempotent so retries do not duplicate side effects.
- Put exports/reports on appropriate queues when the existing module does.

## Notifications

- Email, SMS, realtime, and in-app notifications use existing notification services, jobs, events, and model traits.
- Dispatch notification side effects after DB commits.
- Realtime notifications use Laravel Echo/Reverb channels following existing channel names.
- Use translated messages and packed parameter helpers when existing notification templates require them.

## Settings

- Settings are centralized in `SettingService` and helpers.
- Cache settings with brand-aware keys such as `settings_{brand}`.
- Flush or forget settings cache after updates.
- Store environment-backed settings carefully and never expose secrets.

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

## Schedule Route

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
- Local/debug-only code must not leak into production paths.
- Queue drivers can differ by environment, but code should work with queued execution.
- Scheduled commands with variable runtime should use `withoutOverlapping()` and `onOneServer()` when needed.
- CI checks should include Composer install, config validation, migrations as appropriate, PHPUnit, and Pint for PHP changes.
