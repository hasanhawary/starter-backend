<?php

namespace Tests\Feature\Commercial;

use App\Enum\Commercial\ProvisioningStepEnum;
use App\Models\Deployment;
use App\Models\Plan;
use App\Services\Commercial\DeploymentService;
use App\Services\Commercial\LicenseService;
use App\Services\Commercial\PlanService;
use App\Services\Commercial\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProvisioningIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_flow_creates_logs()
    {
        $plan = Plan::create([
            'code' => 'PRO',
            'name' => ['en' => 'Pro'],
            'status' => 'active',
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
            'default_entitlements' => ['pos' => true],
        ]);

        // Mock to avoid real license logic dependency for this unit-level test
        $licenseService = Mockery::mock(LicenseService::class);
        $licenseService->shouldReceive('issue')->andReturn(['key' => 'TEST-KEY']);

        $service = new ProvisioningService(
            new DeploymentService,
            app(PlanService::class),
            $licenseService
        );

        $result = $service->provision([
            'name_ar' => 'Test Org',
            'deployment_mode' => 'local',
            'plan_id' => $plan->id,
            'max_devices' => 5,
            'max_branches' => 1,
            'expires_at' => now()->addYear()->toDateString(),
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
        ]);

        $this->assertNotNull($result['organization']);
        $this->assertNotNull($result['deployment']);
        $this->assertEquals('TEST-KEY', $result['license_key']);
        $this->assertNotNull($result['provisioning_token']);

        // Check logs
        $logs = $result['deployment']->provisioningLogs;

        // 4 steps that are logged to the deployment (Org creation and Deployment creation pass null for deployment so aren't logged)
        $this->assertCount(8, $logs); // 4 started + 4 completed

        $completedSteps = $logs->where('status', 'completed')->pluck('step');
        $this->assertContains(ProvisioningStepEnum::AssignPlan, $completedSteps);
        $this->assertContains(ProvisioningStepEnum::IssueLicense, $completedSteps);
        $this->assertContains(ProvisioningStepEnum::CreateInitialAdmin, $completedSteps);
        $this->assertContains(ProvisioningStepEnum::GenerateProvisioningToken, $completedSteps);
    }
}
