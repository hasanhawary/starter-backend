<?php

namespace App\Http\Resources\Central\Global\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->value;
        $lang = app()->getLocale();

        if (is_array($value)) {
            $displayValue = $value[$lang] ?? $value;
        }

        return [
            'id' => $this->id,
            'key' => $this->key,
            'translation_value' => $displayValue ?? $value,
            'value' => $this->value,

            'translation_label' => $this->label,
            'label' => $this->getTranslations('label'),

            'translation_placeholder' => $this->placeholder,
            'placeholder' => $this->getTranslations('placeholder'),

            'display_group' => resolveTrans($this->group),
            'group' => $this->group,

            'is_env' => $this->is_env,
            'is_multi_lang' => $this->is_multi_lang,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
