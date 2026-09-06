<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Http\Resources\Commercial\FleetOverviewResource;
use App\Services\Commercial\FleetService;
use Illuminate\Http\Request;

class FleetController extends BaseController
{
    public function __construct(
        private FleetService $fleetService
    ) {}

    public function overview(Request $request)
    {
        $overview = $this->fleetService->overview($request->all());

        return $this->successResponse(
            new FleetOverviewResource($overview)
        );
    }
}
