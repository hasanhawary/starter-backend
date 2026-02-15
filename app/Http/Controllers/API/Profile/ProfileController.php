<?php

namespace App\Http\Controllers\API\Profile;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\Auth\SessionResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\Global\UserSettingService;
use Exception;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProfileController extends BaseController
{
    /**
     * Return current user info and all active sessions
     *
     * @return JsonResponse
     */
    public function user(): JsonResponse
    {
        $user = User::with('roles.permissions')->find(auth()->id());

        if (!$user) {
            return failResponse(trans('api.user_not_found'));
        }

        // Include all active tokens/sessions
        $sessions = $user->tokens()->get(['id', 'name', 'last_used_at', 'created_at']);

        // Include user settings
        $settingService = new UserSettingService($user);
        $settings = $settingService->all();

        return successResponse([
            'user' => new UserResource($user),
            'sessions' => SessionResource::collection($sessions),
            'settings' => $settings,
        ]);
    }

    /**
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = Arr::except(array_filter($request->validated(), fn($value) => $value !== null), 'avatar');

        auth()->user()->update($data);

        return successResponse(auth()->user()->refresh(), trans('api.profile_updated'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function destroyAvatar(Request $request): JsonResponse
    {
        if (empty(auth()->user()->getOriginal('avatar'))) {
            return failResponse(trans('api.no_avatar_found'));
        }

        Media::delete($request->avatar);

        auth()->user()->update(['avatar' => null]);

        return successResponse(auth()->user()->refresh(), trans('api.avatar_deleted'));
    }
}
