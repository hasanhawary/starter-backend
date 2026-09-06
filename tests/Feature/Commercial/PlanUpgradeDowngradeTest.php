<?php

namespace Tests\Feature\Commercial;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\Ingredient;
use App\Models\License;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Unit;
use App\Services\Commercial\LicenseService;
use App\Services\Commercial\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanUpgradeDowngradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_downgrade_preview_warns_of_removed_features_and_requires_reason(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create(['organization_id' => $org->getKey(), 'name' => ['ar' => 'فرع'], 'code' => 'B1', 'is_active' => true]);
        $standardPlan = Plan::query()->where('code', 'standard')->first();
        $starterPlan = Plan::query()->where('code', 'starter')->first();

        $license = app(LicenseService::class)->issue($org, ['plan_id' => $standardPlan->getKey()])['license'];

        $this->createBranchUser($branch, ['manage-commercial-license']);

        // Preview downgrade
        $previewRes = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/licenses/{$license->getKey()}/preview-plan", [
                'plan_code' => 'starter',
            ]);

        $previewRes->assertOk()
            ->assertJsonPath('data.diff.is_downgrade', true)
            ->assertJsonPath('data.diff.requires_reason', true);

        $removedKeys = collect($previewRes->json('data.diff.removed_features'))->pluck('key')->all();
        $this->assertContains('kds', $removedKeys);
        $this->assertContains('inventory', $removedKeys);

        // Downgrade without reason fails validation
        $failRes = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/licenses/{$license->getKey()}/change-plan", [
                'plan_code' => 'starter',
            ]);
        $failRes->assertUnprocessable()->assertJsonValidationErrors(['reason']);

        // Downgrade with reason succeeds
        $successRes = $this->withHeaders(['X-Branch-ID' => $branch->getKey()])
            ->postJson("/api/v1/commercial/licenses/{$license->getKey()}/change-plan", [
                'plan_code' => 'starter',
                'reason' => 'Customer requested starter tier',
            ]);
        $successRes->assertOk();

        $license->refresh();
        $this->assertSame('starter', $license->plan_code);
        $this->assertFalse($license->features['inventory']);
        $this->assertFalse($license->features['kds']);
    }

    public function test_downgrade_preserves_historical_business_data_intact(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create(['organization_id' => $org->getKey(), 'name' => ['ar' => 'فرع'], 'code' => 'B1', 'is_active' => true]);
        $standardPlan = Plan::query()->where('code', 'standard')->first();
        $license = app(LicenseService::class)->issue($org, ['plan_id' => $standardPlan->getKey()])['license'];

        // Seed domain data created while inventory and POS were active
        $category = Category::create([
            'organization_id' => $org->getKey(),
            'slug' => 'dishes-'.Str::random(6),
            'name' => ['ar' => 'أطباق'],
            'is_active' => true,
        ]);
        $product = Product::create([
            'organization_id' => $org->getKey(),
            'slug' => 'burger-'.Str::random(6),
            'sku' => 'SKU-'.Str::random(6),
            'category_id' => $category->getKey(),
            'name' => ['ar' => 'برجر'],
            'price' => 100,
            'is_active' => true,
        ]);
        $unit = Unit::create([
            'organization_id' => $org->getKey(),
            'code' => 'PCS',
            'name' => ['ar' => 'قطعة', 'en' => 'Piece'],
            'conversion_to_base' => 1,
            'is_base' => true,
        ]);
        $ingredient = Ingredient::create([
            'organization_id' => $org->getKey(),
            'unit_id' => $unit->getKey(),
            'sku' => 'ING-BURGER',
            'name' => ['ar' => 'شريحة لحم'],
            'cost_per_unit' => 20,
            'is_active' => true,
        ]);
        $user = $this->createBranchUser($branch, ['orders-create']);
        $order = Order::create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'cashier_id' => $user->getKey(),
            'order_number' => 'ORD-101',
            'order_type' => 'dine_in',
            'status' => 'settled',
            'total' => 100,
            'payment_status' => 'paid',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        // Downgrade license to starter (removes inventory)
        $planService = app(PlanService::class);
        $starterPlan = Plan::query()->where('code', 'starter')->first();
        $planService->changePlan($license, $starterPlan, [
            'reason' => 'Downgrade test',
        ]);

        // Verify data was NOT deleted or mutated
        $this->assertDatabaseHas('products', ['id' => $product->getKey()]);
        $this->assertDatabaseHas('ingredients', ['id' => $ingredient->getKey()]);
        $this->assertDatabaseHas('orders', ['id' => $order->getKey()]);
    }

    public function test_over_limit_state_does_not_delete_active_devices_and_warns(): void
    {
        $org = $this->createOrganization();
        $branch = Branch::create(['organization_id' => $org->getKey(), 'name' => ['ar' => 'فرع'], 'code' => 'B1', 'is_active' => true]);
        $standardPlan = Plan::query()->where('code', 'standard')->first();
        $license = app(LicenseService::class)->issue($org, [
            'plan_id' => $standardPlan->getKey(),
            'max_devices' => 3,
        ])['license'];

        // Activate 3 devices
        for ($i = 1; $i <= 3; $i++) {
            $device = Device::create(['branch_id' => $branch->getKey(), 'name' => "POS {$i}", 'type' => 'pos', 'is_active' => true]);
            DeviceActivation::create([
                'license_id' => $license->getKey(),
                'device_id' => $device->getKey(),
                'installation_id' => (string) Str::uuid(),
                'activation_token_hash' => 'hash',
                'status' => 'active',
                'activated_at' => now(),
                'last_checkin_at' => now(),
                'offline_grace_expires_at' => now()->addDays(14),
            ]);
        }

        $this->assertSame(3, $license->activations()->where('status', 'active')->count());

        // Downgrade to starter (max_devices = 1)
        $starterPlan = Plan::query()->where('code', 'starter')->first();
        $preview = app(PlanService::class)->previewPlanChange($license, $starterPlan);

        $this->assertTrue($preview['diff']['is_over_limit_devices']);
        $this->assertTrue($preview['diff']['requires_reason']);

        app(PlanService::class)->changePlan($license, $starterPlan, [
            'reason' => 'Reducing commercial device tier',
        ]);

        $license->refresh();
        $this->assertSame(1, $license->max_devices);

        // All 3 device activations still exist in DB (no destructive deletion)
        $this->assertSame(3, $license->activations()->where('status', 'active')->count());

        // Status reflects over-limit state
        $status = app(LicenseService::class)->status($license->activations()->first());
        $this->assertTrue($status['license']['is_over_limit_devices']);
    }
}
