<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\UpdateAdminProfileRequest;
use App\Http\Resources\Central\Admin\AdminResource;
use App\Models\Tenant\User;
use Exception;
use HasanHawary\MediaManager\Facades\Media;
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

        return successResponse(new AdminResource($user));
    }

    /**
     * @param UpdateAdminProfileRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateProfile(UpdateAdminProfileRequest $request): JsonResponse
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
