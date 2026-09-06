<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Commercial\ActivateLicenseRequest;
use App\Http\Requests\Commercial\RecoverLicenseRequest;
use App\Models\DeviceActivation;
use App\Models\EdgeConfiguration;
use App\Services\Commercial\LicenseLeaseService;
use App\Services\Commercial\LicenseService;
use App\Services\Edge\EdgeConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicenseController extends BaseController
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly LicenseLeaseService $leaseService,
        private readonly EdgeConfigurationService $configurationService,
    ) {
        parent::__construct();
    }

    public function state(Request $request): JsonResponse
    {
        $configuration = $this->configurationService->current();
        $status = $this->leaseService->currentStatus($configuration);
        $isActivated = in_array($status['state'], ['ACTIVE', 'GRACE'], true);

        return successResponse([
            'activation_required' => ! $isActivated,
            'installation_ready' => $configuration !== null,
            'state' => $status['state'],
            'usable' => $status['usable'],
            'installation_id' => $configuration?->installation_id,
        ]);
    }

    public function activate(ActivateLicenseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $configuration = $this->configurationService->current();

        $data['installation_id'] ??= $configuration?->installation_id ?? (string) Str::uuid();
        $data['device_name'] ??= $configuration?->edge_name ?: 'Main Register';
        $data['platform'] ??= strtolower(PHP_OS_FAMILY);
        $data['app_version'] ??= config('project.project.version');

        $result = $this->licenseService->activate($data);

        return successResponse([
            ...$this->licenseService->status($result['activation']),
            'activation_token' => $result['activation_token'],
            'lease' => $result['lease'],
        ], __('api.license_activated'));
    }

    public function recover(RecoverLicenseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $configuration = $this->configurationService->current();

        $data['installation_id'] ??= $configuration?->installation_id ?? (string) Str::uuid();
        $data['device_name'] ??= $configuration?->edge_name ?: 'Main Register';
        $data['platform'] ??= strtolower(PHP_OS_FAMILY);
        $data['app_version'] ??= config('project.project.version');

        $result = $this->licenseService->recoverInstallation($data);

        return successResponse([
            ...$this->licenseService->status($result['activation']),
            'activation_token' => $result['activation_token'],
            'lease' => $result['lease'],
            'old_installation_id' => $result['old_installation_id'],
        ], __('api.license_recovered'));
    }

    public function authorizeReplacement(Request $request, DeviceActivation $activation): JsonResponse
    {
        $this->authorizeActivationAccess($request, $activation);

        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $result = $this->licenseService->authorizeReplacement(
            $activation,
            $request->input('reason'),
            $request->user()
        );

        return successResponse([
            'recovery_token' => $result['token'],
            'expires_at' => $result['expires_at']->toISOString(),
            'activation' => $this->licenseService->status($result['activation']),
        ], __('api.replacement_authorized'));
    }

    public function deactivate(Request $request, DeviceActivation $activation): JsonResponse
    {
        $this->authorizeActivationAccess($request, $activation);

        $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $this->licenseService->deactivateInstallation($activation, $request->input('reason'));

        return successResponse([], __('api.updated_success'));
    }

    private function authorizeActivationAccess(Request $request, DeviceActivation $activation): void
    {
        $activation->loadMissing('license');
        $branch = $request->attributes->get('pos_branch');
        $user = $request->user();
        $orgId = $branch?->organization_id ?? $user?->organization_id;

        if ($orgId && $activation->license->organization_id !== $orgId) {
            abort(403, __('api.license_organization_mismatch'));
        }
    }

    public function activations(Request $request): JsonResponse
    {
        $user = $request->user();
        $branch = $request->attributes->get('pos_branch');
        $organizationId = $branch?->organization_id ?? $user?->organization_id;

        $activations = DeviceActivation::query()
            ->whereHas('license', fn ($q) => $q->where('organization_id', $organizationId))
            ->with(['device', 'license'])
            ->latest('activated_at')
            ->get()
            ->map(fn (DeviceActivation $act) => $this->licenseService->status($act));

        return successResponse($activations);
    }

    public function status(Request $request): JsonResponse
    {
        return successResponse($this->licenseService->status($this->activation($request)));
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $activation = $this->licenseService->heartbeat($this->activation($request));

        return successResponse($this->licenseService->status($activation), __('api.activation_checkin_success'));
    }

    public function lease(Request $request): JsonResponse
    {
        $activation = $this->activation($request);

        return successResponse(['lease' => $this->leaseService->issue($activation)]);
    }

    public function localStatus(Request $request): JsonResponse
    {
        $configuration = $request->attributes->get('edge_configuration')
            ?: EdgeConfiguration::query()->where('branch_id', $request->attributes->get('pos_branch')?->getKey())->first();

        return successResponse($this->leaseService->currentStatus($configuration));
    }

    private function activation(Request $request): DeviceActivation
    {
        return $request->attributes->get('commercial_activation');
    }
}
