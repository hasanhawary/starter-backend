<?php

namespace App\Http\Controllers\API\Global\Captcha;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Captcha\CaptchaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends BaseController
{
    public function generateCaptcha(): JsonResponse
    {
        $captchaText = Str::random(5);
        $captchaCode = Str::uuid7()->toString();
        $cacheKey = 'captcha_'.$captchaCode;

        Cache::put($cacheKey, $captchaText, now()->addMinutes(10));

        return response()->json([
            'token' => $cacheKey,
            'captcha_code' => $captchaText,
        ]);
    }

    public function verifyCaptcha(CaptchaRequest $request): JsonResponse
    {
        $storedCaptcha = Cache::get($request->token);

        if ($storedCaptcha && $storedCaptcha === $request->captcha) {
            Cache::forget($request->token);

            return successResponse(msg: 'Captcha Verified');
        }

        return failResponse('Captcha Incorrect', code: 422);
    }
}
