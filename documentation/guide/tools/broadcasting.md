---
title: Broadcasting Events
description: Event broadcasting and real-time updates
---

# Broadcasting Events

Broadcasting allows you to broadcast events to WebSocket channels for real-time updates.

## Overview

Broadcasting sends Laravel events to connected WebSocket clients in real-time.

**Use Cases:**

- Real-time notifications
- Live data updates
- Activity feeds
- Chat notifications
- Presence tracking

## Broadcasting Channels

### Public Channels

Accessible to anyone:

```php
broadcast(new OrderShipped($order))->toOthers();
```

### Private Channels

Require authentication:

```php
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return (int) $user->id === (int) Cache::get('chat:' . $chatId . ':creator_id');
});
```

### Presence Channels

Track online users:

```php
Broadcast::channel('online-users', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
```

## Creating Broadcastable Events

```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderStatusChanged implements ShouldBroadcast
{
    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.' . $this->order->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status-changed';
    }

    public function broadcastWith(): array
    {
        return ['order' => $this->order];
    }
}
```

## Broadcasting Events

### From Controller

```php
class OrderController
{
    public function updateStatus(Order $order)
    {
        $order->update(['status' => 'shipped']);
        
        broadcast(new OrderStatusChanged($order));
        
        return response()->json(['success' => true]);
    }
}
```

### From Anywhere

```php
use App\Events\OrderStatusChanged;

OrderStatusChanged::dispatch($order);
```

## Listening on Frontend

```javascript
// Subscribe to private channel
Echo.private('orders.' + userId)
    .listen('order.status-changed', (event) => {
        console.log('Order updated:', event.order);
        updateOrderUI(event.order);
    });

// Subscribe to presence channel
Echo.join('online-users')
    .here((users) => console.log('Online:', users))
    .joining((user) => console.log('Joined:', user))
    .leaving((user) => console.log('Left:', user));
```

## Configuration

Configure broadcasting in `config/broadcasting.php`:

```php
'default' => env('BROADCAST_DRIVER', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST', 'localhost'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'useTLS' => env('REVERB_SCHEME') === 'https',
        ],
    ],
],
```

## See Also

- [Reverb](/guide/features/reverb) — Real-time WebSocket server
- [Notifications](/guide/features/notifications) — Notification system
- [Events Documentation](https://laravel.com/docs/events) — Laravel Events
