<?php

namespace Tests\Feature\Commercial;

use App\Models\Branch;
use App\Models\CommercialLicenseEvent;
use App\Models\Ingredient;
use App\Models\License;
use App\Models\Plan;
use App\Models\Unit;
use App\Services\Commercial\EntitlementService;
use App\Services\Commercial\LicenseService;
use App\Services\Commercial\PlanService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PlanHardeningAndAuditImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_license_event_records_are_append_only_and_immutable(): void
    {
        $org = $this->createOrganization();
        $license = License::create([
            'organization_id' => $org->getKey(),
            'key_hash' => 'hash1',
            'key_fingerprint' => 'fp1',
            'key_last_four' => '1111',
            'status' => 'active',
            'features' => ['pos' => true],
            'max_devices' => 2,
            'max_branches' => 1,
            'expires_at' => now()->addYear(),
        ]);

        $event = CommercialLicenseEvent::create([
            'license_id' => $license->getKey(),
            'event' => 'LICENSE_ISSUED',
            'metadata' => ['plan' => 'custom', 'devices' => 2],
            'occurred_at' => now(),
        ]);

        $this->assertDatabaseHas('commercial_license_events', [
            'id' => $event->getKey(),
            'event' => 'LICENSE_ISSUED',
        ]);

        // Attempting to update must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $event->update(['event' => 'FORGED_EVENT']);
    }

    public function test_commercial_license_event_cannot_be_deleted(): void
    {
        $org = $this->createOrganization();
        $license = License::create([
            'organization_id' => $org->getKey(),
            'key_hash' => 'hash2',
            'key_fingerprint' => 'fp2',
            'key_last_four' => '2222',
            'status' => 'active',
            'features' => ['pos' => true],
            'max_devices' => 2,
            'max_branches' => 1,
            'expires_at' => now()->addYear(),
        ]);

        $event = CommercialLicenseEvent::create([
            'license_id' => $license->getKey(),
            'event' => 'LICENSE_ISSUED',
            'metadata' => ['plan' => 'custom'],
            'occurred_at' => now(),
        ]);

        // Attempting to delete must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $event->delete();
    }

    public function test_plan_referenced_by_license_cannot_be_hard_deleted(): void
    {
        $plan = Plan::create([
            'code' => 'custom_tier_a',
            'name' => ['ar' => 'باقة أ', 'en' => 'Tier A'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true],
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
        ]);

        $org = $this->createOrganization();
        License::create([
            'organization_id' => $org->getKey(),
            'plan_id' => $plan->getKey(),
            'plan_code' => $plan->code,
            'plan_version' => $plan->version,
            'key_hash' => 'hash3',
            'key_fingerprint' => 'fp3',
            'key_last_four' => '3333',
            'status' => 'active',
            'features' => ['pos' => true],
            'max_devices' => 1,
            'max_branches' => 1,
            'expires_at' => now()->addYear(),
        ]);

        $this->expectException(DomainException::class);
        $plan->delete();
    }

    public function test_archived_plan_cannot_be_assigned_or_previewed(): void
    {
        $plan = Plan::create([
            'code' => 'archived_tier',
            'name' => ['ar' => 'باقة مؤرشفة', 'en' => 'Archived Tier'],
            'status' => 'archived',
            'version' => 1,
            'default_entitlements' => ['pos' => true],
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
        ]);

        $org = $this->createOrganization();
        $license = License::create([
            'organization_id' => $org->getKey(),
            'key_hash' => 'hash4',
            'key_fingerprint' => 'fp4',
            'key_last_four' => '4444',
            'status' => 'active',
            'features' => ['pos' => true],
            'max_devices' => 1,
            'max_branches' => 1,
            'expires_at' => now()->addYear(),
        ]);

        $branch = Branch::create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'code' => 'B01',
            'is_active' => true,
        ]);

        $user = $this->createBranchUser($branch, ['manage-commercial-license']);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Branch-Id', $branch->getKey())
            ->postJson("/api/v1/commercial/licenses/{$license->getKey()}/preview-plan", [
                'plan_code' => 'archived_tier',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_code']);
    }

    public function test_plan_snapshot_isolation_preserves_license_contract_across_plan_template_updates(): void
    {
        $planService = app(PlanService::class);

        $plan = Plan::create([
            'code' => 'test_plan_v1',
            'name' => ['ar' => 'باقة 1', 'en' => 'Plan 1'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true, 'tables' => true, 'inventory' => false],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
        ]);

        $org = $this->createOrganization();
        $licenseService = app(LicenseService::class);
        $issueResult = $licenseService->issue($org, [
            'plan_id' => $plan->getKey(),
        ]);

        /** @var License $license */
        $license = $issueResult['license'];
        $this->assertSame(1, $license->plan_version);
        $this->assertSame(2, $license->max_devices);
        $this->assertFalse($license->features['inventory'] ?? false);

        // Commercial admin edits the plan template to v2 (adds inventory, increases devices to 5)
        $planService->update($plan, [
            'default_entitlements' => ['pos' => true, 'tables' => true, 'inventory' => true],
            'default_limits' => ['max_devices' => 5, 'max_branches' => 2],
        ]);

        $plan->refresh();
        $this->assertSame(2, $plan->version);
        $this->assertSame(5, $plan->defaultLimits()['max_devices']);

        // License must maintain v1 contract snapshot completely untouched
        $license->refresh();
        $this->assertSame(1, $license->plan_version);
        $this->assertSame(2, $license->max_devices);
        $this->assertFalse($license->features['inventory'] ?? false);
    }

    public function test_license_specific_overrides_take_precedence_over_plan_defaults(): void
    {
        $plan = Plan::create([
            'code' => 'base_tier',
            'name' => ['ar' => 'باقة أساسية', 'en' => 'Base Tier'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true, 'inventory' => false],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
        ]);

        $planService = app(PlanService::class);
        $effective = $planService->resolveEffectiveConfiguration(
            $plan,
            ['inventory' => true], // Contract entitlement override
            ['max_devices' => 4, 'max_branches' => 3], // Contract limit overrides
        );

        $this->assertTrue($effective['features']['inventory']);
        $this->assertSame(4, $effective['max_devices']);
        $this->assertSame(3, $effective['max_branches']);
    }

    public function test_unknown_entitlement_fails_closed(): void
    {
        $entitlementService = app(EntitlementService::class);

        $normalized = $entitlementService->normalize([
            'pos' => true,
            'unknown_feature_hacked' => true,
            'non_existent_module' => true,
        ]);

        $this->assertTrue($normalized['pos']);
        $this->assertArrayNotHasKey('unknown_feature_hacked', $normalized);
        $this->assertArrayNotHasKey('non_existent_module', $normalized);

        $this->assertFalse($entitlementService->allows([
            'state' => 'ACTIVE',
            'claims' => ['entitlements' => ['unknown_feature_hacked' => true]],
        ], 'unknown_feature_hacked'));
    }

    public function test_downgrade_preserves_business_data_and_restores_cleanly(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'فرع المعادي', 'en' => 'Maadi Branch'],
            'code' => 'MAADI',
            'is_active' => true,
        ]);

        $license = License::create([
            'organization_id' => $org->getKey(),
            'key_hash' => 'hash5',
            'key_fingerprint' => 'fp5',
            'key_last_four' => '5555',
            'status' => 'active',
            'features' => ['pos' => true, 'inventory' => true],
            'max_devices' => 2,
            'max_branches' => 1,
            'expires_at' => now()->addYear(),
        ]);

        $unit = Unit::create([
            'organization_id' => $org->getKey(),
            'code' => 'g',
            'name' => ['ar' => 'جرام', 'en' => 'Gram'],
        ]);

        // Create business inventory data
        $ingredient = Ingredient::create([
            'organization_id' => $org->getKey(),
            'unit_id' => $unit->getKey(),
            'name' => ['ar' => 'قهوة برازيلي', 'en' => 'Brazilian Coffee'],
            'sku' => 'ING-COF',
            'cost_per_unit' => 50,
            'is_active' => true,
        ]);

        // Disable inventory via commercial override
        $license->update([
            'features' => ['pos' => true, 'inventory' => false],
            'entitlement_overrides' => ['inventory' => false],
        ]);

        // Verify ingredient still exists in database
        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->getKey(),
            'cost_per_unit' => 50,
        ]);

        // Re-enable inventory
        $license->update([
            'features' => ['pos' => true, 'inventory' => true],
            'entitlement_overrides' => ['inventory' => true],
        ]);

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->getKey(),
            'cost_per_unit' => 50,
        ]);
    }
}
