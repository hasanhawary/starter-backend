<?php

namespace App\Http\Controllers\API\Central\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\UpdateAdminProfileRequest;
use App\Http\Resources\Central\Admin\AdminResource;
use App\Models\Central\Admin;
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
        $admin = Admin::with('roles.permissions')->find(auth()->id());

        return successResponse(new AdminResource($admin));
    }

    /**
     * @param UpdateAdminProfileRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateProfile(UpdateAdminProfileRequest $request): JsonResponse
    {
        $data = Arr::except(array_filter($request->validated(), fn($value) => $value !== null), 'avatar');

        auth()->user('admin')->update($data);

        return successResponse(auth()->user('admin')->refresh(), trans('api.profile_updated'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function destroyAvatar(Request $request): JsonResponse
    {
        Media::delete($request->avatar);

        auth()->user('admin')->update(['avatar' => null]);

        return successResponse(auth()->user('admin')->refresh(), trans('api.profile_updated'));
    }
}
