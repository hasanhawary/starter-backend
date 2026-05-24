<?php

namespace Modules\Export\App\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Export\App\Enum\ExportFormatEnum;
use Modules\Export\App\Enum\ExportStatusEnum;
use Modules\Export\App\Models\ExportFile;

class ExportFileService
{
    public function createExport(array $data, ExportFormatEnum $format): ExportFile
    {
        $filters = array_merge($data, match ($format) {
            ExportFormatEnum::Excel => ['related_type' => 'count', 'format' => 'xlsx'],
            ExportFormatEnum::Pdf => ['lang' => app()->getLocale() ?? 'ar', 'format' => 'pdf'],
            default => throw new \Exception('Unexpected match value'),
        });

        return ExportFile::create([
            'exportable_type' => $data['page'],
            'format' => $format,
            'status' => ExportStatusEnum::Pending,
            'metadata' => ['filters' => $filters],
        ]);
    }

    public function markAsProcessing(ExportFile $export): void
    {
        $export->update([
            'status' => ExportStatusEnum::Processing,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(ExportFile $export, string $filePath, string $fileName): void
    {
        $export->update([
            'status' => ExportStatusEnum::Completed,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'completed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(ExportFile $export, string $errorMessage): void
    {
        $export->update([
            'status' => ExportStatusEnum::Failed,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function deleteExport(ExportFile $export): bool
    {
        if (! empty($export->getRawOriginal('file_path')) && Storage::exists($export->getRawOriginal('file_path'))) {
            Storage::delete($export->getRawOriginal('file_path'));
        }

        return $export->forceDelete();
    }

    public function bulkDeleteExports(array $exportIds): int
    {
        $exports = ExportFile::query()
            ->onlyTrashed()
            ->whereIn('id', $exportIds)
            ->where('created_by', auth()->id())
            ->get();

        $deletedCount = 0;

        foreach ($exports as $export) {
            if ($this->deleteExport($export)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
