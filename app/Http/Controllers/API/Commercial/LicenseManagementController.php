<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Commercial\ChangeLicensePlanRequest;
use App\Http\Requests\Commercial\PreviewPlanChangeRequest;
use App\Http\Requests\Commercial\RenewLicenseRequest;
use App\Http\Requests\Commercial\UpdateLicenseEntitlementsRequest;
use App\Http\Requests\Commercial\UpdateLicenseLimitsRequest;
use App\Http\Requests\Commercial\UpdateLicenseStatusRequest;
use App\Models\CommercialLicenseEvent;
use App\Models\License;
use App\Models\Plan;
use App\Services\Commercial\EntitlementService;
use App\Services\Commercial\LicenseLeaseService;
use App\Services\Commercial\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicenseManagementController extends BaseController
{
    public function __construct(
        private readonly PlanService $planService,
        private readonly EntitlementService $entitlementService,
        private readonly LicenseLeaseService $leaseService,
    ) {
        parent::__construct();
    }

    public function previewPlan(PreviewPlanChangeRequest $request, License $license): JsonResponse
    {
        $this->authorizeLicenseBranch($request, $license);
        $targetPlan = $this->resolveTargetPlan($request);

        $preview = $this->planService->previewPlanChange(
            $license,
            $targetPlan,
            $request->validated('entitlement_overrides'),
            $request->validated('limit_overrides'),
        );

        return successResponse($preview);
    }

    public function changePlan(ChangeLicensePlanRequest $request, License $license): JsonResponse
    {
        $this->authorizeLicenseBranch($request, $license);
        $targetPlan = $this->resolveTargetPlan($request);

        $result = $this->planService->changePlan(
            $license,
            $targetPlan,
            $request->validated(),
            $request->user(),
        );

        return successResponse([
            'license' => $this->licenseData($result['license']),
            'preview' => $result['preview'],
        ], 'License plan changed successfully.');
    }

    public function updateEntitlements(UpdateLicenseEntitlementsRequest $request, License $license): JsonResponse
    {
        $this->authorizeLicenseBranch($request, $license);

        return DB::transaction(function () use ($request, $license): JsonResponse {
            $lockedLicense = License::query()->lockForUpdate()->findOrFail($license->getKey());
            $newEntitlements = $this->entitlementService->normalize($request->validated('entitlements'));

            $before = [
                'features' => $lockedLicense->features,
                'entitlement_overrides' => $lockedLicense->entitlement_overrides,
            ];

            // Calculate overrides relative to the current plan defaults if plan exists
            $plan = $lockedLicense->planModel() ?: ($lockedLicense->plan_code ? $this->planService->findByCode($lockedLicense->plan_code) : null);
            $overrides = [];
            if ($plan) {
                $planDefaults = $this->entitlementService->normalize($plan->default_entitlements);
                foreach ($newEntitlements as $k => $v) {
                    if (($planDefaults[$k] ?? false) !== $v) {
                        $overrides[$k] = $v;
                    }
                }
            } else {
                $overrides = $newEntitlements;
            }

            $lockedLicense->update([
                'features' => $newEntitlements,
                'entitlement_overrides' => count($overrides) > 0 ? $overrides : null,
            ]);

            $after = [
                'features' => $newEntitlements,
                'entitlement_overrides' => count($overrides) > 0 ? $overrides : null,
            ];

            $this->record($lockedLicense, 'LICENSE_ENTITLEMENTS_CHANGED', [
                'before' => $before,
                'after' => $after,
                'reason' => $request->input('reason'),
                'actor_id' => $request->user()?->getKey(),
                'actor_name' => $request->user()?->name ?? 'Commercial Admin',
            ]);

            return successResponse($this->licenseData($lockedLicense->fresh(['commercialPlan', 'organization'])), 'Entitlements updated successfully.');
        });
    }

    public function updateLimits(UpdateLicenseLimitsRequest $request, License $license): JsonResponse
    {
        $this->authorizeLicenseBranch($request, $license);

        return DB::transaction(function () use ($request, $license): JsonResponse {
            $lockedLicense = License::query()->lockForUpdate()->findOrFail($license->getKey());
            $maxDevices = (int) $request->validated('max_devices');
            $maxBranches = (int) $request->validated('max_branches');

            $activeDevices = $lockedLicense->activations()->where('status', 'active')->count();
            $activeBranches = $lockedLicense->organization ? $lockedLicense->organization->branches()->where('is_active', true)->count() : 0;

            $isReduced = $maxDevices < $lockedLicense->max_devices || $maxBranches < $lockedLicense->max_branches;
            $isOverLimit = $activeDevices > $maxDevices || $activeBranches > $maxBranches;

            if (($isReduced || $isOverLimit) && empty(trim((string) $request->input('reason')))) {
                throw ValidationException::withMessages([
                    'reason' => ['A reason is required when reducing limits or creating an over-limit condition.'],
                ]);
            }

            $before = [
                'max_devices' => $lockedLicense->max_devices,
                'max_branches' => $lockedLicense->max_branches,
                'limit_overrides' => $lockedLicense->limit_overrides,
            ];

            $lockedLicense->update([
                'max_devices' => $maxDevices,
                'max_branches' => $maxBranches,
                'limit_overrides' => [
                    'max_devices' => $maxDevices,
                    'max_branches' => $maxBranches,
                ],
            ]);

            $after = [
                'max_devices' => $maxDevices,
                'max_branches' => $maxBranches,
                'limit_overrides' => $lockedLicense->limit_overrides,
            ];

            $this->record($lockedLicense, 'LICENSE_LIMITS_CHANGED', [
                'before' => $before,
                'after' => $after,
                'reason' => $request->input('reason'),
                'actor_id' => $request->user()?->getKey(),
                'actor_name' => $request->user()?->name ?? 'Commercial Admin',
            ]);

            return successResponse($this->licenseData($lockedLicense->fresh(['commercialPlan', 'organization'])), 'Limits updated successfully.');
        });
    }

    public function updateStatus(UpdateLicenseStatusRequest $request, License $license): JsonResponse
    {
        $this->authorizeLicenseBranch($request, $license);

        return DB::transaction(function () use ($request, $license): JsonResponse {
            $lockedLicense = License::query()->lockForUpdate()->findOrFail($license->getKey());
            $newStatus = (string) $request->validated('status');

            $beforeStatus = $lockedLicense->status;
            $lockedLicense->update(['status' => $newStatus]);

            $this->record($lockedLicense, 'LICENSE_STATUS_CHANGED', [
                'before' => ['status' => $beforeStatus],
                'after' => ['status' => $newStatus],
                'reason' => $request->input('reason'),
                'actor_id' => $request->user()?->getKey(),
                'actor_name' => $request->user()?->name ?? 'Commercial Admin',
            ]);

            return successResponse($this->licenseData($lockedLicense->fresh(['commercialPlan', 'organization'])), 'License status updated successfully.');
        });
    }

    public function renew(RenewLicenseRequest $request, License $license): JsonResponse
    {
        $this->authorizeLicenseBranch($request, $license);

        return DB::transaction(function () use ($request, $license): JsonResponse {
            $lockedLicense = License::query()->lockForUpdate()->findOrFail($license->getKey());
            $newExpiresAt = Carbon::parse($request->validated('expires_at'));
            $graceDays = $request->has('grace_period_days') ? (int) $request->validated('grace_period_days') : $lockedLicense->grace_period_days;

            $before = [
                'expires_at' => $lockedLicense->expires_at?->toISOString(),
                'grace_period_days' => $lockedLicense->grace_period_days,
            ];

            $lockedLicense->update([
                'expires_at' => $newExpiresAt,
                'grace_period_days' => $graceDays,
                'status' => $lockedLicense->status === 'expired' ? 'active' : $lockedLicense->status,
            ]);

            $after = [
                'expires_at' => $newExpiresAt->toISOString(),
                'grace_period_days' => $graceDays,
                'status' => $lockedLicense->status,
            ];

            $this->record($lockedLicense, 'LICENSE_RENEWED', [
                'before' => $before,
                'after' => $after,
                'reason' => $request->input('reason'),
                'actor_id' => $request->user()?->getKey(),
                'actor_name' => $request->user()?->name ?? 'Commercial Admin',
            ]);

            return successResponse($this->licenseData($lockedLicense->fresh(['commercialPlan', 'organization'])), 'License renewed successfully.');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function licenseData(License $license): array
    {
        $license->loadMissing(['commercialPlan', 'organization.branches']);
        $planModel = $license->planModel();
        $activeDevices = $license->activations()->where('status', 'active')->count();
        $activeBranches = $license->organization ? $license->organization->branches()->where('is_active', true)->count() : 0;

        return [
            'id' => $license->getKey(),
            'organization_id' => $license->organization_id,
            'plan_id' => $license->plan_id,
            'plan' => $license->plan_code ?: $license->plan,
            'plan_code' => $license->plan_code ?: $license->plan,
            'plan_version' => $license->plan_version ?? $planModel?->version ?? 1,
            'plan_name' => $planModel?->name,
            'status' => $license->status,
            'max_devices' => $license->max_devices,
            'max_branches' => $license->max_branches,
            'active_devices_count' => $activeDevices,
            'active_branches_count' => $activeBranches,
            'is_over_limit_devices' => $activeDevices > $license->max_devices,
            'is_over_limit_branches' => $activeBranches > $license->max_branches,
            'features' => $this->entitlementService->normalize($license->features),
            'entitlement_overrides' => $license->entitlement_overrides,
            'limit_overrides' => $license->limit_overrides,
            'starts_at' => $license->starts_at?->toISOString(),
            'expires_at' => $license->expires_at?->toISOString(),
            'grace_period_days' => $license->grace_period_days,
            'key_last_four' => $license->key_last_four,
        ];
    }

    private function resolveTargetPlan(Request $request): Plan
    {
        if ($request->filled('plan_id')) {
            return Plan::query()->findOrFail($request->input('plan_id'));
        }

        if ($request->filled('plan_code')) {
            $plan = $this->planService->findByCode($request->input('plan_code'));
            if (! $plan) {
                throw ValidationException::withMessages(['plan_code' => ['Specified plan code not found.']]);
            }

            return $plan;
        }

        throw ValidationException::withMessages(['plan_id' => ['Target plan is required.']]);
    }

    private function authorizeLicenseBranch(Request $request, License $license): void
    {
        $branch = $request->attributes->get('pos_branch');
        abort_unless($branch && $branch->organization_id === $license->organization_id, 403, __('api.license_organization_mismatch'));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(License $license, string $event, array $metadata): void
    {
        CommercialLicenseEvent::create([
            'license_id' => $license->getKey(),
            'event' => $event,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
