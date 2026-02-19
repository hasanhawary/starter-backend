<?php

use Illuminate\Support\Facades\Broadcast;

function authorizeBroadcast(string $guard, int $userId): bool
{
    return auth($guard)->check() && (int) auth($guard)->id() === $userId;
}

Broadcast::channel('notification.user.{userId}',
    // Only allow the authenticated user to listen to their own channel
    static fn (int $userId) => authorizeBroadcast('user', $userId)
);

Broadcast::channel('notification.admin.{userId}',
    // Only allow the authenticated user to listen to their own channel
    static fn (int $userId) => authorizeBroadcast('admin', $userId)
);
