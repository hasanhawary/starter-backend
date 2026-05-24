<?php

namespace Modules\Export\App\Jobs;

use HasanHawary\ExportBuilder\ExportBuilder;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Export\App\Models\ExportFile;
use Modules\Export\App\Services\ExportFileService;
use Throwable;

class ExportToExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $exportId,
        protected array $filters,
        protected string $exportType = 'data',
        protected string $folderPath = 'exports'
    ) {}

    public function handle(ExportFileService $exportService): void
    {
        $export = ExportFile::find($this->exportId);

        if (! $export) {
            return;
        }

        try {
            $exportService->markAsProcessing($export);

            $response = (new ExportBuilder($this->filters()))->response();
            $realPath = $response->getFile()->getRealPath();

            $fileName = $this->generateFileName();
            $path = Media::withName(fn () => $fileName)->upload($realPath, $this->folderPath);

            $exportService->markAsCompleted($export, $path, Media::meta($path)->basename());

        } catch (Throwable $e) {
            $exportService->markAsFailed($export, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare filters for the export.
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * Generate file name for the export.
     */
    protected function generateFileName(): string
    {
        $name = $this->filters['name'] ?? $this->exportType;
        $timestamp = time();

        return "{$name}_export_{$timestamp}.xlsx";
    }
}
