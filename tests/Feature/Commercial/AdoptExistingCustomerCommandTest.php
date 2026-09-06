<?php

namespace Tests\Feature\Commercial;

use App\Enum\Commercial\DeploymentModeEnum;
use App\Enum\Commercial\DeploymentStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdoptExistingCustomerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_adopt_an_existing_organization()
    {
        $org = $this->createOrganization([
            'slug' => 'albaraka',
            'name' => ['en' => 'Al Baraka'],
        ]);

        $this->artisan('pilot:commercial:adopt-existing', ['organization_slug' => 'albaraka'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('deployments', [
            'organization_id' => $org->id,
            'mode' => DeploymentModeEnum::Local->value,
            'status' => DeploymentStatusEnum::Active->value,
            'health_status' => 'UNKNOWN',
        ]);
    }

    public function test_it_does_not_adopt_if_deployment_already_exists()
    {
        $org = $this->createOrganization([
            'slug' => 'albaraka',
        ]);

        $org->deployments()->create([
            'mode' => DeploymentModeEnum::Local,
            'status' => DeploymentStatusEnum::Active,
        ]);

        $this->artisan('pilot:commercial:adopt-existing', ['organization_slug' => 'albaraka'])
            ->expectsOutput("Organization 'albaraka' already has a deployment. Skipping adoption.")
            ->assertExitCode(0);

        $this->assertEquals(1, $org->deployments()->count());
    }

    public function test_it_fails_if_organization_does_not_exist()
    {
        $this->artisan('pilot:commercial:adopt-existing', ['organization_slug' => 'nonexistent'])
            ->expectsOutput("Organization with slug 'nonexistent' not found.")
            ->assertExitCode(1);
    }
}
