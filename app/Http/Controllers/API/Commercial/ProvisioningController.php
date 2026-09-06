<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Commercial\ProvisionClientRequest;
use App\Http\Resources\Commercial\CommercialOrganizationDetailResource;
use App\Services\Commercial\ProvisioningService;

class ProvisioningController extends BaseController
{
    public function __construct(
        private ProvisioningService $provisioningService
    ) {}

    public function provision(ProvisionClientRequest $request)
    {
        $result = $this->provisioningService->provision($request->validated());

        return $this->successResponse([
            'organization' => new CommercialOrganizationDetailResource($result['organization']),
            'license_key' => $result['license_key'],
            'deployment_id' => $result['deployment']->id,
            'provisioning_token' => $result['provisioning_token'],
            'admin_credentials' => $result['admin_credentials'],
        ], 'Client provisioned successfully.', 201);
    }
}
