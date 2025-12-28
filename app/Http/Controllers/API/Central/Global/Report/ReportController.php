<?php

namespace App\Http\Controllers\API\Global\Report;

use App\Enum\Global\ReportChartTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Global\Report\ReportRequest;
use HasanHawary\ReportBuilder\ReportBuilder;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * @param ReportRequest $request
     * @return JsonResponse*
     */
    public function __invoke(ReportRequest $request): JsonResponse
    {
        $report = new ReportBuilder($this->filters($request));

        return successResponse($report->response());
    }

    /**
     * @param ReportRequest $request
     * @return array
     */
    private function filters(ReportRequest $request): array
    {
        $filter = $request->validated();
        $filter ['page'] = $request->page ?? 'user';
        $filter ['apply_date'] = $request->start || $request->end;
        $filter ['prefer_chart'] = $request->prefer_chart ?? ReportChartTypeEnum::default();

        return $filter;
    }
}
