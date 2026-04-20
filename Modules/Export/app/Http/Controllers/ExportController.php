<?php

namespace Modules\Export\App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use HasanHawary\ExportBuilder\ExportBuilder;
use Modules\Export\App\Http\Requests\ExportRequest;
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
