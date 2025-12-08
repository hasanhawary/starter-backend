<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notification.user.{userId}', static fn($locationId) => auth()->check());
