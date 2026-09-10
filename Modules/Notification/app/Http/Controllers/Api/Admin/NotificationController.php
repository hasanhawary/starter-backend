<?php

namespace Modules\Notification\app\Http\Controllers\Api\Admin;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Modules\Notification\app\Http\Requests\NotificationRequest;
use Modules\Notification\app\Http\Resources\NotificationResource;

class NotificationController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    public function index(): JsonResponse
    {
        $user = auth()->user();

        $baseQuery = $user->notifications();
        $countQuery = (clone $baseQuery)->whereNull('read_at')->count();

        $notifications = [
            'count' => $countQuery,
            'notifications' => fetchData(
                $baseQuery->orderBy('created_at', 'desc'),
                request('pageSize'),
                NotificationResource::class
            ),
        ];

        return successResponse($notifications);
    }

    public function update(NotificationRequest $request): JsonResponse
    {
        $user = auth()->user();

        match ($request->action) {
            'open' => $user->notifications()->whereNull('open_at')->update(['open_at' => now()]),
            'read' => $user->notifications()->whereIn('id', $request->ids)->update(['read_at' => now()]),
        };

        return successResponse(msg: trans('api.notifications_updated'));
    }
}
