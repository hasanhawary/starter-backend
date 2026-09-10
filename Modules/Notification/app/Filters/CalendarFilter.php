<?php

namespace Modules\Notification\app\Filters;

use App\Filters\BaseFilter;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Modules\Notification\app\Enum\SystemEventModuleEnum;

class CalendarFilter extends BaseFilter
{
    public function handle($request, Closure $next): Builder
    {
        $query = $next($request);

        $query->when(request()->input('module'), function ($q) {
            $q->whereHas('notificationEvent', function ($q) {
                $q->whereHas('systemEvent', function ($q) {
                    $q->where('module', SystemEventModuleEnum::tryFrom(request()->input('module')));
                });
            });
        });

        $this->applyDateFilter($query);

        return $query;
    }

    /**
     * Filter on the `date_time` column using the shared range keys
     * (`date_from` / `date_to`), like every other module.
     *
     * The single `date` param is kept because the calendar's day view asks for
     * exactly one day; it resolves to that day's own range.
     */
    private function applyDateFilter(Builder $query): void
    {
        $this->applyDateRangeFilter($query, 'date', 'date_time');

        if (! empty(request('date'))) {
            $day = Carbon::parse(request('date'));

            $query->whereBetween('date_time', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
        }
    }
}
