<?php

namespace App\Http\Controllers\API\Global\Setting;

use App\Filters\Setting\GroupFilter;
use App\Filters\Setting\KeyFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Setting\SettingRequest;
use App\Http\Resources\Global\Setting\SettingGroupResource;
use App\Models\Setting;
use App\Services\Global\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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

    public function index(): JsonResponse
    {
        $settings = app(Pipeline::class)
            ->send(Setting::query()->when(auth()->check(), fn ($q) => $q->public()))
            ->through([KeyFilter::class, GroupFilter::class])
            ->thenReturn()
            ->get();

        return successResponse(SettingGroupResource::organizeNested($settings));
    }

    public function update(SettingRequest $request): JsonResponse
    {
        $this->service->updateSettings($request->validated()['settings']);

        return successResponse(msg: __('api.updated_success'));
    }
}
