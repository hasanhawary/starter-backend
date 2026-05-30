<?php

namespace AiChat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->name(),
            'description' => $this->resource->description(),
            'schema' => $this->resource->schema(),
        ];
    }
}
