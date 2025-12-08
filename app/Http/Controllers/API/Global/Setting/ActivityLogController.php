<?php

namespace App\Http\Controllers\API\Global\Setting;

use App\Filters\Global\OrderByFilter;
use App\Filters\Setting\ActivityLogFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Global\Setting\ActivityLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-log'), only: ['index','show']),
        ];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Activity::query())
            ->through([
                ActivityLogFilter::class,
                OrderByFilter::class,
            ])
            ->thenReturn();

        return successResponse(fetchData($query, $request->input('pageSize'), ActivityLogResource::class));
    }

    /**
     * @param Activity $activity
     * @return JsonResponse
     */
    public function show(Activity $activity): JsonResponse
    {
        return successResponse(new ActivityLogResource($activity));
    }

}
