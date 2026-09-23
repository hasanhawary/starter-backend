<?php

namespace Modules\Showcase\app\Http\Resources;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;

class ShowcaseNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'display_type' => ShowcaseNoteTypeEnum::resolve($this->type),
            'body' => $this->body,
            'is_pinned' => $this->is_pinned,
            'author' => $this->whenLoaded('author', fn () => new BasicUserResource($this->author), ['id' => $this->author_id]),
            'created_at' => $this->created_at,
        ];
    }
}
