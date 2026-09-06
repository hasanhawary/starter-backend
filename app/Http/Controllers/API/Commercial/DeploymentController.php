<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Services\Commercial\DeploymentService;
use Illuminate\Http\Request;

class DeploymentController extends BaseController
{
    public function __construct(
        private DeploymentService $deploymentService
    ) {}

    public function index(Request $request)
    {
        $deployments = $this->deploymentService->listWithFilters($request->all());

        return $this->wrapPaginate($deployments);
    }
}
