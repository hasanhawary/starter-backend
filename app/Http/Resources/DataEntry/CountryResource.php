<?php

namespace App\Http\Resources\DataEntry;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'translation_name' => $this->name,
            'name' => $this->getTranslations('name'),
            'translation_nationality' => $this->nationality,
            'nationality' => $this->getTranslations('nationality'),
            'flag' => $this->flag,
            'code' => $this->code,
            'phone_code' => $this->phone_code,
            'phone_length' => $this->phone_length,
            'created_at' => $this->created_at,
        ];
    }
}
