<?php

namespace App\Http\Resources\Global\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => __('api.'.$this->group.'.'.$this->key),
            'label_group' =>__('api.'.$this->group.'.'.'label'),
            'placeholder' =>__('api.'.$this->group.'.'.$this->key),
            'value' => $this->value,
            'group' => $this->group,
            'model' => $this->model,
            'is_env'=>$this->is_env,
            'type'=>$this->type,
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at
        ];
    }
}
