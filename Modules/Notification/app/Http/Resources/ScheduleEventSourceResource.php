<?php

namespace Modules\Notification\app\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ScheduleEventSourceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => class_basename($this->resource),
            'type_path' => Str::lower(Str::plural(class_basename($this->resource))),
            'created_at' => $this->created_at,
        ];
    }
}
