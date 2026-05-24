<?php

namespace Modules\Export\app\Http\Controllers;

use App\Filters\Global\DateFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Other\PageRequest;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Modules\Export\App\Enum\ExportFormatEnum;
use Modules\Export\app\Filters\ExportFilter;
use Modules\Export\App\Http\Requests\ExportRequest;
use Modules\Export\App\Http\Requests\ForceDeleteExportRequest;
use Modules\Export\App\Http\Resources\ExportResource;
use Modules\Export\App\Jobs\ExportToExcel;
use Modules\Export\App\Jobs\ExportToPdf;
use Modules\Export\App\Models\ExportFile;
use Modules\Export\App\Services\ExportFileService;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ExportJobController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods;

    public function __construct(private readonly ExportFileService $exportService)
    {
        parent::__construct();
        $this->model = ExportFile::class;
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('create-export-file'), only: ['export']),
            new Middleware(PermissionMiddleware::using('force-delete-export-file'), only: ['forceDelete']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', ExportFile::class);

        $query = app(Pipeline::class)
            ->send(ExportFile::query()->with(['creator'])->related())
            ->through([ExportFilter::class, DateFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, ExportResource::class));
    }

    public function export(ExportRequest $request): JsonResponse
    {
        $filters = $request->all();
        $format = ExportFormatEnum::from($filters['format']);

        $export = $this->exportService->createExport($filters, $format);
        $job = match ($format) {
            ExportFormatEnum::Excel => ExportToExcel::class,
            ExportFormatEnum::Pdf => ExportToPdf::class,
        };

        $job::dispatch($export->id, $filters, $filters['page'], "exports/{$filters['page']}");

        return successResponse(['export_id' => $export->id], msg: __('Export started successfully'));
    }

    public function forceDelete(ForceDeleteExportRequest $request): JsonResponse
    {
        $ids = $request->id ? Arr::wrap($request->id) : $request->ids;
        $deletedCount = $this->exportService->bulkDeleteExports($ids);

        return successResponse(
            ['deleted_count' => $deletedCount],
            msg: __('Exports deleted successfully')
        );
    }
}
