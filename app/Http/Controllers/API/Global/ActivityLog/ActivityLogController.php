<?php

namespace App\Http\Controllers\API\Global\ActivityLog;

use App\Filters\Global\OrderByFilter;
use App\Filters\Setting\ActivityLogFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Resources\Global\ActivityLog\ActivityLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ActivityLogController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-log'), only: ['index', 'show']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Activity::query()->with(['causer', 'subject']))
            ->through([
                ActivityLogFilter::class,
                OrderByFilter::class,
            ])
            ->thenReturn();

        return successResponse(wrapPaginate($query, ActivityLogResource::class));
    }

    public function show(Activity $activity): JsonResponse
    {
        return successResponse(new ActivityLogResource($activity->load(['causer', 'subject'])));
    }
}
