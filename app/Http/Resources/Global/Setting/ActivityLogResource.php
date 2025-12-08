<?php

namespace App\Http\Resources\Global\Setting;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
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

    /**
     * @return string
     */
    private function resolveMessage(): string
    {
        $causerName = $this->causer ? ($this->causer->name ?? $this->causer->full_name) : resolveTrans('automatic_causer', 'attributes');

        info($causerName);
        return resolveTrans('done', 'attributes')
            . ' ' . trans('attributes.' . $this->description)
            . ' ' . resolveTrans(getModelKey($this->subject_type), 'api')
            . ' ' . resolveTrans('id', 'attributes') . ' ' . $this->subject?->id
            . ' ' . resolveTrans('causer', 'attributes') . ' ' . $causerName;
    }
}
