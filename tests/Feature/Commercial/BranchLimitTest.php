<?php

namespace Tests\Feature\Commercial;

use App\Models\Branch;
use App\Models\Plan;
use App\Services\Branch\BranchService;
use App\Services\Commercial\LicenseService;
use App\Services\Commercial\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BranchLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_service_blocks_creation_when_max_branches_reached(): void
    {
        $org = $this->createOrganization();
        $starterPlan = Plan::query()->where('code', 'starter')->first(); // max_branches = 1
        app(LicenseService::class)->issue($org, ['plan_id' => $starterPlan->getKey()]);

        $branchService = app(BranchService::class);

        // First active branch succeeds
        $b1 = $branchService->create($org, [
            'name' => ['ar' => 'الفرع الرئيسي'],
            'code' => 'MAIN',
            'is_active' => true,
        ]);
        $this->assertTrue($b1->exists);

        // Second active branch throws ValidationException
        $this->expectException(ValidationException::class);
        $branchService->create($org, [
            'name' => ['ar' => 'فرع إضافي'],
            'code' => 'EXTRA',
            'is_active' => true,
        ]);
    }

    public function test_inactive_branches_do_not_consume_active_slot(): void
    {
        $org = $this->createOrganization();
        $starterPlan = Plan::query()->where('code', 'starter')->first(); // max_branches = 1
        app(LicenseService::class)->issue($org, ['plan_id' => $starterPlan->getKey()]);

        $branchService = app(BranchService::class);

        // Create 1 active branch
        $b1 = $branchService->create($org, [
            'name' => ['ar' => 'فرع 1'],
            'code' => 'B1',
            'is_active' => true,
        ]);

        // Create 1 inactive branch (e.g. prepared or archived branch) - allowed
        $b2 = $branchService->create($org, [
            'name' => ['ar' => 'فرع مسودة'],
            'code' => 'DRAFT',
            'is_active' => false,
        ]);
        $this->assertFalse($b2->is_active);

        // Deactivating b1 allows activating b2
        $branchService->deactivate($b1);
        $this->assertFalse($b1->fresh()->is_active);

        $branchService->activate($b2);
        $this->assertTrue($b2->fresh()->is_active);

        // Now trying to re-activate b1 will be blocked because b2 is active
        $this->expectException(ValidationException::class);
        $branchService->activate($b1);
    }

    public function test_upgrading_plan_allows_additional_branches(): void
    {
        $org = $this->createOrganization();
        $starterPlan = Plan::query()->where('code', 'starter')->first(); // max_branches = 1
        $enterprisePlan = Plan::query()->where('code', 'enterprise')->first(); // max_branches = 5

        $license = app(LicenseService::class)->issue($org, ['plan_id' => $starterPlan->getKey()])['license'];
        $branchService = app(BranchService::class);

        $branchService->create($org, [
            'name' => ['ar' => 'فرع 1'],
            'code' => 'B1',
            'is_active' => true,
        ]);

        // Upgrade to enterprise plan
        app(PlanService::class)->changePlan($license, $enterprisePlan);

        // Now creating second, third branches succeeds
        $b2 = $branchService->create($org, [
            'name' => ['ar' => 'فرع 2'],
            'code' => 'B2',
            'is_active' => true,
        ]);
        $b3 = $branchService->create($org, [
            'name' => ['ar' => 'فرع 3'],
            'code' => 'B3',
            'is_active' => true,
        ]);

        $this->assertSame(3, $branchService->activeBranchCount($org));
        $this->assertSame(5, $branchService->maxBranches($org));
    }
}
