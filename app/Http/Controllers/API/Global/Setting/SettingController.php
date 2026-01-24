<?php

namespace App\Http\Controllers\API\Global\Setting;

use App\Filters\Setting\GroupFilter;
use App\Filters\Setting\KeyFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Setting\SettingRequest;
use App\Http\Requests\Global\Setting\TestCredentialsRequest;
use App\Http\Resources\Global\Setting\SettingResource;
use App\Mail\BasicMail;
use App\Models\Setting;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Middleware\PermissionMiddleware;

class SettingController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('update-setting'), only: ['update']),
        ];
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $baseQuery = Setting::query();

        if (auth()->check()) {
            $baseQuery = $baseQuery->public();
        }

        $query = app(Pipeline::class)
            ->send($baseQuery)
            ->through([KeyFilter::class, GroupFilter::class])
            ->thenReturn();

        $settings = $query->get()->groupBy('group');

        // Transform each setting into a resource
        $settingsResource = $settings->map(function ($group) {
            return SettingResource::collection($group);
        });

        return successResponse($settingsResource);
    }

    /**
     * @param SettingRequest $request
     * @return JsonResponse
     */
    public function update(SettingRequest $request): JsonResponse
    {
        foreach ($request->settings as $item) {
            $value = !empty($item['value']) ? $item['value'] : null;

            if ($value && is_file($value)) {
                $value = Media::upload($item['value'], 'settings');
            }

            $setting = Setting::updateOrCreate([
                'key' => $item['key'],
                'group' => $item['group'],
            ], [
                'value' => $value,
            ]);

            if ($setting->is_env) {
                updateDotEnv([strtoupper($item['key']) => $value]);
            }
        }

        return successResponse(msg: __('api.updated_success'));
    }

    /**
     * @param TestCredentialsRequest $request
     * @return JsonResponse
     */
    public function testMailCredentials(TestCredentialsRequest $request): JsonResponse
    {
        Mail::to($request->email)->send(new BasicMail(null, [
            'title' => 'test_credentials',
            'msg' => $request->body,
        ]));

        return successResponse(msg: __('api.test_credentials_success'));
    }
}
