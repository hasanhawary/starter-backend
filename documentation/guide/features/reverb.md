---
title: Reverb - Real-Time WebSocket Server
description: Real-time notifications and updates with Laravel Reverb
---

# Reverb - Real-Time WebSocket Server

Reverb is Laravel's first-party WebSocket server for real-time communication. This project uses Reverb for instant notifications and live updates.

## What is Reverb?

Reverb provides WebSocket connectivity for:

- **Real-time notifications** — Instant delivery without polling
- **Live updates** — Update UI automatically when data changes
- **Broadcasting** — Send events to multiple users simultaneously
- **Presence channels** — Track who's online


## Installation & Setup

Reverb is pre-configured in this project. To start the server:

```bash
php artisan reverb:start
```

For development with auto-reload:
```bash
composer dev  # Starts server, queue, logs, and vite together
```

## Configuration

### Environment Variables

```env
# Enable real-time features
REALTIME_ENABLED=true

# Reverb credentials
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret

# Server settings
REVERB_HOST=127.0.0.1
REVERB_PORT=9000
REVERB_SCHEME=http

# For production
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=9000
```

### Config File

`config/reverb.php`:
```php
return [
    'apps' => [
        [
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
        ],
    ],
    'host' => env('REVERB_HOST', 'localhost'),
    'port' => env('REVERB_PORT', 8080),
];
```

## NotificationEvent

The primary broadcast event for user notifications.

**Location:** `app/Events/NotificationEvent.php`

```php
class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $user_id,
        public array $data
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("notification.user.$this->user_id")];
    }

    public function broadcastAs(): string
    {
        return 'notification.event';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->data['id'],
            'type' => $this->data['type'],
            'title' => transWithParams($this->data['title'] ?? ''),
            'message' => transWithParams($this->data['msg'] ?? ''),
            'created_at' => now(),
        ];
    }
}
```

### Triggering the Event

```php
// Direct dispatch
event(new NotificationEvent($user->id, [
    'id' => $notification->id,
    'type' => 'order_created',
    'title' => 'notifications.new_order',
    'msg' => 'notifications.order_msg|order_id=' . $order->id,
]));

// Via NotificationService (recommended)
NotificationService::resolve($user, $data, ['realtime']);
```

## Channel Naming Convention

| Channel Pattern | Description |
|-----------------|-------------|
| `notification.user.{id}` | User-specific notifications |
| `orders.{id}` | Order updates |
| `presence-room.{id}` | Presence channel for rooms |

## Frontend Integration

### Using Laravel Echo

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
```

### Listening for Notifications

```javascript
// User-specific notifications
Echo.channel('notification.user.' + userId)
    .listen('.notification.event', (event) => {
        console.log('New notification:', event);

        // event structure:
        // {
        //     id: 'uuid',
        //     type: 'order_created',
        //     title: 'New Order',
        //     message: 'Order #123 has been created',
        //     created_at: '2024-01-20T10:00:00Z'
        // }

        showToast(event.title, event.message);
        updateNotificationBadge();
    });
```

### Presence Channels

Track online users:

```javascript
Echo.join('presence-room.' + roomId)
    .here((users) => {
        console.log('Users in room:', users);
    })
    .joining((user) => {
        console.log('User joined:', user);
    })
    .leaving((user) => {
        console.log('User left:', user);
    });
```

## Creating Custom Events

1. Create the event class:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderStatusChanged implements ShouldBroadcast
{
    public function __construct(
        public int $orderId,
        public string $status
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("orders.{$this->orderId}")];
    }

    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'status' => $this->status,
            'updated_at' => now(),
        ];
    }
}
```

2. Dispatch the event:

```php
event(new OrderStatusChanged($order->id, 'shipped'));
```

3. Listen on frontend:

```javascript
Echo.channel('orders.' + orderId)
    .listen('.order.status.changed', (event) => {
        updateOrderStatus(event.status);
    });
```

## Common Use Cases

### 1. Instant Notifications

```php
// After user action
$user->sendNotification([
    'type' => 'message_received',
    'title' => 'notifications.new_message',
    'msg' => 'notifications.message_from|sender=' . $sender->name,
], ['notify', 'realtime']);
```

### 2. Live Data Updates

```php
// After data changes
event(new DataUpdated('users', $user->id, new UserResource($user)));
```

### 3. Activity Feeds

```php
// Log activity and broadcast
activity()->log('User updated profile');
event(new ActivityLogged($activity));
```

## Troubleshooting

### Connection Refused

```bash
# Ensure Reverb server is running
php artisan reverb:start

# Check port availability
netstat -an | grep 9000
```

### Events Not Broadcasting

1. Check `REALTIME_ENABLED=true` in `.env`
2. Ensure queue worker is running: `php artisan queue:work`
3. Verify event implements `ShouldBroadcast`

### Debug Mode

```bash
# Start with verbose output
php artisan reverb:start --debug
```

## Production Deployment

### Supervisor Configuration

```ini
[program:reverb]
command=php /path/to/artisan reverb:start
directory=/path/to/project
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/logs/reverb.log
```

### SSL/TLS (HTTPS)

```env
REVERB_SCHEME=https
REVERB_SSL_LOCAL_CERT=/path/to/cert.pem
REVERB_SSL_LOCAL_PK=/path/to/key.pem
```

## See Also

- [Notifications](/guide/features/notifications) — Multi-channel notification system
- [Background Jobs](/guide/features/jobs) — Queue worker setup
- [Configuration](/guide/configuration) — Environment variables
