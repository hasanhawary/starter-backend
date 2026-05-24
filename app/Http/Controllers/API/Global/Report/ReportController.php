<?php

namespace App\Http\Controllers\API\Global\Report;

use App\Enum\Global\ReportChartTypeEnum;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Report\ReportRequest;
use HasanHawary\ReportBuilder\ReportBuilder;
use Illuminate\Http\JsonResponse;

class ReportController extends BaseController
{
    /**
     * @return JsonResponse*
     */
    public function __invoke(ReportRequest $request): JsonResponse
    {
        $report = new ReportBuilder($this->filters($request));

        return successResponse($report->response());
    }

    private function filters(ReportRequest $request): array
    {
        $filter = $request->validated();
        $filter['page'] = $request->page ?? 'user';
        $filter['apply_date'] = $request->start || $request->end;
        $filter['prefer_chart'] = $request->prefer_chart ?? ReportChartTypeEnum::default();

        return $filter;
    }
}
