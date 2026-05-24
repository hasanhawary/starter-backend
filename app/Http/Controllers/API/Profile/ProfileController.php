<?php

namespace App\Http\Controllers\API\Profile;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateSettingRequest;
use App\Http\Resources\Auth\SessionResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Exception;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProfileController extends BaseController
{
    /**
     * Return current user info and all active sessions
     */
    public function user(): JsonResponse
    {
        $user = User::with(['roles.permissions', 'settings'])->find(auth()->id());

        if (! $user) {
            return failResponse(trans('api.user_not_found'));
        }

        // Include all active tokens/sessions
        $sessions = $user->tokens()->select('id', 'meta')->get();

        return successResponse([
            'sessions' => SessionResource::collection($sessions),
            'user' => new UserResource($user),
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = Arr::except(array_filter($request->validated(), fn ($value) => $value !== null), 'avatar');
        $data['avatar'] = Media::replace($user->avatar)->upload($request->file('avatar'), 'users');

        $user->update($data);

        return successResponse($user->refresh()->load('roles'), trans('api.profile_updated'));
    }

    /**
     * @throws Exception
     */
    public function updateSetting(UpdateSettingRequest $request): JsonResponse
    {
        $user = auth()->user();
        $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            ['setting' => $request->setting]
        );

        return successResponse($user->refresh()->load('settings'), trans('api.profile_updated'));
    }

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
