<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notification.user.{userId}', function (int $userId) {
    // Only allow the authenticated user to listen to their own channel
    return auth()->check() && (int) auth()->id() === $userId;
});
