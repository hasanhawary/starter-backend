<?php

namespace Tests\Feature\Commercial;

use App\Models\License;
use App\Models\Plan;
use App\Services\Commercial\EntitlementService;
use App\Services\Commercial\LicenseService;
use App\Services\Commercial\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_commercial_plans_with_permission(): void
    {
        $this->actingAsUserWithPermissions(['manage-commercial-plans']);

        $response = $this->getJson('/api/v1/commercial/plans');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'status', 'version', 'default_entitlements', 'default_limits']]]);
    }

    public function test_user_without_permission_cannot_access_plans(): void
    {
        $this->actingAsUserWithPermissions(['settings-pos']);

        $response = $this->getJson('/api/v1/commercial/plans');

        $response->assertForbidden();
    }

    public function test_can_create_plan_with_canonical_entitlements_and_limits(): void
    {
        $this->actingAsUserWithPermissions(['manage-commercial-plans']);

        $payload = [
            'code' => 'custom-pro',
            'name' => ['ar' => 'باقة مخصصة برو', 'en' => 'Custom Pro Plan'],
            'description' => ['ar' => 'وصف تجريبي للباقة', 'en' => 'Test description'],
            'status' => 'active',
            'default_limits' => [
                'max_devices' => 5,
                'max_branches' => 2,
            ],
            'default_entitlements' => [
                'pos' => true,
                'kds' => true,
                'inventory' => true,
                'tables' => true,
            ],
        ];

        $response = $this->postJson('/api/v1/commercial/plans', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'custom-pro')
            ->assertJsonPath('data.default_limits.max_devices', 5)
            ->assertJsonPath('data.default_limits.max_branches', 2)
            ->assertJsonPath('data.default_entitlements.pos', true)
            ->assertJsonPath('data.default_entitlements.kds', true);

        $this->assertDatabaseHas('plans', ['code' => 'custom-pro', 'version' => 1]);
    }

    public function test_duplicate_plan_code_is_rejected(): void
    {
        $this->actingAsUserWithPermissions(['manage-commercial-plans']);

        $planService = app(PlanService::class);
        $planService->create([
            'code' => 'unique-plan',
            'name' => ['ar' => 'فريدة'],
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
        ]);

        $response = $this->postJson('/api/v1/commercial/plans', [
            'code' => 'unique-plan',
            'name' => ['ar' => 'تكرار'],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_updating_plan_increments_version_and_preserves_historical_licenses(): void
    {
        $this->actingAsUserWithPermissions(['manage-commercial-plans']);

        $planService = app(PlanService::class);
        $plan = $planService->create([
            'code' => 'evolving-plan',
            'name' => ['ar' => 'خطة متطورة'],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
            'default_entitlements' => ['pos' => true, 'kds' => false],
        ]);

        $org = $this->createOrganization();
        $licenseData = app(LicenseService::class)->issue($org, [
            'plan_id' => $plan->getKey(),
        ]);
        /** @var License $license */
        $license = $licenseData['license'];

        $this->assertSame(2, $license->max_devices);
        $this->assertSame(1, $license->plan_version);
        $this->assertFalse($license->features['kds']);

        // Update the plan template to v2 with max_devices = 4 and kds = true
        $updateResponse = $this->putJson("/api/v1/commercial/plans/{$plan->getKey()}", [
            'name' => ['ar' => 'خطة متطورة v2'],
            'default_limits' => ['max_devices' => 4, 'max_branches' => 1],
            'default_entitlements' => ['pos' => true, 'kds' => true],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.default_limits.max_devices', 4);

        // Historical license retains its initial snapshot until explicitly changed
        $license->refresh();
        $this->assertSame(2, $license->max_devices);
        $this->assertSame(1, $license->plan_version);
        $this->assertFalse($license->features['kds']);
    }

    public function test_can_archive_and_restore_plan(): void
    {
        $this->actingAsUserWithPermissions(['manage-commercial-plans']);

        $plan = Plan::create([
            'code' => 'archivable',
            'name' => ['ar' => 'خطة قابلة للأرشفة'],
            'status' => 'active',
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
            'default_entitlements' => ['pos' => true],
        ]);

        $archiveRes = $this->postJson("/api/v1/commercial/plans/{$plan->getKey()}/archive", [
            'reason' => 'Deprecated template',
        ]);
        $archiveRes->assertOk()->assertJsonPath('data.status', 'archived');
        $this->assertTrue($plan->fresh()->isArchived());

        $restoreRes = $this->postJson("/api/v1/commercial/plans/{$plan->getKey()}/restore");
        $restoreRes->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertFalse($plan->fresh()->isArchived());
    }

    public function test_unknown_entitlement_key_fails_closed(): void
    {
        $entitlementService = app(EntitlementService::class);

        $normalized = $entitlementService->normalize([
            'pos' => true,
            'fake_hacked_module' => true,
            'kds' => false,
        ]);

        $this->assertTrue($normalized['pos']);
        $this->assertFalse($normalized['kds']);
        $this->assertArrayNotHasKey('fake_hacked_module', $normalized);

        $this->assertFalse($entitlementService->allows([
            'state' => 'ACTIVE',
            'claims' => ['entitlements' => ['fake_hacked_module' => true]],
        ], 'fake_hacked_module'));
    }
}
