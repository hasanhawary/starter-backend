---
title: Notifications
description: Email, SMS, real-time, and in-app notifications
---

# Notifications

The notification system supports multiple channels: email, SMS, real-time (WebSocket), and in-app.

## Features

- **Multi-Channel** — Email, SMS, real-time, in-app
- **Event-Driven** — Triggered by model events
- **Async Sending** — Queued jobs prevent blocking
- **Customizable** — Override templates and content

## Notification Channels

### Email

Sent via configured mail service.

### SMS

Sent via external SMS provider.

### Real-Time

Broadcast via Reverb WebSocket.

### In-App

Stored in notifications table.

## Triggering Notifications

```php
NotificationService::resolve($user, [
    'type' => 'user_created',
    'data' => [...]
], ['email', 'realtime']);
```

## Configuration

```php
// config/notification.php
return [
    'channels' => ['email', 'sms', 'realtime'],
    'queue' => true,
];
```

## See Also

- [Global Features API](/guide/api-reference) — Notification endpoints
- [Reverb](/guide/features/reverb) — Real-time WebSocket server
