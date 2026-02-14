---
title: Reverb - Real-Time WebSocket Server
description: Real-time notifications and updates with Reverb
---

# Reverb - Real-Time WebSocket Server

Reverb is a real-time communication server for Laravel applications, enabling instant notifications and live updates.

## What is Reverb?

Reverb provides WebSocket connectivity for:

- **Real-time notifications** — Send updates to users instantly
- **Live updates** — Update UI without polling
- **Broadcasting** — Send events to multiple users
- **Presence channels** — Track who's online
- **Activity tracking** — Real-time activity feeds

## Installation

Reverb is included in the project. To start the server:

```bash
php artisan reverb:start
```

## Configuration

Configure Reverb in `config/reverb.php`:

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

## Broadcasting Events

Broadcast user notifications:

```php
use Illuminate\\Broadcasting\\Channel;
use Illuminate\\Contracts\\Broadcasting\\ShouldBroadcast;

class UserNotificationEvent implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [new Channel('notifications.' . $this->user->id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }
}
```

Trigger the event:

```php
UserNotificationEvent::dispatch($user, $notification);
```

## Listening on Frontend

Listen for real-time events:

```javascript
// Using Echo library
Echo.channel('notifications.' + userId)
    .listen('notification.sent', (event) => {
        console.log('New notification:', event.data);
        displayNotification(event.data);
    });
```

## Presence Channels

Track online users:

```php
// Server-side
return [new PresenceChannel('users.online')];

// Client-side
Echo.join('users.online')
    .here((users) => {
        console.log('Online users:', users);
    })
    .joining((user) => {
        console.log('User came online:', user);
    })
    .leaving((user) => {
        console.log('User went offline:', user);
    });
```

## Common Use Cases

### 1. Notification Delivery

Send instant notifications without page reload:

```php
event(new UserNotificationEvent($user, $notification));
```

### 2. Live Chat

Real-time messaging between users:

```php
event(new MessageSent($message, $chat));
```

### 3. Data Updates

Push updated data to all viewers:

```php
event(new DataUpdated($model));
```

### 4. Activity Stream

Show live activity in real-time:

```php
event(new ActivityLogged($activity));
```

## Troubleshooting

**Connection refused**

```bash
# Ensure Reverb server is running
php artisan reverb:start

# Check port is not in use
netstat -an | grep 8080
```

**Messages not being received**

- Verify broadcaster is set to `reverb` in `.env`
- Check WebSocket server logs
- Verify client-side Echo connection

## See Also

- [Notifications](/guide/features/notifications) — Notification system
- [Broadcasting](/guide/tools/broadcasting) — Broadcast events
- [Real-time Features](/guide/features/reverb) — Advanced Reverb guide
