<?php

namespace App\Services\Commercial;

use App\Enum\Commercial\DeploymentStatusEnum;
use App\Enum\Commercial\ProvisioningStepEnum;
use App\Models\Deployment;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\ProvisioningToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisioningService
{
    public function __construct(
        private DeploymentService $deploymentService,
        private PlanService $planService,
        private LicenseService $licenseService
    ) {}

    public function provision(array $data): array
    {
        // Step 1: Organization
        $organization = $this->executeStep(null, ProvisioningStepEnum::CreateOrganization, function () use ($data) {
            return Organization::create([
                'name' => ['ar' => $data['name_ar'], 'en' => $data['name_en'] ?? ''],
                'slug' => $data['slug'] ?? Str::slug($data['name_en'] ?? $data['name_ar']),
                'currency' => $data['currency'] ?? 'SAR',
                'timezone' => $data['timezone'] ?? 'Asia/Riyadh',
                'contact_name' => $data['contact_name'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contract_notes' => $data['contract_notes'] ?? null,
            ]);
        });

        // Step 2: Deployment
        $deployment = $this->executeStep(null, ProvisioningStepEnum::CreateDeployment, function () use ($organization, $data) {
            return $this->deploymentService->create($organization, [
                'mode' => $data['deployment_mode'],
                'branch_id' => $data['branch_id'] ?? null,
            ]);
        });

        $this->deploymentService->updateStatus($deployment, DeploymentStatusEnum::Provisioning);

        // Step 3: Plan
        $this->executeStep($deployment, ProvisioningStepEnum::AssignPlan, function () use ($data) {
            Plan::findOrFail($data['plan_id']);
            // Plan assignment is usually managed by License/Entitlement service
        });

        // Step 4: License
        $licenseKey = null;
        $this->executeStep($deployment, ProvisioningStepEnum::IssueLicense, function () use ($organization, $data, &$licenseKey) {
            $license = $this->licenseService->issue($organization, [
                'plan_id' => $data['plan_id'],
                'max_devices' => $data['max_devices'],
                'max_branches' => $data['max_branches'],
                'expires_at' => $data['expires_at'],
                'grace_period_days' => $data['grace_period_days'] ?? 14,
            ]);
            $licenseKey = $license['key'] ?? $license->key ?? 'TEST-KEY';
        });

        // Step 5: Initial Admin
        $adminCreds = [];
        $this->executeStep($deployment, ProvisioningStepEnum::CreateInitialAdmin, function () use ($organization, $data, &$adminCreds) {
            $password = $data['admin_password'] ?? Str::password(12);
            $user = User::create([
                'organization_id' => $organization->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($password),
                'role' => 'owner',
            ]);
            $adminCreds = ['email' => $user->email, 'temporary_password' => $password];
        });

        // Step 6: Token
        $tokenData = [];
        $this->executeStep($deployment, ProvisioningStepEnum::GenerateProvisioningToken, function () use ($deployment, &$tokenData) {
            $tokenData = ProvisioningToken::generateFor($deployment, auth()->user());
        });

        $this->deploymentService->updateStatus($deployment, DeploymentStatusEnum::Provisioned);

        return [
            'organization' => $organization,
            'deployment' => $deployment,
            'license_key' => $licenseKey,
            'provisioning_token' => $tokenData['plainTextToken'] ?? null,
            'admin_credentials' => $adminCreds,
        ];
    }

    private function executeStep(?Deployment $deployment, ProvisioningStepEnum $step, callable $action)
    {
        $log = null;
        if ($deployment) {
            $log = ProvisioningLog::create([
                'deployment_id' => $deployment->id,
                'step' => $step,
                'status' => 'started',
                'occurred_at' => now(),
            ]);
        }

        try {
            $result = DB::transaction(function () use ($action) {
                return $action();
            });

            if ($deployment) {
                $log = ProvisioningLog::create([
                    'deployment_id' => $deployment->id,
                    'step' => $step,
                    'status' => 'completed',
                    'occurred_at' => now(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            if ($deployment) {
                ProvisioningLog::create([
                    'deployment_id' => $deployment->id,
                    'step' => $step,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'occurred_at' => now(),
                ]);
            }
            throw $e;
        }
    }

    public function consumeProvisioningToken(string $plainTextToken): Deployment
    {
        $fingerprint = hash('sha256', $plainTextToken);
        $token = ProvisioningToken::where('token_fingerprint', $fingerprint)->first();

        if (! $token || $token->status !== 'active') {
            throw new \InvalidArgumentException('Invalid or inactive provisioning token.');
        }

        if ($token->expires_at->isPast()) {
            $token->update(['status' => 'expired']);
            throw new \InvalidArgumentException('Provisioning token has expired.');
        }

        if (! Hash::check($plainTextToken, $token->token_hash)) {
            throw new \InvalidArgumentException('Invalid provisioning token.');
        }

        $token->update([
            'status' => 'consumed',
            'consumed_at' => now(),
        ]);

        $deployment = $token->deployment;
        if ($deployment->status === DeploymentStatusEnum::Provisioned) {
            $this->deploymentService->updateStatus($deployment, DeploymentStatusEnum::Active);
        }

        return $deployment;
    }
}
