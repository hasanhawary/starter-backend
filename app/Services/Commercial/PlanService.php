<?php

namespace App\Services\Commercial;

use App\Models\CommercialLicenseEvent;
use App\Models\License;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlanService
{
    public function __construct(
        private readonly EntitlementService $entitlementService,
    ) {}

    /**
     * @param  array{status?: string}  $filters
     * @return Collection<int, Plan>
     */
    public function list(array $filters = []): Collection
    {
        return Plan::query()
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderBy('code')
            ->get();
    }

    public function findByCode(string $code): ?Plan
    {
        return Plan::query()->where('code', $code)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Plan
    {
        $code = Str::lower(trim((string) ($data['code'] ?? '')));
        if (Plan::query()->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => ['Plan code is already taken.']]);
        }

        $entitlements = $this->entitlementService->normalize($data['default_entitlements'] ?? []);
        $limits = [
            'max_devices' => max(1, (int) ($data['default_limits']['max_devices'] ?? 1)),
            'max_branches' => max(1, (int) ($data['default_limits']['max_branches'] ?? 1)),
        ];

        $plan = Plan::create([
            'code' => $code,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? Plan::STATUS_ACTIVE,
            'version' => 1,
            'default_entitlements' => $entitlements,
            'default_limits' => $limits,
            'metadata' => $data['metadata'] ?? [],
        ]);

        $this->recordPlanEvent($plan, 'PLAN_CREATED', [
            'code' => $plan->code,
            'name' => $plan->name,
            'limits' => $limits,
            'created_by' => $actor?->name ?? 'System',
        ]);

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Plan $plan, array $data, ?User $actor = null): Plan
    {
        $entitlementsChanged = array_key_exists('default_entitlements', $data);
        $limitsChanged = array_key_exists('default_limits', $data);

        $newEntitlements = $entitlementsChanged
            ? $this->entitlementService->normalize($data['default_entitlements'])
            : $plan->default_entitlements;

        $newLimits = $limitsChanged ? [
            'max_devices' => max(1, (int) ($data['default_limits']['max_devices'] ?? $plan->defaultLimits()['max_devices'])),
            'max_branches' => max(1, (int) ($data['default_limits']['max_branches'] ?? $plan->defaultLimits()['max_branches'])),
        ] : $plan->default_limits;

        $version = $plan->version;
        if ($entitlementsChanged || $limitsChanged) {
            $version++;
        }

        $before = [
            'name' => $plan->name,
            'description' => $plan->description,
            'version' => $plan->version,
            'default_entitlements' => $plan->default_entitlements,
            'default_limits' => $plan->default_limits,
        ];

        $plan->update([
            'name' => $data['name'] ?? $plan->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $plan->description,
            'version' => $version,
            'default_entitlements' => $newEntitlements,
            'default_limits' => $newLimits,
            'metadata' => array_key_exists('metadata', $data) ? $data['metadata'] : $plan->metadata,
        ]);

        $this->recordPlanEvent($plan, 'PLAN_UPDATED', [
            'before' => $before,
            'after' => [
                'name' => $plan->name,
                'version' => $plan->version,
                'default_entitlements' => $plan->default_entitlements,
                'default_limits' => $plan->default_limits,
            ],
            'updated_by' => $actor?->name ?? 'System',
        ]);

        return $plan->fresh();
    }

    public function archive(Plan $plan, ?string $reason = null, ?User $actor = null): Plan
    {
        if ($plan->isArchived()) {
            return $plan;
        }

        $plan->update(['status' => Plan::STATUS_ARCHIVED]);

        $this->recordPlanEvent($plan, 'PLAN_ARCHIVED', [
            'reason' => $reason,
            'archived_by' => $actor?->name ?? 'System',
        ]);

        return $plan;
    }

    public function restore(Plan $plan, ?User $actor = null): Plan
    {
        if (! $plan->isArchived()) {
            return $plan;
        }

        $plan->update(['status' => Plan::STATUS_ACTIVE]);

        $this->recordPlanEvent($plan, 'PLAN_RESTORED', [
            'restored_by' => $actor?->name ?? 'System',
        ]);

        return $plan;
    }

    public function delete(Plan $plan, ?User $actor = null): void
    {
        if ($plan->licenses()->exists()) {
            throw ValidationException::withMessages([
                'plan' => ['Cannot delete a commercial plan referenced by existing licenses. Please archive it instead.'],
            ]);
        }

        $this->recordPlanEvent($plan, 'PLAN_DELETED', [
            'deleted_by' => $actor?->name ?? 'System',
        ]);

        $plan->delete();
    }

    /**
     * Resolve authoritative effective configuration for a Plan + Overrides combination.
     *
     * @param  array<string, bool>|null  $entitlementOverrides
     * @param  array{max_devices?: int, max_branches?: int}|null  $limitOverrides
     * @return array{plan: array<string, mixed>, features: array<string, bool>, max_devices: int, max_branches: int, entitlement_overrides: array<string, bool>|null, limit_overrides: array<string, int>|null}
     */
    public function resolveEffectiveConfiguration(
        Plan $plan,
        ?array $entitlementOverrides = null,
        ?array $limitOverrides = null,
    ): array {
        $defaults = $plan->defaultEntitlements();
        $normalizedOverrides = null;

        if (is_array($entitlementOverrides) && count($entitlementOverrides) > 0) {
            $normalizedOverrides = [];
            foreach ($entitlementOverrides as $k => $v) {
                if ($this->entitlementService->isValidKey((string) $k)) {
                    $normalizedOverrides[(string) $k] = (bool) $v;
                }
            }
        }

        $effectiveFeatures = array_replace(
            $this->entitlementService->normalize($defaults),
            $normalizedOverrides ?? [],
        );

        $defaultLimits = $plan->defaultLimits();
        $maxDevices = max(1, (int) ($limitOverrides['max_devices'] ?? $defaultLimits['max_devices']));
        $maxBranches = max(1, (int) ($limitOverrides['max_branches'] ?? $defaultLimits['max_branches']));

        return [
            'plan' => [
                'id' => $plan->getKey(),
                'code' => $plan->code,
                'version' => $plan->version,
                'name' => $plan->name,
                'status' => $plan->status,
            ],
            'features' => $effectiveFeatures,
            'max_devices' => $maxDevices,
            'max_branches' => $maxBranches,
            'entitlement_overrides' => $normalizedOverrides,
            'limit_overrides' => $limitOverrides ? [
                'max_devices' => $maxDevices,
                'max_branches' => $maxBranches,
            ] : null,
        ];
    }

    /**
     * Preview impact of changing a license's plan or overrides without persisting.
     *
     * @param  array<string, bool>|null  $entitlementOverrides
     * @param  array{max_devices?: int, max_branches?: int}|null  $limitOverrides
     * @return array<string, mixed>
     */
    public function previewPlanChange(
        License $license,
        Plan $targetPlan,
        ?array $entitlementOverrides = null,
        ?array $limitOverrides = null,
    ): array {
        if ($targetPlan->isArchived()) {
            throw ValidationException::withMessages([
                'plan_code' => ['Cannot select an archived plan. Please choose an active plan or restore this plan first.'],
            ]);
        }

        $license->loadMissing('organization.branches');
        $resolved = $this->resolveEffectiveConfiguration($targetPlan, $entitlementOverrides, $limitOverrides);

        $currentFeatures = $this->entitlementService->normalize($license->features);
        $newFeatures = $resolved['features'];

        $addedFeatures = [];
        $removedFeatures = [];
        foreach ($this->entitlementService->catalog() as $key => $meta) {
            $cur = $currentFeatures[$key] ?? false;
            $new = $newFeatures[$key] ?? false;
            if (! $cur && $new) {
                $addedFeatures[] = ['key' => $key, 'label' => $meta['label'], 'label_ar' => $meta['label_ar']];
            } elseif ($cur && ! $new) {
                $removedFeatures[] = ['key' => $key, 'label' => $meta['label'], 'label_ar' => $meta['label_ar']];
            }
        }

        $activeDevicesCount = $license->activations()->where('status', 'active')->count();
        $activeBranchesCount = $license->organization ? $license->organization->branches()->where('is_active', true)->count() : 0;

        $isOverLimitDevices = $activeDevicesCount > $resolved['max_devices'];
        $isOverLimitBranches = $activeBranchesCount > $resolved['max_branches'];
        $isLimitReduced = $resolved['max_devices'] < $license->max_devices || $resolved['max_branches'] < $license->max_branches;
        $isDowngrade = count($removedFeatures) > 0 || $isLimitReduced;

        $warnings = [];
        if ($isOverLimitDevices) {
            $warnings[] = [
                'type' => 'devices_over_limit',
                'message' => "Current active devices ({$activeDevicesCount}) exceed new limit ({$resolved['max_devices']}). Existing terminals will not be deleted, but adding or activating new devices is blocked.",
                'message_ar' => "عدد الأجهزة النشطة حالياً ({$activeDevicesCount}) يتجاوز الحد الجديد ({$resolved['max_devices']}). لن يتم حذف الأجهزة، ولكن سيتم منع إضافة أو تفعيل أجهزة جديدة.",
            ];
        }

        if ($isOverLimitBranches) {
            $warnings[] = [
                'type' => 'branches_over_limit',
                'message' => "Current active branches ({$activeBranchesCount}) exceed new limit ({$resolved['max_branches']}). Existing branch data is preserved, but creating or activating new branches is blocked.",
                'message_ar' => "عدد الفروع النشطة حالياً ({$activeBranchesCount}) يتجاوز الحد الجديد ({$resolved['max_branches']}). بيانات الفروع محفوظة، ولكن سيتم منع إنشاء أو تفعيل فروع جديدة.",
            ];
        }

        if (count($removedFeatures) > 0) {
            $warnings[] = [
                'type' => 'features_removed',
                'message' => 'Disabled modules will become inaccessible at next lease refresh. All historical database records remain strictly preserved.',
                'message_ar' => 'المزايا الملغاة ستصبح غير متاحة عند تحديث الترخيص القادم. كافة البيانات التاريخية وسجلات الحركات ستبقى محفوظة بالكامل.',
            ];
        }

        return [
            'license_id' => $license->getKey(),
            'current' => [
                'plan_code' => $license->plan_code ?: $license->plan,
                'max_devices' => $license->max_devices,
                'max_branches' => $license->max_branches,
                'active_devices' => $activeDevicesCount,
                'active_branches' => $activeBranchesCount,
                'features' => $currentFeatures,
            ],
            'target' => [
                'plan_id' => $targetPlan->getKey(),
                'plan_code' => $targetPlan->code,
                'plan_name' => $targetPlan->name,
                'plan_version' => $targetPlan->version,
                'max_devices' => $resolved['max_devices'],
                'max_branches' => $resolved['max_branches'],
                'features' => $newFeatures,
                'entitlement_overrides' => $resolved['entitlement_overrides'],
                'limit_overrides' => $resolved['limit_overrides'],
            ],
            'diff' => [
                'added_features' => $addedFeatures,
                'removed_features' => $removedFeatures,
                'is_downgrade' => $isDowngrade,
                'is_limit_reduced' => $isLimitReduced,
                'is_over_limit_devices' => $isOverLimitDevices,
                'is_over_limit_branches' => $isOverLimitBranches,
                'requires_reason' => $isDowngrade || $isOverLimitDevices || $isOverLimitBranches,
                'warnings' => $warnings,
            ],
        ];
    }

    /**
     * Atomically execute a Plan change or override modification with concurrency locking and audit logging.
     *
     * @param  array{entitlement_overrides?: array<string, bool>|null, limit_overrides?: array{max_devices?: int, max_branches?: int}|null, reason?: string|null}  $data
     * @return array<string, mixed>
     */
    public function changePlan(License $license, Plan $targetPlan, array $data = [], ?User $actor = null): array
    {
        return DB::transaction(function () use ($license, $targetPlan, $data, $actor): array {
            $lockedLicense = License::query()->lockForUpdate()->findOrFail($license->getKey());

            $preview = $this->previewPlanChange(
                $lockedLicense,
                $targetPlan,
                $data['entitlement_overrides'] ?? null,
                $data['limit_overrides'] ?? null,
            );

            if ($preview['diff']['requires_reason'] && empty(trim((string) ($data['reason'] ?? '')))) {
                throw ValidationException::withMessages([
                    'reason' => ['A reason is required when downgrading, reducing limits, or creating an over-limit condition.'],
                ]);
            }

            $resolved = $this->resolveEffectiveConfiguration(
                $targetPlan,
                $data['entitlement_overrides'] ?? null,
                $data['limit_overrides'] ?? null,
            );

            $before = [
                'plan_id' => $lockedLicense->plan_id,
                'plan' => $lockedLicense->plan,
                'plan_code' => $lockedLicense->plan_code,
                'plan_version' => $lockedLicense->plan_version,
                'features' => $lockedLicense->features,
                'max_devices' => $lockedLicense->max_devices,
                'max_branches' => $lockedLicense->max_branches,
                'entitlement_overrides' => $lockedLicense->entitlement_overrides,
                'limit_overrides' => $lockedLicense->limit_overrides,
            ];

            $lockedLicense->update([
                'plan_id' => $targetPlan->getKey(),
                'plan' => $targetPlan->code,
                'plan_code' => $targetPlan->code,
                'plan_version' => $targetPlan->version,
                'features' => $resolved['features'],
                'max_devices' => $resolved['max_devices'],
                'max_branches' => $resolved['max_branches'],
                'entitlement_overrides' => $resolved['entitlement_overrides'],
                'limit_overrides' => $resolved['limit_overrides'],
            ]);

            $after = [
                'plan_id' => $targetPlan->getKey(),
                'plan' => $targetPlan->code,
                'plan_code' => $targetPlan->code,
                'plan_version' => $targetPlan->version,
                'features' => $resolved['features'],
                'max_devices' => $resolved['max_devices'],
                'max_branches' => $resolved['max_branches'],
                'entitlement_overrides' => $resolved['entitlement_overrides'],
                'limit_overrides' => $resolved['limit_overrides'],
            ];

            CommercialLicenseEvent::create([
                'license_id' => $lockedLicense->getKey(),
                'event' => 'LICENSE_PLAN_CHANGED',
                'metadata' => [
                    'before' => $before,
                    'after' => $after,
                    'reason' => $data['reason'] ?? null,
                    'actor_id' => $actor?->getKey(),
                    'actor_name' => $actor?->name ?? 'System',
                    'diff' => $preview['diff'],
                ],
                'occurred_at' => now(),
            ]);

            return [
                'license' => $lockedLicense->fresh(['commercialPlan', 'organization']),
                'preview' => $preview,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordPlanEvent(Plan $plan, string $event, array $metadata = []): void
    {
        CommercialLicenseEvent::create([
            'license_id' => null,
            'event' => $event,
            'metadata' => [
                'plan_id' => $plan->getKey(),
                'plan_code' => $plan->code,
                ...$metadata,
            ],
            'occurred_at' => now(),
        ]);
    }
}
