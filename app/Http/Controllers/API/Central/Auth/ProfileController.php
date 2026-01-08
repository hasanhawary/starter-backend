<?php

namespace App\Http\Controllers\API\Central\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\UpdateAdminProfileRequest;
use App\Http\Resources\Central\Admin\AdminResource;
use App\Http\Resources\Central\Auth\SessionResource;
use App\Models\Central\Admin;
use Exception;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProfileController extends Controller
{
    /**
     * Return current user info and all active sessions
     *
     * @return JsonResponse
     */
    public function user(): JsonResponse
    {
        $user = Admin::with('roles.permissions')->find(auth('admin')->id());

        if (!$user) {
            return failResponse(msg: trans('api.user_not_found'));
        }

        // Include all active tokens/sessions
        $sessions = $user->tokens()->get(['id', 'name', 'last_used_at', 'created_at']);

        return successResponse([
            'user' => new AdminResource($user),
            'sessions' => SessionResource::collection($sessions),
        ]);
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
