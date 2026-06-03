<?php

namespace App\Http\Controllers\API\Global\Notification;

use App\Filters\Notification\NotificationFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Notification\NotificationRequest;
use App\Http\Resources\Global\Notification\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;

class NotificationController extends BaseController
{
    public function index(): JsonResponse
    {
        $baseQuery = app(Pipeline::class)
            ->send(Notification::forCurrentUser())
            ->through([
                NotificationFilter::class,
            ])
            ->thenReturn();

        $countQuery = clone $baseQuery;

        $notifications = [
            'count' => $countQuery->whereNull('open_at')->count(),
            'notifications' => wrapPaginate($baseQuery->orderBy('created_at', 'desc'), NotificationResource::class),
        ];

        return successResponse($notifications);
    }

    public function update(NotificationRequest $request): JsonResponse
    {
        $query = Notification::forCurrentUser();

        match ($request->action) {
            'open' => $query->whereNull('open_at')->update(['open_at' => now()]),
            'read' => $query->whereIn('id', $request->ids)->update(['read_at' => now()]),
            default => null,
        };

        return successResponse(msg: trans('api.notifications_updated'));
    }
}
