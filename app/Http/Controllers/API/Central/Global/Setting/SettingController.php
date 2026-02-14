<?php

namespace App\Http\Controllers\API\Central\Global\Setting;

use App\Filters\Central\Setting\GroupFilter;
use App\Filters\Central\Setting\KeyFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\Global\Setting\SettingRequest;
use App\Http\Resources\Central\Global\Setting\SettingGroupResource;
use App\Models\Central\Setting;
use App\Services\Global\SettingService;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use JsonException;
use Spatie\Permission\Middleware\PermissionMiddleware;

class SettingController extends BaseController implements HasMiddleware
{
    public function __construct(public SettingService $service)
    {
        parent::__construct();
    }

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
        $settings = app(Pipeline::class)
            ->send(Setting::query()->when(auth()->check(), fn($q) => $q->public()))
            ->through([KeyFilter::class, GroupFilter::class])
            ->thenReturn()
            ->get();

        return successResponse(SettingGroupResource::organizeNested($settings));
    }

    /**
     * @param SettingRequest $request
     * @return JsonResponse
     * @throws JsonException
     */
    public function update(SettingRequest $request): JsonResponse
    {
        foreach ($request->validated()['settings'] as $item) {
            // Find the existing setting by key and group
            $setting = Setting::where('key', $item['key'])
                ->where('group', $item['group'])
                ->first();

            if (!$setting) {
                continue;
            }

            // Normalize the value (handles media uploads or special types)
            $value = $this->normalizeValue($item['value'], $setting->type);

            $setting->update(['value' => $value]);

            // If this setting should be synced with the .env file, do it
            if ($setting->is_env) {
                $this->syncEnv($value);
            }
        }

        $this->service->clearCache();

        return successResponse(msg: __('api.updated_success'));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function normalizeValue(mixed $value, $type): mixed
    {
        if ($value && in_array($type, ['imageUploader', 'file'])) {
            return Media::replace($value)->upload($value, 'settings');
        }

        return $value;
    }

    /**
     * @throws JsonException
     */
    protected function syncEnv(mixed $value): void
    {
        updateDotEnv([
            strtoupper($value['key']) => is_array($value)
                ? json_encode($value, JSON_THROW_ON_ERROR)
                : $value
        ]);
    }
}
