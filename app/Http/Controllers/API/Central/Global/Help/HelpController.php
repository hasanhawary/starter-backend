<?php

namespace App\Http\Controllers\API\Central\Global\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Global\Help\HelpEnumRequest;
use App\Http\Requests\Central\Global\Help\HelpModelRequest;
use HasanHawary\LookupManager\Facades\Lookup;
use Illuminate\Http\JsonResponse;

class HelpController extends Controller
{
    /**
     * Retrieves and transforms data from specified models based on the provided request.
     *
     * @param HelpModelRequest $request
     * @return JsonResponse
     */
    public function models(HelpModelRequest $request): JsonResponse
    {
        $result = Lookup::getModels($request->all());

        return successResponse($result);
    }

    /**
     * Retrieves a list of enums based on the request parameters.
     *
     * @param HelpEnumRequest $request
     * @return JsonResponse
     */
    public function enums(HelpEnumRequest $request): JsonResponse
    {
        $result = Lookup::getEnums($request->all());

        return successResponse($result);
    }
}
