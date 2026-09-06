<?php

namespace Tests\Feature\Commercial;

use App\Enum\Commercial\DeploymentModeEnum;
use App\Models\Deployment;
use App\Models\License;
use App\Models\Plan;
use App\Services\Commercial\FleetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_overview_aggregates_across_deployments()
    {
        $org1 = $this->createOrganization();
        $org2 = $this->createOrganization();

        $plan = Plan::create([
            'code' => 'PRO',
            'name' => ['en' => 'Pro'],
            'status' => 'active',
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
            'default_entitlements' => ['pos' => true],
        ]);

        License::create(['organization_id' => $org1->id, 'plan_id' => $plan->id, 'key_hash' => hash('sha256', 'TEST1'), 'key_fingerprint' => 'FINGERPRINT1', 'key_last_four' => 'EST1', 'status' => 'active', 'expires_at' => now()->addYear()]);
        License::create(['organization_id' => $org2->id, 'plan_id' => $plan->id, 'key_hash' => hash('sha256', 'TEST2'), 'key_fingerprint' => 'FINGERPRINT2', 'key_last_four' => 'EST2', 'status' => 'suspended', 'expires_at' => now()->addYear()]);

        Deployment::create([
            'organization_id' => $org1->id,
            'mode' => DeploymentModeEnum::Local,
            'current_version' => '1.0.0',
            'health_status' => 'HEALTHY',
        ]);
        Deployment::create([
            'organization_id' => $org1->id,
            'mode' => DeploymentModeEnum::Local,
            'current_version' => '1.1.0',
            'health_status' => 'DEGRADED',
        ]);
        Deployment::create([
            'organization_id' => $org2->id,
            'mode' => DeploymentModeEnum::Cloud,
            'current_version' => '1.1.0',
            'health_status' => 'HEALTHY',
        ]);

        $service = new FleetService;
        $overview = $service->overview();

        $this->assertEquals(2, $overview['total_customers']);
        $this->assertEquals(3, $overview['total_deployments']);
        $this->assertEquals(1, $overview['total_active_licenses']);

        // Check version distribution (1 x 1.0.0, 2 x 1.1.0)
        $versions = collect($overview['version_distribution']);
        $this->assertEquals(1, $versions->where('version', '1.0.0')->first()['count']);
        $this->assertEquals(2, $versions->where('version', '1.1.0')->first()['count']);

        // Check health distribution (2 x HEALTHY, 1 x DEGRADED)
        $healths = collect($overview['health_distribution']);
        $this->assertEquals(2, $healths->where('status', 'HEALTHY')->first()['count']);
        $this->assertEquals(1, $healths->where('status', 'DEGRADED')->first()['count']);
    }
}
