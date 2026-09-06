<?php

namespace Tests\Feature\Commercial;

use App\Enum\Commercial\DeploymentModeEnum;
use App\Models\Deployment;
use App\Models\ProvisioningToken;
use App\Services\Commercial\DeploymentService;
use App\Services\Commercial\LicenseService;
use App\Services\Commercial\PlanService;
use App\Services\Commercial\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisioningTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_is_hashed_at_rest_and_can_be_consumed()
    {
        $org = $this->createOrganization();
        $deployment = Deployment::create([
            'organization_id' => $org->id,
            'mode' => DeploymentModeEnum::Local,
        ]);

        $tokenData = ProvisioningToken::generateFor($deployment);
        $plainText = $tokenData['plainTextToken'];

        $tokenModel = $tokenData['token'];

        $this->assertNotEquals($plainText, $tokenModel->token_hash);
        $this->assertTrue(Hash::check($plainText, $tokenModel->token_hash));

        $service = new ProvisioningService(
            new DeploymentService,
            app(PlanService::class),
            app(LicenseService::class)
        );

        $consumedDeployment = $service->consumeProvisioningToken($plainText);

        $this->assertEquals($deployment->id, $consumedDeployment->id);

        $tokenModel->refresh();
        $this->assertEquals('consumed', $tokenModel->status);
        $this->assertNotNull($tokenModel->consumed_at);
    }

    public function test_token_cannot_be_consumed_twice()
    {
        $org = $this->createOrganization();
        $deployment = Deployment::create([
            'organization_id' => $org->id,
            'mode' => DeploymentModeEnum::Local,
        ]);

        $tokenData = ProvisioningToken::generateFor($deployment);
        $plainText = $tokenData['plainTextToken'];

        $service = new ProvisioningService(
            new DeploymentService,
            app(PlanService::class),
            app(LicenseService::class)
        );

        $service->consumeProvisioningToken($plainText);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or inactive provisioning token.');

        $service->consumeProvisioningToken($plainText);
    }
}
