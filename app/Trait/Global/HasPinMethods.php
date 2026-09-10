<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait HasPinMethods
{
    public function pin(): JsonResponse
    {
        $model = $this->resolvePinModel();

        $pivotData = property_exists($model, 'pinPivotData') ? $model->pinPivotData : [];
        $result = $model->pinUsers()->toggle([auth()->id() => $pivotData]);

        $key = empty($result['attached']) ? 'unpinned' : 'pinned';

        return successResponse(msg: $this->pinMessage($key));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function resolvePinModel(): Model
    {
        $item = array_values(request()->route()->parameters())[0] ?? null;

        return $item instanceof Model ? $item : $this->model::findOrFail($item);
    }

    private function pinMessage(string $key = 'pinned'): string
    {
        $module = getModelKey($this->model);

        return __("api.global.{$key}", ['item' => __("api.action_modules.{$module}")]);
    }
}
