<?php

namespace Modules\Notification\app\Http\Controllers\Api\Admin;

use App\Filters\Global\OrderByFilter;
use App\Http\Requests\Global\Other\PageRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Modules\Notification\app\Filters\CalendarFilter;
use Modules\Notification\app\Http\Resources\ScheduleEventResource;
use Modules\Notification\app\Models\ScheduleEvent;

class CalendarController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(ScheduleEvent::with(['source', 'notificationEvent.variables'])->calendar()->byCurrentUser())
            ->through([CalendarFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, ScheduleEventResource::class));
    }

    public function getByMonth($month): JsonResponse
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $datesWithEvents = ScheduleEvent::calendar()
            ->byCurrentUser()
            ->whereBetween('date_time', [$start, $end])
            ->pluck('date_time')
            ->map(fn ($dt) => Carbon::parse($dt)->toDateString())
            ->unique()
            ->flip()
            ->all();

        $days = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $days[] = [
                'date' => $dateStr,
                'has_data' => isset($datesWithEvents[$dateStr]),
            ];
            $current->addDay();
        }

        return successResponse($days);
    }
}
