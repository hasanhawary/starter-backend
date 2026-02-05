---
title: Events System
description: Event-driven architecture
---

# Events System

Build event-driven applications with Laravel's event system.

## What are Events?

Events provide a way to decouple application logic. Instead of directly calling methods, you emit events that listeners can react to.

## Creating Events

```php
namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}
```

## Creating Listeners

```php
namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(UserRegistered $event): void
    {
        Mail::send(new WelcomeEmail($event->user));
    }
}
```

## Registering Listeners

In `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    UserRegistered::class => [
        SendWelcomeEmail::class,
        LogUserRegistration::class,
    ],
];
```

## Dispatching Events

```php
use App\Events\UserRegistered;
use App\Models\User;

$user = User::create([...]);
UserRegistered::dispatch($user);
```

## Queued Listeners

Make listeners process asynchronously:

```php
namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        // Sent in background...
    }
}
```

## See Also

- [Broadcasting](/guide/tools/broadcasting) — Broadcast events to users
- [Notifications](/guide/features/notifications) — Event-triggered notifications
- [Activity Logging](/guide/features/activity-logging) — Model events
