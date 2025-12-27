<?php

namespace App\Http\Controllers\API\Global\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CaptchaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function generateCaptcha(): JsonResponse
    {
        $captchaText = Str::random(5);
        $captchaCode = uniqid();
        $cacheKey = 'captcha_' . $captchaCode;

        Cache::put($cacheKey, $captchaText, now()->addMinutes(10));

        return response()->json([
            'token' => $cacheKey,
            'captcha_code' => $captchaText
        ]);
    }

    /**
     * @param CaptchaRequest $request
     * @return JsonResponse
     */
    public function verifyCaptcha(CaptchaRequest $request) : JsonResponse
    {
        $storedCaptcha = Cache::get($request->token);

        if ($storedCaptcha && $storedCaptcha === $request->captcha) {
            // Cache::forget($request->token);
            return successResponse(msg: 'Captcha Verified');
        }

        return failResponse(msg: 'Captcha Incorrect', code: 422);
    }
}
