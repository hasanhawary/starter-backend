---
title: Notification System
description: Multi-channel notifications including email, SMS, real-time, and in-app
---

# Notification System

The notification system supports multiple channels: database (in-app), WebSocket (real-time), email, and SMS.

## Features

- **Multi-Channel** — Send via database, WebSocket, email, or SMS
- **Unified API** — Single `NotificationService::resolve()` method
- **Async Support** — Email and SMS are queued for background processing
- **Translation Ready** — Supports localized messages with parameters

## NotificationService

The central service for sending notifications across all channels.

**Location:** `app/Services/Global/NotificationService.php`

### Basic Usage

```php
use App\Services\Global\NotificationService;

NotificationService::resolve($user, [
    'type' => 'order_created',
    'id' => $order->id,
    'title' => 'notifications.new_order',
    'msg' => 'notifications.order_created_msg|order_id=' . $order->id,
], ['notify', 'realtime', 'email']);
```

### Parameters

```php
NotificationService::resolve(
    Authenticatable $user,  // Target user
    array $data,            // Notification data
    ?array $types = ['notify', 'realtime']  // Channel types
): void
```

### Channel Types

| Type | Description | Implementation |
|------|-------------|----------------|
| `notify` | Database notification | `UserNotify` notification class |
| `realtime` | WebSocket broadcast | `NotificationEvent` event |
| `email` | Email message | `BasicMail` mailable |
| `sms` | SMS message | `SendSmsJob` queued job |

---

## Data Structure

The `$data` array should contain:

```php
$data = [
    'type' => 'order_created',           // Notification type identifier
    'id' => $entity->id,                 // Related entity ID
    'title' => 'notifications.title_key', // Translation key for title
    'msg' => 'notifications.msg_key|param=value', // Message with optional params
    'url' => '/orders/' . $entity->id,   // Optional: link URL
    'urlText' => 'View Order',           // Optional: link text
];
```

### Translation Format

Messages support inline parameters using pipe syntax:

```php
'msg' => 'notifications.order_msg|order_id=123|total=500'

// In lang/en/notifications.php:
'order_msg' => 'Order #:order_id has been created. Total: $:total'
```

## Channel Implementations

### Database Notifications (`notify`)

Stored in the `notifications` table for in-app display.

```php
// Sends via UserNotify notification class
private static function sendNotify(Authenticatable $user, array $data): void
{
    $user->notify(new UserNotify($data));
}
```

### Real-time Notifications (`realtime`)

Broadcasts via WebSocket using Laravel Reverb.

```php
private static function sendRealtimeNotification(Authenticatable $user, array $data): void
{
    if (config('project.realtime.enabled')) {
        event(new NotificationEvent($user->id, $data));
    }
}
```

**Channel:** `notification.user.{user_id}`
**Event:** `notification.event`

### Email Notifications (`email`)

Sends via configured mail driver.

```php
public static function sendEmail(Authenticatable $user, array $data): void
{
    Mail::to($user->email)->send(new BasicMail($user, $data));
}
```

### SMS Notifications (`sms`)

Queues SMS for background sending.

```php
private static function sendSMS(Authenticatable $user, array $data): void
{
    $message = self::resolveMessageContent($data);

    if ($user->phone) {
        dispatch(new SendSmsJob($user->phone, $message));
    }
}
```

## Using from Models

Models can use the `ApplyNotification` trait for convenience:

```php
use App\Trait\Global\ApplyNotification;

class User extends Authenticatable
{
    use ApplyNotification;
}

// Send notification
$user->sendNotification([
    'type' => 'welcome',
    'title' => 'notifications.welcome_title',
    'msg' => 'notifications.welcome_msg',
], ['notify', 'email']);
```

## Batch Notifications

Use `SendEmailJob` for sending to multiple users:

```php
use App\Jobs\SendEmailJob;

$users = User::where('is_active', true)->get();

dispatch(new SendEmailJob($users, [
    'type' => 'announcement',
    'title' => 'notifications.announcement',
    'msg' => 'notifications.announcement_msg',
], ['email', 'notify']));
```

## API Endpoints

### List Notifications

```bash
GET /api/notifications?per_page=20
Authorization: Bearer {token}
```

**Response:**
```json
{
    "status": true,
    "data": {
        "unread" : 5,
        "notifications": {
            "data": [
                {
                    "id": "uuid",
                    "type": "order_created",
                    "title": "New Order",
                    "message": "Order #123 has been created",
                    "read_at": null,
                    "created_at": "2024-01-20T10:00:00Z"
                }
            ],
            "meta": { ... }
        }
    }
}
```

### Mark as Read

```bash
PUT /api/notifications
Authorization: Bearer {token}
Content-Type: application/json

{
    "ids": ["uuid1", "uuid2"]
}
```

## Configuration

### Enable Real-time

In `.env`:
```env
REALTIME_ENABLED=true
```

### Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### SMS Provider

In `config/project.php`:
```php
'notifications' => [
    'sms_provider' => env('SMS_PROVIDER', 'twilio'),
],
```

## Frontend Integration

### WebSocket Listener

```javascript
// Using Laravel Echo
Echo.channel('notification.user.' + userId)
    .listen('.notification.event', (event) => {
        console.log('New notification:', event);
        // event.id, event.type, event.title, event.message, event.created_at
        showNotification(event);
    });
```

## See Also

- [Reverb](/guide/features/reverb) — WebSocket server setup
- [Background Jobs](/guide/features/jobs) — Queue configuration
- [Useful Traits](/guide/features/useful-traits) — ApplyNotification trait
- [API Reference](/guide/api-reference) — Notification endpoints
