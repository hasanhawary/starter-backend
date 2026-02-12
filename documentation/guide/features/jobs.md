---
title: Background Jobs
description: Async task processing with queues
---

# Background Jobs

Process long-running tasks asynchronously using Laravel queues.

## Creating Jobs

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $exportId,
        public array $filters = []
    ) {}

    public function handle(): void
    {
        $export = Export::find($this->exportId);
        // Process export...
    }
}
```

## Dispatching Jobs

### Dispatch Immediately

```php
ProcessExport::dispatch($exportId);
```

### Schedule for Later

```php
ProcessExport::dispatch($exportId)->delay(now()->addMinutes(10));
```

### Use Specific Queue

```php
ProcessExport::dispatch($exportId)->onQueue('exports');
```

## Queue Configuration

Configure in `config/queue.php`:

```php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry' => 3,
        'timeout' => 300,
    ],
],
```

## Running the Queue Worker

```bash
php artisan queue:work redis
php artisan queue:work --queue=exports
```

## See Also

- [Export Builder](/guide/tools/export-builder) — Uses queued jobs
- [Report Builder](/guide/tools/report-builder) — Async report generation
