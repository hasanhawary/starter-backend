<?php

namespace AiChat\Http\Resources;

use AiChat\Response\FinalResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->role === 'assistant'
                ? app(FinalResponseFormatter::class)->format((string) $this->content, '')
                : $this->content,
            'tool_calls' => $this->whenLoaded('toolCalls', fn () => $this->tool_calls),
            'usage' => $this->when(isset($this->usage), fn () => $this->usage),
            'created_at' => $this->created_at,
        ];
    }
}
