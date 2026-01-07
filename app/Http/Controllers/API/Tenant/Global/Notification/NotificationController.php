<?php

namespace App\Http\Controllers\API\Tenant\Global\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Global\Notification\NotificationRequest;
use App\Http\Resources\Central\Global\Notification\NotificationResource;
use App\Models\Central\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
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
            'notifications' => fetchData($baseQuery->orderBy('created_at', 'desc'), request()->pageSize, NotificationResource::class)
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
