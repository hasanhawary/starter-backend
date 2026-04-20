<?php

namespace Modules\Export\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Export\App\Enum\ExportFormatEnum;
use Modules\Export\App\Enum\ExportStatusEnum;
use Modules\Export\app\Services\ExportRegistry;

class ExportResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $exportableType = class_basename($this->exportable_type);
        return [
            'id' => $this->id,
            'display_exportable_type' => ExportRegistry::getTitle($exportableType),
            'exportable_type' => $exportableType,
            'exportable_id' => $this->exportable_id,
            'display_format' => ExportFormatEnum::resolve($this->format),
            'format' => $this->format,
            'display_status' => ExportStatusEnum::resolve($this->status),
            'status' => $this->status,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'error_message' => $this->error_message,
            'metadata' => $this->metadata ?? [],
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'creator' => $this->whenLoaded('creator', fn() => new $this->creatorResource($this->creator), ['id' => $this->created_by]),
        ];
    }
}
