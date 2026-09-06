<?php

namespace Tests\Feature\Commercial;

use App\Enum\Commercial\DeploymentModeEnum;
use App\Models\Branch;
use App\Models\Deployment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_can_have_multiple_deployments()
    {
        $org = $this->createOrganization();
        $branch1 = Branch::create(['organization_id' => $org->id, 'name' => ['en' => 'B1'], 'code' => 'B1']);
        $branch2 = Branch::create(['organization_id' => $org->id, 'name' => ['en' => 'B2'], 'code' => 'B2']);

        $deployment1 = Deployment::create([
            'organization_id' => $org->id,
            'branch_id' => $branch1->id,
            'mode' => DeploymentModeEnum::Local,
        ]);

        $deployment2 = Deployment::create([
            'organization_id' => $org->id,
            'branch_id' => $branch2->id,
            'mode' => DeploymentModeEnum::Local,
        ]);

        $deployment3 = Deployment::create([
            'organization_id' => $org->id,
            'mode' => DeploymentModeEnum::Cloud,
        ]);

        $this->assertEquals(3, $org->deployments()->count());
    }

    public function test_organization_delete_is_restricted_if_deployments_exist()
    {
        $org = $this->createOrganization();
        Deployment::create([
            'organization_id' => $org->id,
            'mode' => DeploymentModeEnum::Local,
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');

        $org->delete();
    }
}
