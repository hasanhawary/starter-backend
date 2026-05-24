<?php

namespace App\Http\Resources\Global\ActivityLog;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => resolveTrans(prepareModelType($this->subject_type)),
            'message' => $this->resolveMessage(),
            'event_key' => $this->event,
            'event' => resolveTrans($this->event, 'api'),
            'subject_type_key' => getModelKey($this->subject_type),
            'properties' => collect($this->properties)->map(function ($property) {
                return collect($property)->map(function ($value, $key) {
                    return [
                        'key' => resolveTrans($key, 'attributes'),
                        'value' => $value,
                    ];
                })->values()->toArray();
            }),

            'created_at' => $this->created_at->locale(app()->getLocale())->translatedFormat('d F Y h:i A'),
        ];
    }

    private function resolveMessage(): string
    {
        $causerName = $this->causer ? ($this->causer->name ?? $this->causer->full_name) : resolveTrans('automatic_causer', 'attributes');

        return resolveTrans('done', 'attributes')
            .' ActivityLogResource.php'.resolve('validation.attributes.'.$this->description)
            .' '.resolveTrans(getModelKey($this->subject_type), 'api')
            .' '.resolveTrans('id', 'attributes').' '.$this->subject?->id
            .' '.resolveTrans('causer', 'attributes').' '.$causerName;
    }
}
