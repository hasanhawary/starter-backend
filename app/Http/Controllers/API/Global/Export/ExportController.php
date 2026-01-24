<?php

namespace App\Http\Controllers\API\Global\Export;


use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Export\ExportRequest;
use HasanHawary\ExportBuilder\ExportBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends BaseController
{
    /**
     * @param ExportRequest $request
     * @return BinaryFileResponse
     */
    public function __invoke(ExportRequest $request): BinaryFileResponse
    {
        return (new ExportBuilder($this->filters($request)))->response();
    }

    /**
     * Prepare filters for the report service.
     *
     * @param ExportRequest $request
     * @return array
     */
    private function filters(ExportRequest $request): array
    {
        $filter = $request->validated();
        $filter['page'] = $request->page ?? 'user';
        $filter['related_type'] = 'count';

        return $filter;
    }
}
