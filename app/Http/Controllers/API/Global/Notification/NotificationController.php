<?php

namespace App\Http\Controllers\API\Global\Notification;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Notification\NotificationRequest;
use App\Http\Resources\Global\Notification\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController extends BaseController
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $baseQuery = Notification::forCurrentUser();
        $countQuery = clone $baseQuery;

        $notifications = [
            'count' => $countQuery->whereNull('open_at')->count(),
            'notifications' => wrapPaginate($baseQuery->orderBy('created_at', 'desc'), NotificationResource::class)
        ];

        return successResponse($notifications);
    }

    /**
     * @param NotificationRequest $request
     * @return JsonResponse
     */
    public function update(NotificationRequest $request): JsonResponse
    {
        $query = Notification::query();

        match ($request->action) {
            // TODO: use markAsOpen or markAsRead instead
            'open' => $query->whereNull('open_at')->update(['open_at' => now()]),
            'read' => $query->whereIn('id', $request->ids)->update(['read_at' => now()]),
        };

        return successResponse(msg: trans('api.notifications_updated'));
    }
}
