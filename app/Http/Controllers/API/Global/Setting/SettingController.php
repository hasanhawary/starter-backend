<?php

namespace App\Http\Controllers\API\Global\Setting;

use App\Filters\Setting\GroupFilter;
use App\Filters\Setting\KeyFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Resources\Global\Setting\SettingResource;
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
        $baseQuery = Setting::query();

        if (auth()->check()) {
            $baseQuery = $baseQuery->public();
        }

        $query = app(Pipeline::class)
            ->send($baseQuery)
            ->through([KeyFilter::class, GroupFilter::class])
            ->thenReturn();

        $settings = $query->get()->groupBy('group');

        // Transform each setting into a resource
        $settingsResource = $settings->map(function ($group) {
            return SettingResource::collection($group);
        });

        return successResponse($settingsResource);
    }
}
