<?php

namespace App\Http\Controllers\API\Central\Auth;

use App\Http\Controllers\Controller;
use App\Models\Central\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Logout user from current device, all devices, or a specific session
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = auth('admin')->user();

        if (!$user) {
            return failResponse(msg: trans('api.user_not_found'));
        }

        // Logout from all devices
        if ($request->boolean('all_devices')) {
            $user->tokens()->delete();
            return successResponse(msg: trans('api.user_logged_out_all_devices'));
        }

        // Logout from a specific session
        if ($request->filled('token_id')) {
            $deleted = $user->tokens()->where('id', $request->token_id)->delete();

            if ($deleted) {
                return successResponse(msg: trans('api.user_logged_out_specific'));
            }

            return failResponse(msg: trans('api.token_not_found'));
        }

        // Logout from current device only
        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $user->tokens()->where('id', $currentToken->id)->delete();
        }

        return successResponse(msg: trans('api.user_logged_out'));
    }
}
