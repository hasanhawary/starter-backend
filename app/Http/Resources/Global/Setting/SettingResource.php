<?php

namespace App\Http\Resources\Global\Setting;

use App\Enum\Global\SettingTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->value;
        $group = Str::afterLast($this->group, '.');

        return [
            'id' => $this->id,
            'key' => $this->key,

            'value' => $value,
            'translated_value' => is_array($value)
                ? ($value[app()->getLocale()] ?? $value)
                : $value,

            'label' => $this->getTranslations('label'),
            'translated_label' => $this->label,

            'placeholder' => $this->getTranslations('placeholder'),
            'translated_placeholder' => $this->placeholder,

            'group' => $this->group,
            'display_group' => resolveTrans("settings_trans.$group"),

            'type' => $this->type,
            'display_type' => SettingTypeEnum::resolve($this->type),

            'is_env' => (int) $this->is_env,
            'is_multi_lang' => (int) $this->is_multi_lang,

            'last_updated_at' => $this->updated_at,
        ];
    }
}
