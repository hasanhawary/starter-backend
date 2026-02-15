<?php

namespace App\Http\Controllers\API\Profile;

use App\Http\Controllers\Controller;
use App\Services\Global\UserSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Update user settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $settingService = new UserSettingService(auth()->user());

        // Update each setting provided
        foreach ($request->all() as $key => $value) {
            $settingService->set($key, $value);
        }

        return successResponse(
            data: $settingService->all(),
            msg: __('api.settings_updated')
        );
    }

    /**
     * Get all user settings
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $settingService = new UserSettingService(auth()->user());
        $settings = $settingService->all();

        return successResponse(data: $settings);
    }
}
