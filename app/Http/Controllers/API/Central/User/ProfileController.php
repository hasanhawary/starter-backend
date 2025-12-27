<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserProfileRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use HasanHawary\MediaManager\Facades\Media;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProfileController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function user(): JsonResponse
    {
        $user = User::with('roles.permissions:name')->find(auth()->id());

        return successResponse(new UserResource($user));
    }

    /**
     * @param UpdateUserProfileRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
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
        Media::delete($request->avatar);

        auth()->user()->update(['avatar' => null]);

        return successResponse(auth()->user()->refresh(), trans('api.profile_updated'));
    }
}
