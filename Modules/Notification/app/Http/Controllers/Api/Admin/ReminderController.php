<?php

namespace Modules\Notification\app\Http\Controllers\Api\Admin;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Carbon;
use Modules\Notification\app\Http\Resources\ScheduleEventResource;
use Modules\Notification\app\Models\ScheduleEvent;

class ReminderController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    /**
     * Get reminders grouped by date: today | tomorrow | this_week | next_week.
     *
     * Each reminder lands in exactly one bucket:
     *  - today      → dated today
     *  - tomorrow   → dated tomorrow
     *  - this_week  → within the current week, excluding today and tomorrow
     *  - next_week  → within the following week, excluding today and tomorrow
     *
     * The week is pinned to Saturday→Friday (not Carbon's locale default, which
     * differs between ar and en) so the buckets are identical in both the Arabic
     * and English views.
     */
    public function index(Request $request): JsonResponse
    {
        $baseQuery = ScheduleEvent::with(['source'])
            ->reminder()
            ->byCurrentUser()
            ->orderBy('date_time');

        $today = today();
        $tomorrow = today()->addDay();

        /** Skip today and tomorrow, which have their own dedicated buckets. */
        $withoutTodayAndTomorrow = function (Builder $query) use ($today, $tomorrow): void {
            $query->whereDate('date_time', '!=', $today)
                ->whereDate('date_time', '!=', $tomorrow);
        };

        $data = [
            'today' => ScheduleEventResource::collection(
                (clone $baseQuery)->whereDate('date_time', $today)->get()
            ),
            'tomorrow' => ScheduleEventResource::collection(
                (clone $baseQuery)->whereDate('date_time', $tomorrow)->get()
            ),
            'this_week' => ScheduleEventResource::collection(
                (clone $baseQuery)
                    ->whereBetween('date_time', [now()->startOfWeek(Carbon::SATURDAY), now()->endOfWeek(Carbon::FRIDAY)])
                    ->where($withoutTodayAndTomorrow)
                    ->get()
            ),
            'next_week' => ScheduleEventResource::collection(
                (clone $baseQuery)
                    ->whereBetween('date_time', [now()->addWeek()->startOfWeek(Carbon::SATURDAY), now()->addWeek()->endOfWeek(Carbon::FRIDAY)])
                    ->where($withoutTodayAndTomorrow)
                    ->get()
            ),
        ];

        return successResponse($data);
    }
}
