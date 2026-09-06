<?php

namespace Tests\Feature\Commercial;

use App\Models\Branch;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\License;
use App\Models\Plan;
use App\Services\Commercial\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommercialAuthorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_pos_alone_cannot_authorize_installation_replacement(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create(['organization_id' => $org->getKey(), 'name' => ['ar' => 'فرع'], 'code' => 'B1', 'is_active' => true]);
        $device = Device::create(['branch_id' => $branch->getKey(), 'name' => 'POS 1', 'type' => 'pos', 'is_active' => true]);
        $licenseData = app(LicenseService::class)->issue($org);
        /** @var License $license */
        $license = $licenseData['license'];

        $activation = DeviceActivation::create([
            'license_id' => $license->getKey(),
            'device_id' => $device->getKey(),
            'installation_id' => (string) Str::uuid(),
            'activation_token_hash' => 'hash',
            'status' => 'active',
            'activated_at' => now(),
            'last_checkin_at' => now(),
            'offline_grace_expires_at' => now()->addDays(14),
        ]);

        // User with ONLY settings-pos tries to authorize replacement
        $this->createBranchUser($branch, ['settings-pos']);

        $response = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/activations/{$activation->getKey()}/authorize-replacement", [
                'reason' => 'Hardware failure',
            ]);

        $response->assertForbidden();
    }

    public function test_authorize_license_recovery_permission_can_authorize_replacement(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create(['organization_id' => $org->getKey(), 'name' => ['ar' => 'فرع'], 'code' => 'B1', 'is_active' => true]);
        $device = Device::create(['branch_id' => $branch->getKey(), 'name' => 'POS 1', 'type' => 'pos', 'is_active' => true]);
        $licenseData = app(LicenseService::class)->issue($org);
        /** @var License $license */
        $license = $licenseData['license'];

        $activation = DeviceActivation::create([
            'license_id' => $license->getKey(),
            'device_id' => $device->getKey(),
            'installation_id' => (string) Str::uuid(),
            'activation_token_hash' => 'hash',
            'status' => 'active',
            'activated_at' => now(),
            'last_checkin_at' => now(),
            'offline_grace_expires_at' => now()->addDays(14),
        ]);

        $this->createBranchUser($branch, ['authorize-license-recovery']);

        $response = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/activations/{$activation->getKey()}/authorize-replacement", [
                'reason' => 'Motherboard died',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['recovery_token', 'expires_at']]);
    }

    public function test_settings_pos_alone_cannot_change_commercial_plan_or_limits(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create(['organization_id' => $org->getKey(), 'name' => ['ar' => 'فرع'], 'code' => 'B1', 'is_active' => true]);
        $licenseData = app(LicenseService::class)->issue($org);
        /** @var License $license */
        $license = $licenseData['license'];

        $this->createBranchUser($branch, ['settings-pos']);

        $response = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/licenses/{$license->getKey()}/change-plan", [
                'plan_code' => 'enterprise',
            ]);

        $response->assertForbidden();

        $limitsResponse = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->putJson("/api/v1/commercial/licenses/{$license->getKey()}/limits", [
                'max_devices' => 10,
                'max_branches' => 5,
            ]);

        $limitsResponse->assertForbidden();
    }

    public function test_commercial_admin_can_preview_and_change_plan(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create(['organization_id' => $org->getKey(), 'name' => ['ar' => 'فرع'], 'code' => 'B1', 'is_active' => true]);
        $starterPlan = Plan::query()->where('code', 'starter')->first();
        $enterprisePlan = Plan::query()->where('code', 'enterprise')->first();

        $licenseData = app(LicenseService::class)->issue($org, ['plan_id' => $starterPlan->getKey()]);
        /** @var License $license */
        $license = $licenseData['license'];

        $this->createBranchUser($branch, ['manage-commercial-license']);

        // Preview plan change
        $previewRes = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/licenses/{$license->getKey()}/preview-plan", [
                'plan_code' => 'enterprise',
            ]);

        $previewRes->assertOk()
            ->assertJsonPath('data.target.plan_code', 'enterprise')
            ->assertJsonPath('data.target.max_devices', 10)
            ->assertJsonPath('data.target.max_branches', 5);

        // Apply plan change
        $changeRes = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/licenses/{$license->getKey()}/change-plan", [
                'plan_code' => 'enterprise',
            ]);

        $changeRes->assertOk()
            ->assertJsonPath('data.license.plan_code', 'enterprise')
            ->assertJsonPath('data.license.max_devices', 10)
            ->assertJsonPath('data.license.max_branches', 5);

        $license->refresh();
        $this->assertSame('enterprise', $license->plan_code);
        $this->assertSame(10, $license->max_devices);
        $this->assertSame(5, $license->max_branches);
        $this->assertTrue($license->features['inventory']);
    }

    public function test_cross_tenant_commercial_license_management_is_rejected(): void
    {
        $orgA = $this->createOrganization();
        $branchA = Branch::create(['organization_id' => $orgA->getKey(), 'name' => ['ar' => 'فرع أ'], 'code' => 'BA', 'is_active' => true]);

        $orgB = $this->createOrganization();
        $licenseB = app(LicenseService::class)->issue($orgB)['license'];

        $this->createBranchUser($branchA, ['manage-commercial-license']);

        $response = $this->withHeaders(['X-Branch-ID' => $branchA->getKey()])
            ->postJson("/api/v1/commercial/licenses/{$licenseB->getKey()}/change-plan", [
                'plan_code' => 'enterprise',
            ]);

        $response->assertForbidden();
    }
}
