<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Commercial\CreateCommercialOrganizationRequest;
use App\Http\Requests\Commercial\IssueCommercialLicenseRequest;
use App\Http\Resources\Commercial\CommercialOrganizationDetailResource;
use App\Http\Resources\Commercial\CommercialOrganizationSummaryResource;
use App\Models\CommercialLicenseEvent;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Commercial\LicenseService;
use App\Services\Commercial\PlanService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class CommercialCustomerController extends BaseController
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly PlanService $planService,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Organization::query()
            ->with([
                'licenses.commercialPlan',
                'licenses.activations',
                'branches.devices',
            ]);

        // Search query
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('name->ar', 'like', "%{$search}%")
                    ->orWhere('name->en', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('settings->contact_phone', 'like', "%{$search}%")
                    ->orWhere('settings->contact_email', 'like', "%{$search}%")
                    ->orWhere('settings->contact_name', 'like', "%{$search}%")
                    ->orWhereHas('licenses', function ($licQ) use ($search): void {
                        $licQ->where('key_last_four', 'like', "%{$search}%")
                            ->orWhere('plan', 'like', "%{$search}%")
                            ->orWhere('plan_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('branches.devices', function ($devQ) use ($search): void {
                        $devQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by organization status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by onboarding status
        if ($request->filled('onboarding_status')) {
            $query->where('onboarding_status', $request->input('onboarding_status'));
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $plan = $request->input('plan');
            $query->whereHas('licenses', function ($licQ) use ($plan): void {
                $licQ->where('plan_code', $plan)->orWhere('plan', $plan)->orWhere('plan_id', $plan);
            });
        }

        // Filter by license status
        if ($request->filled('license_status')) {
            $licenseStatus = $request->input('license_status');
            $query->whereHas('licenses', function ($licQ) use ($licenseStatus): void {
                $licQ->where('status', $licenseStatus);
            });
        }

        // Filter by expiry window
        if ($request->filled('expiry')) {
            $expiry = $request->input('expiry');
            $now = CarbonImmutable::now();
            $query->whereHas('licenses', function ($licQ) use ($expiry, $now): void {
                if ($expiry === '7days') {
                    $licQ->where('expires_at', '>=', $now)->where('expires_at', '<=', $now->addDays(7));
                } elseif ($expiry === '30days') {
                    $licQ->where('expires_at', '>=', $now)->where('expires_at', '<=', $now->addDays(30));
                } elseif ($expiry === '60days') {
                    $licQ->where('expires_at', '>=', $now)->where('expires_at', '<=', $now->addDays(60));
                } elseif ($expiry === 'expired') {
                    $licQ->where('expires_at', '<', $now);
                }
            });
        }

        $perPage = min(100, max(5, (int) $request->input('per_page', 15)));
        $organizations = $query->latest('created_at')->paginate($perPage);

        return successResponse([
            'data' => CommercialOrganizationSummaryResource::collection($organizations->items()),
            'current_page' => $organizations->currentPage(),
            'last_page' => $organizations->lastPage(),
            'per_page' => $organizations->perPage(),
            'total' => $organizations->total(),
        ], 'Customers retrieved successfully.');
    }

    public function show(Organization $organization): JsonResponse
    {
        $organization->loadMissing([
            'licenses.commercialPlan',
            'licenses.activations.device.branch',
            'branches.devices',
        ]);

        return successResponse(
            new CommercialOrganizationDetailResource($organization),
            'Customer details retrieved successfully.'
        );
    }

    public function store(CreateCommercialOrganizationRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request): JsonResponse {
            $data = $request->validated();

            $slug = $data['slug'] ?? null;
            if (blank($slug)) {
                $slug = Str::slug($data['name']['en'] ?? $data['name']['ar']) ?: 'org-'.Str::lower(Str::random(8));
                if (Organization::query()->where('slug', $slug)->exists()) {
                    $slug .= '-'.Str::lower(Str::random(4));
                }
            }

            $settings = [
                'contact_name' => $data['contact_name'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            $organization = Organization::create([
                'id' => (string) Str::uuid(),
                'name' => $data['name'],
                'slug' => $slug,
                'currency' => $data['currency'] ?? 'EGP',
                'timezone' => $data['timezone'] ?? 'Africa/Cairo',
                'settings' => $settings,
                'onboarding_status' => 'NOT_STARTED',
                'onboarding_step' => null,
                'onboarding_completed_steps' => [],
                'onboarding_data' => [],
                'onboarding_version' => 1,
                'onboarding_completed_at' => null,
                'is_active' => true,
            ]);

            // Initial operator/manager account creation if provided
            $initialUser = null;
            $tempPassword = null;
            if (! empty($data['initial_admin_email'])) {
                $tempPassword = $data['initial_admin_password'] ?: 'Pilot!'.random_int(100000, 999999);
                $initialUser = User::create([
                    'name' => $data['initial_admin_name'] ?: ($data['name']['ar'] ?? 'Restaurant Owner'),
                    'email' => $data['initial_admin_email'],
                    'password' => $tempPassword,
                    'is_active' => true,
                ]);

                // Assign owner/manager permissions
                $guard = config('roles.default_guard', 'api');
                $ownerRole = Role::query()->firstOrCreate(['name' => 'owner', 'guard_name' => $guard], [
                    'display_name' => ['ar' => 'المالك', 'en' => 'Owner'],
                ]);
                $initialUser->assignRole($ownerRole);

                // Ensure pos configuration permission
                $posPerm = Permission::query()->firstOrCreate(
                    ['name' => 'settings-pos', 'guard_name' => $guard],
                    ['display_name' => ['ar' => 'إعداد نقطة البيع', 'en' => 'Configure POS'], 'group' => 'pos'],
                );
                $initialUser->givePermissionTo($posPerm);
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                $settings['initial_admin_user_id'] = $initialUser->getKey();
                $organization->update(['settings' => $settings]);
            }

            return successResponse([
                'organization' => new CommercialOrganizationSummaryResource($organization),
                'initial_user' => $initialUser ? [
                    'id' => $initialUser->getKey(),
                    'name' => $initialUser->name,
                    'email' => $initialUser->email,
                    'temporary_password' => $tempPassword,
                ] : null,
            ], 'Customer organization created successfully.', 201);
        });
    }

    public function issueLicense(IssueCommercialLicenseRequest $request, Organization $organization): JsonResponse
    {
        return DB::transaction(function () use ($request, $organization): JsonResponse {
            $lockedOrg = Organization::query()->lockForUpdate()->findOrFail($organization->getKey());

            // Check if organization already has an active license
            $hasActiveLicense = $lockedOrg->licenses()->where('status', 'active')->exists();
            if ($hasActiveLicense) {
                throw ValidationException::withMessages([
                    'license' => ['This customer already has an active license. Use Renew or Change Plan instead.'],
                ]);
            }

            $attributes = $request->validated();
            $issued = $this->licenseService->issue($lockedOrg, $attributes);
            $license = $issued['license'];

            CommercialLicenseEvent::create([
                'license_id' => $license->getKey(),
                'event' => 'LICENSE_ISSUED',
                'occurred_at' => now(),
                'metadata' => [
                    'plan_code' => $license->plan_code ?: $license->plan,
                    'max_devices' => $license->max_devices,
                    'max_branches' => $license->max_branches,
                    'expires_at' => $license->expires_at?->toIso8601String(),
                    'issued_by' => auth()->id(),
                    'notes' => $attributes['notes'] ?? null,
                ],
            ]);

            return successResponse([
                'license' => [
                    'id' => $license->getKey(),
                    'organization_id' => $license->organization_id,
                    'plan_id' => $license->plan_id,
                    'plan_code' => $license->plan_code ?: $license->plan,
                    'plan_version' => $license->plan_version,
                    'status' => $license->status,
                    'max_devices' => $license->max_devices,
                    'max_branches' => $license->max_branches,
                    'starts_at' => $license->starts_at?->toISOString(),
                    'expires_at' => $license->expires_at?->toISOString(),
                    'grace_period_days' => $license->grace_period_days,
                    'key_last_four' => $license->key_last_four,
                    'features' => $license->features,
                    'entitlement_overrides' => $license->entitlement_overrides,
                    'limit_overrides' => $license->limit_overrides,
                ],
                'license_key' => $issued['license_key'],
            ], 'Commercial license issued successfully.', 201);
        });
    }

    public function auditEvents(Request $request, Organization $organization): JsonResponse
    {
        $licenseIds = $organization->licenses()->pluck('id');

        $events = CommercialLicenseEvent::query()
            ->whereIn('license_id', $licenseIds)
            ->latest('occurred_at')
            ->paginate(min(50, max(5, (int) $request->input('per_page', 15))));

        return successResponse($events, 'Commercial audit events retrieved successfully.');
    }
}
