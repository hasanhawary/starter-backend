<?php

namespace App\Http\Controllers\API\Global\Help;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Help\HelpConfigRequest;
use App\Http\Requests\Global\Help\HelpEnumRequest;
use App\Http\Requests\Global\Help\HelpModelRequest;
use HasanHawary\LookupManager\Facades\Lookup;
use Illuminate\Http\JsonResponse;

class HelpController extends BaseController
{
    /**
     * Retrieves and transforms data from specified models based on the provided request.
     */
    public function models(HelpModelRequest $request): JsonResponse
    {
        $result = Lookup::getModels($request->validated());

        return successResponse($result);
    }

    /**
     * Retrieves a list of enums based on the request parameters.
     */
    public function enums(HelpEnumRequest $request): JsonResponse
    {
        $result = Lookup::getEnums($request->validated());

        return successResponse($result);
    }

    /**
     * Retrieves whitelisted config data based on the request parameters.
     */
    public function configs(HelpConfigRequest $request): JsonResponse
    {
        $result = Lookup::getConfigs($request->validated());

        return successResponse($result);
    }
}
