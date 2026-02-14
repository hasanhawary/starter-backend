<?php

namespace App\Http\Controllers\API\Global\Setting;

use App\Filters\Setting\GroupFilter;
use App\Filters\Setting\KeyFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Resources\Global\Setting\SettingGroupResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;

class SettingController extends BaseController
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $settings = app(Pipeline::class)
            ->send(Setting::query()->when(auth()->check(), fn($q) => $q->public()))
            ->through([KeyFilter::class, GroupFilter::class])
            ->thenReturn()
            ->get();

        return successResponse(SettingGroupResource::organizeNested($settings));
    }
}
