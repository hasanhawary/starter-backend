<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    /**
     * Transform the session token into an array
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $meta = $this->meta ?? [];

        return [
            'id' => $this->id,
            'ip_address' => $meta['ip'] ?? null,
            'user_agent' => $meta['ua'] ?? null,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'device' => $meta['device'] ?? null,
            'platform' => $meta['platform'] ?? null,
            'timezone' => $meta['timezone'] ?? null,
            'language' => $meta['language'] ?? null,
            'screen' => $meta['screen'] ?? null,
        ];
    }
}
