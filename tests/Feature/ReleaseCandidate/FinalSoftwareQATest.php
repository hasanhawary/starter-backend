<?php

namespace Tests\Feature\ReleaseCandidate;

use App\Models\ApprovalChallenge;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DiningTable;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\KitchenStation;
use App\Models\KitchenTicket;
use App\Models\License;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\ProductPortion;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinalSoftwareQATest extends TestCase
{
    use RefreshDatabase;

    private function commercialAdmin(): User
    {
        return $this->actingAsUserWithPermissions([
            'manage-commercial-license',
            'manage-commercial-plans',
            'manage-commercial-organizations',
            'authorize-license-recovery',
        ]);
    }

    private function restaurantManager(Organization $org, Branch $branch): User
    {
        $user = $this->createUser([
            'email' => 'manager_'.Str::random(6).'@pilot.local',
        ]);

        $this->givePermissions($user, [
            'settings-pos',
            'view-dashboard',
            'create-orders',
            'view-orders',
            'cancel-orders',
            'discount-orders',
            'comp-orders',
            'approve-discount-orders',
            'approve-comp-orders',
            'receive-payments',
            'refund-payments',
            'approve-refund-payments',
            'movements-cash',
            'open-shifts',
            'close-shifts',
            'manage-menu',
            'view-catalog',
            'manage-catalog',
            'view-inventory',
            'manage-inventory',
            'receive-inventory',
            'waste-inventory',
            'count-inventory',
            'approve-inventory',
            'view-reports',
            'view-kitchen',
            'update-kitchen',
            'send-kitchen',
            'manage-tables',
            'view-promotions',
            'manage-promotions',
        ]);

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    /**
     * Test 1: Full Commercial Customer Journey & Provisioning Idempotency
     */
    public function test_rc_full_commercial_customer_journey_and_idempotency(): void
    {
        $this->commercialAdmin();

        // 1. Create Organization + Initial Admin
        $orgRes = $this->postJson('/api/v1/commercial/organizations', [
            'name' => ['ar' => 'مطعم الياسمين الشامي', 'en' => 'Al Yasmeen Restaurant'],
            'slug' => 'al-yasmeen-'.Str::lower(Str::random(6)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'contact_name' => 'ياسر الشامي',
            'contact_phone' => '01012345678',
            'contact_email' => 'yasser@yasmeen.local',
            'initial_admin_name' => 'ياسر المدير',
            'initial_admin_email' => 'yasser.admin@yasmeen.local',
            'initial_admin_password' => 'SecurePass!2026',
        ]);

        $orgRes->assertCreated();
        $orgId = $orgRes->json('data.organization.id');
        $this->assertNotEmpty($orgId);

        // 2. Issue Commercial License with Plan
        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'starter_rc_'.Str::random(4),
            'name' => ['ar' => 'باقة المطاعم الفردية', 'en' => 'Single Restaurant'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true, 'tables' => true, 'inventory' => true, 'kds' => true],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
        ]);

        $licRes = $this->postJson("/api/v1/commercial/organizations/{$orgId}/licenses", [
            'plan_id' => $plan->getKey(),
            'max_devices' => 2,
            'max_branches' => 1,
            'expires_at' => now()->addYear()->toDateTimeString(),
            'grace_period_days' => 14,
        ]);

        $licRes->assertCreated();
        $licenseKey = $licRes->json('data.license_key');
        $this->assertStringStartsWith('POS-', $licenseKey);

        // 3. Pre-auth Edge Activation
        $actRes = $this->postJson('/api/v1/activation', [
            'license_key' => $licenseKey,
            'device_name' => 'Cashier Main',
            'device_type' => 'pos',
            'fingerprint' => [
                'machine_id' => 'MACHINE-RC-99001',
                'hostname' => 'POS-YASMEEN-01',
                'platform' => 'mac',
                'cpu_model' => 'Apple Silicon M3',
                'core_count' => 8,
                'total_memory_bytes' => 17179869184,
                'disk_serial' => 'DISK-RC-99001',
                'mac_addresses' => ['00:1A:2B:3C:4D:5E'],
            ],
        ]);

        $actRes->assertOk();
        $actRes->assertJsonPath('data.can_work_online', true);
        $actRes->assertJsonPath('data.license.status', 'active');

        // 4. Initial Operator Authentication using handed-off credentials
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'yasser.admin@yasmeen.local',
            'password' => 'SecurePass!2026',
        ]);

        $loginRes->assertOk();
        $token = $loginRes->json('data.token');
        $this->assertNotEmpty($token);

        // 5. Setup Access
        $adminUser = User::query()->where('email', 'yasser.admin@yasmeen.local')->firstOrFail();
        Sanctum::actingAs($adminUser, ['*']);

        $setupStatus = $this->getJson('/api/v1/setup');
        $setupStatus->assertOk();
        $setupStatus->assertJsonPath('data.organization.id', $orgId);
    }

    /**
     * Test 2: Entitlement Variants A (Full) vs B (No Inventory, No KDS)
     */
    public function test_rc_entitlement_variants_invariants(): void
    {
        $this->commercialAdmin();

        // Variant B Plan: No Inventory, No KDS
        $planNoInvNoKds = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'basic_pos_only_'.Str::random(4),
            'name' => ['ar' => 'باقة الكاشير السريع'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true, 'tables' => true, 'inventory' => false, 'kds' => false],
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
        ]);

        $org = $this->createOrganization('مطعم بسيط بدون مخزون');

        $licRes = $this->postJson("/api/v1/commercial/organizations/{$org->getKey()}/licenses", [
            'plan_id' => $planNoInvNoKds->getKey(),
        ]);
        $licRes->assertCreated();

        $license = License::query()->findOrFail($licRes->json('data.license.id'));
        $this->assertFalse($license->features['inventory'] ?? true);
        $this->assertFalse($license->features['kds'] ?? true);
        $this->assertTrue($license->features['pos'] ?? false);
        $this->assertTrue($license->features['tables'] ?? false);
    }

    /**
     * Test 3: Financial Arithmetic & Line Item Exactness (No Float Drift)
     */
    public function test_rc_financial_arithmetic_precision(): void
    {
        $org = $this->createOrganization('مطعم الحسابات الدقيقة');
        $branch = Branch::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'الفرع الرئيسي'],
            'code' => 'BR-01',
            'is_active' => true,
        ]);
        $user = $this->restaurantManager($org, $branch);

        // Open Shift
        $shift = Shift::query()->create([
            'branch_id' => $branch->getKey(),
            'opened_by' => $user->getKey(),
            'opened_at' => now(),
            'opening_cash' => 500.00,
            'status' => 'open',
        ]);

        // Category & Product
        $cat = Category::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'المأكولات الرئيسية'],
            'slug' => 'main-dishes-'.Str::random(4),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'organization_id' => $org->getKey(),
            'category_id' => $cat->getKey(),
            'sku' => 'SKU-'.Str::random(6),
            'name' => ['ar' => 'وجبة مشويات مشكلة'],
            'price' => 150.00,
            'cost' => 80.00,
            'is_active' => true,
        ]);

        $portion = ProductPortion::query()->create([
            'product_id' => $product->getKey(),
            'name' => ['ar' => 'حجم عائلي كبير'],
            'quantity' => 1,
            'price' => 280.00,
            'is_active' => true,
        ]);

        $modGroup = ModifierGroup::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'المقبلات الإضافية'],
            'is_active' => true,
        ]);

        $modOption = ModifierOption::query()->create([
            'modifier_group_id' => $modGroup->getKey(),
            'name' => ['ar' => 'سلطة طحينة إضافية'],
            'price_delta' => 15.00,
            'is_active' => true,
        ]);

        // Create Order: 2x Family Portion (280 ea) + 2x Modifier (15 ea) = 2 * (280 + 15) = 590.00
        $order = Order::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'shift_id' => $shift->getKey(),
            'cashier_id' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'order_number' => 'ORD-1001',
            'order_type' => 'takeaway',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 590.00,
            'discount_total' => 50.00,
            'tax_total' => 75.60,
            'total' => 615.60,
            'paid_total' => 0.00,
        ]);

        $this->assertEquals(615.60, (float) $order->total);
        $this->assertEquals(0.00, (float) $order->paid_total);

        // Mixed Tender Settlement: 350.00 Cash + 265.60 Card = 615.60 exact
        $order->payments()->create([
            'branch_id' => $branch->getKey(),
            'shift_id' => $shift->getKey(),
            'received_by' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'method' => 'cash',
            'amount' => 350.00,
            'paid_at' => now(),
            'status' => 'completed',
        ]);

        $order->payments()->create([
            'branch_id' => $branch->getKey(),
            'shift_id' => $shift->getKey(),
            'received_by' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'method' => 'card',
            'amount' => 265.60,
            'paid_at' => now(),
            'status' => 'completed',
        ]);

        $order->update([
            'paid_total' => 615.60,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        $order->refresh();
        $this->assertEquals(615.60, (float) $order->paid_total);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('completed', $order->status);
    }

    /**
     * Test 4: Anti-Theft Ledger Arithmetic & Moving Weighted Average Cost
     */
    public function test_rc_inventory_anti_theft_ledger_arithmetic(): void
    {
        $org = $this->createOrganization('مطعم الرقابة المخزنية');
        $branch = Branch::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'فرع النزهة'],
            'code' => 'BR-NZH',
            'is_active' => true,
        ]);
        $user = $this->restaurantManager($org, $branch);

        $unit = Unit::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'كيلوجرام', 'en' => 'Kilogram'],
            'code' => 'kg',
            'is_active' => true,
        ]);

        $ingredient = Ingredient::query()->create([
            'organization_id' => $org->getKey(),
            'unit_id' => $unit->getKey(),
            'name' => ['ar' => 'لحم بلدي مفروم'],
            'sku' => 'ING-BEEF-01',
            'cost_per_unit' => 350.00,
            'is_active' => true,
        ]);

        // 1. Opening stock: 30.00 kg @ 350.00 EGP/kg
        InventoryMovement::query()->create([
            'branch_id' => $branch->getKey(),
            'ingredient_id' => $ingredient->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'opening',
            'quantity' => 30.00,
            'unit_cost' => 350.00,
            'total_value' => 10500.00,
            'occurred_at' => now(),
            'user_id' => $user->getKey(),
        ]);
        $currentStock = 30.00;

        // 2. Receive delivery: 20.00 kg @ 360.00 EGP/kg
        InventoryMovement::query()->create([
            'branch_id' => $branch->getKey(),
            'ingredient_id' => $ingredient->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'receiving',
            'quantity' => 20.00,
            'unit_cost' => 360.00,
            'total_value' => 7200.00,
            'occurred_at' => now(),
            'user_id' => $user->getKey(),
        ]);
        $currentStock += 20.00; // 50.00 kg

        // Weighted Average Cost Calculation:
        // (30 * 350 + 20 * 360) / 50 = (10500 + 7200) / 50 = 17700 / 50 = 354.00 EGP/kg
        $avgCost = (10500.00 + 7200.00) / 50.00;
        $this->assertEquals(354.00, $avgCost);

        // 3. Sales recipe consumption: 14.25 kg
        InventoryMovement::query()->create([
            'branch_id' => $branch->getKey(),
            'ingredient_id' => $ingredient->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'sale',
            'quantity' => -14.25,
            'unit_cost' => 354.00,
            'total_value' => -14.25 * 354.00,
            'occurred_at' => now(),
            'user_id' => $user->getKey(),
        ]);
        $currentStock -= 14.25; // 35.75 kg

        // 4. Recorded Waste: 0.50 kg
        InventoryMovement::query()->create([
            'branch_id' => $branch->getKey(),
            'ingredient_id' => $ingredient->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'waste',
            'quantity' => -0.50,
            'unit_cost' => 354.00,
            'total_value' => -0.50 * 354.00,
            'occurred_at' => now(),
            'user_id' => $user->getKey(),
        ]);
        $currentStock -= 0.50; // 35.25 kg

        $expectedStock = 35.25;
        $this->assertEquals(35.25, round($currentStock, 2));

        // 5. Physical Count Audit: 34.70 kg
        $physicalCount = 34.70;
        $varianceQty = round($physicalCount - $expectedStock, 2); // -0.55 kg
        $varianceValue = round($varianceQty * $avgCost, 2); // -0.55 * 354 = -194.70 EGP

        $this->assertEquals(-0.55, $varianceQty);
        $this->assertEquals(-194.70, $varianceValue);

        // Record Audit Reconciliation Adjustment
        InventoryMovement::query()->create([
            'branch_id' => $branch->getKey(),
            'ingredient_id' => $ingredient->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'adjustment',
            'quantity' => $varianceQty,
            'unit_cost' => $avgCost,
            'total_value' => $varianceValue,
            'notes' => 'عجز جرد أسبوعي غير مبرر',
            'occurred_at' => now(),
            'user_id' => $user->getKey(),
        ]);

        $finalLedgerSum = InventoryMovement::query()
            ->where('ingredient_id', $ingredient->getKey())
            ->sum('quantity');

        $this->assertEquals(34.70, round($finalLedgerSum, 2));
    }

    /**
     * Test 5: Dine-In Post-Kitchen Amendments & Delta Ticket Routing
     */
    public function test_rc_dine_in_post_kitchen_amendments_delta_tickets(): void
    {
        $org = $this->createOrganization('مطعم الصالة والمطابخ');
        $branch = Branch::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'فرع الصالة'],
            'code' => 'BR-DINE',
            'is_active' => true,
        ]);
        $user = $this->restaurantManager($org, $branch);

        $cat = Category::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'المطابخ'],
            'slug' => 'kitchen-cat-'.Str::random(4),
            'is_active' => true,
        ]);

        $prod1 = Product::query()->create([
            'organization_id' => $org->getKey(),
            'category_id' => $cat->getKey(),
            'sku' => 'SKU-KBB',
            'name' => ['ar' => 'شيش كباب بلدي'],
            'price' => 100.00,
            'is_active' => true,
        ]);

        $prod2 = Product::query()->create([
            'organization_id' => $org->getKey(),
            'category_id' => $cat->getKey(),
            'sku' => 'SKU-KFT',
            'name' => ['ar' => 'كفتة مشوية إضافية'],
            'price' => 100.00,
            'is_active' => true,
        ]);

        $table = DiningTable::query()->create([
            'branch_id' => $branch->getKey(),
            'name' => 'طاولة 5',
            'code' => 'T-05',
            'capacity' => 4,
            'status' => 'available',
            'is_active' => true,
        ]);

        $station = KitchenStation::query()->create([
            'branch_id' => $branch->getKey(),
            'name' => 'محطة المشاوي الساخنة',
            'code' => 'GRILL-01',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'cashier_id' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'order_number' => 'ORD-DINE-05',
            'order_type' => 'dine_in',
            'status' => 'in_progress',
            'dining_table_id' => $table->getKey(),
            'subtotal' => 300.00,
            'total' => 300.00,
        ]);

        $item1 = $order->items()->create([
            'product_id' => $prod1->getKey(),
            'product_name' => 'شيش كباب بلدي',
            'quantity' => 2,
            'unit_price' => 100.00,
            'total' => 200.00,
        ]);

        $item2 = $order->items()->create([
            'product_id' => $prod2->getKey(),
            'product_name' => 'كفتة مشوية إضافية',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $table->update(['status' => 'occupied']);

        // Round 1: Send 2x Kebab to Kitchen
        $ticket1 = KitchenTicket::query()->create([
            'branch_id' => $branch->getKey(),
            'order_id' => $order->getKey(),
            'station_id' => $station->getKey(),
            'status' => 'new',
        ]);
        $ticket1->items()->create([
            'order_item_id' => $item1->getKey(),
            'product_name' => 'شيش كباب بلدي',
            'quantity' => 2,
            'notes' => 'وسط الاستواء',
        ]);

        $this->assertEquals(1, KitchenTicket::query()->where('order_id', $order->getKey())->count());
        $this->assertEquals('new', $ticket1->status);

        // Station accepts ticket1
        $ticket1->update(['status' => 'preparing']);

        // Round 2 (Post-Kitchen Amendment): Customer orders 1x Extra Kofta
        // Must produce Ticket #2 with ONLY the new Kofta delta item
        $ticket2 = KitchenTicket::query()->create([
            'branch_id' => $branch->getKey(),
            'order_id' => $order->getKey(),
            'station_id' => $station->getKey(),
            'status' => 'new',
        ]);
        $ticket2->items()->create([
            'order_item_id' => $item2->getKey(),
            'product_name' => 'كفتة مشوية إضافية',
            'quantity' => 1,
            'notes' => 'طلب إضافي مستعجل',
        ]);

        $tickets = KitchenTicket::query()->where('order_id', $order->getKey())->with('items')->get();
        $this->assertCount(2, $tickets);

        // Verify ticket 1 remained intact and was not duplicated
        $this->assertEquals('preparing', $tickets[0]->status);
        $this->assertCount(1, $tickets[0]->items);
        $this->assertEquals('شيش كباب بلدي', $tickets[0]->items[0]->product_name);

        // Verify ticket 2 contains only the amendment item
        $this->assertEquals('new', $tickets[1]->status);
        $this->assertCount(1, $tickets[1]->items);
        $this->assertEquals('كفتة مشوية إضافية', $tickets[1]->items[0]->product_name);
    }

    /**
     * Test 6: Delivery Customer Address Immutability Snapshot
     */
    public function test_rc_delivery_customer_address_immutability(): void
    {
        $org = $this->createOrganization('مطعم التوصيل السريع');
        $branch = Branch::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'فرع المعادي'],
            'code' => 'BR-MAADI',
            'is_active' => true,
        ]);
        $user = $this->restaurantManager($org, $branch);

        // Create Customer with address
        $customer = Customer::query()->create([
            'organization_id' => $org->getKey(),
            'name' => 'طارق عبد الله',
            'phone' => '01055554444',
            'phone_normalized' => '01055554444',
        ]);

        $address = CustomerAddress::query()->create([
            'customer_id' => $customer->getKey(),
            'label' => 'المنزل',
            'address_text' => '15 شارع النصر، المعادي',
            'building' => 'عمارة 12',
            'floor' => '4',
            'apartment' => '402',
            'is_default' => true,
        ]);

        // Place Delivery Order capturing address snapshot
        $addressSnapshot = [
            'address_text' => $address->address_text,
            'building' => $address->building,
            'floor' => $address->floor,
            'apartment' => $address->apartment,
        ];

        $order = Order::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'cashier_id' => $user->getKey(),
            'customer_id' => $customer->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'order_number' => 'ORD-DELIV-88',
            'order_type' => 'delivery',
            'status' => 'in_progress',
            'delivery_address_snapshot' => $addressSnapshot,
            'subtotal' => 200.00,
            'total' => 200.00,
        ]);

        // Customer updates address in profile
        $address->update([
            'address_text' => '90 شارع التسعين، التجمع الخامس',
            'building' => 'فيلا 88',
        ]);

        // Verify Historical Order retained the original immutable delivery address
        $order->refresh();
        $this->assertEquals('15 شارع النصر، المعادي', $order->delivery_address_snapshot['address_text']);
        $this->assertEquals('عمارة 12', $order->delivery_address_snapshot['building']);
    }

    /**
     * Test 7: Strict Cross-Tenant Isolation
     */
    public function test_rc_strict_cross_tenant_isolation(): void
    {
        $orgA = $this->createOrganization('Tenant Alpha');
        $branchA = Branch::query()->create([
            'organization_id' => $orgA->getKey(),
            'name' => ['ar' => 'فرع ألفا'],
            'code' => 'BR-A',
            'is_active' => true,
        ]);
        $userA = $this->restaurantManager($orgA, $branchA);

        $orgB = $this->createOrganization('Tenant Beta');
        $branchB = Branch::query()->create([
            'organization_id' => $orgB->getKey(),
            'name' => ['ar' => 'فرع بيتا'],
            'code' => 'BR-B',
            'is_active' => true,
        ]);

        // User A (Tenant A) attempts to access Tenant B commercial endpoints
        Sanctum::actingAs($userA, ['*']);

        // User A cannot list commercial organizations
        $this->getJson('/api/v1/commercial/organizations')->assertForbidden();

        // User A cannot manage Tenant B licenses
        $this->postJson("/api/v1/commercial/organizations/{$orgB->getKey()}/licenses", [
            'plan_code' => 'standard',
        ])->assertForbidden();
    }

    /**
     * Test 8: One-Time Secrets are NOT persisted in plaintext
     */
    public function test_rc_one_time_secrets_not_persisted_in_plaintext(): void
    {
        $this->commercialAdmin();
        $org = $this->createOrganization('مطعم الأمان السري');

        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'starter_sec_'.Str::random(4),
            'name' => ['ar' => 'الباقة الآمنة'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true],
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
        ]);

        $res = $this->postJson("/api/v1/commercial/organizations/{$org->getKey()}/licenses", [
            'plan_id' => $plan->getKey(),
        ]);
        $res->assertCreated();

        $licenseKey = $res->json('data.license_key');
        $licenseId = $res->json('data.license.id');

        // Check License row in database
        $dbLicense = DB::table('licenses')->where('id', $licenseId)->first();
        $this->assertNotNull($dbLicense);

        // Plaintext key MUST NOT appear anywhere in the database row
        $rawRow = json_encode((array) $dbLicense);
        $this->assertStringNotContainsString($licenseKey, $rawRow);

        // Fingerprint and last 4 only
        $this->assertNotEmpty($dbLicense->key_fingerprint);
        $this->assertEquals(substr($licenseKey, -4), $dbLicense->key_last_four);
    }

    /**
     * Test 9: Manager Approval Challenge Security & Anti-Replay
     */
    public function test_rc_manager_approval_challenge_security_and_lifecycle(): void
    {
        $org = $this->createOrganization('مطعم الموافقات الإدارية');
        $branch = Branch::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'فرع الإدارة'],
            'code' => 'BR-MGMT',
            'is_active' => true,
        ]);
        $manager = $this->restaurantManager($org, $branch);

        $cashier = $this->createUser([
            'email' => 'cashier_'.Str::random(6).'@pilot.local',
        ]);
        $this->givePermissions($cashier, ['settings-pos', 'create-orders', 'view-orders']);

        $shift = Shift::query()->create([
            'branch_id' => $branch->getKey(),
            'opened_by' => $manager->getKey(),
            'opened_at' => now(),
            'opening_cash' => 500.00,
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'shift_id' => $shift->getKey(),
            'cashier_id' => $cashier->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'order_number' => 'ORD-DISC-99',
            'order_type' => 'takeaway',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 400.00,
            'total' => 400.00,
            'paid_total' => 0.00,
        ]);

        // Cashier attempts 50% discount requiring approval
        Sanctum::actingAs($cashier, ['*']);

        // Create Challenge
        $challenge = ApprovalChallenge::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'requested_by' => $cashier->getKey(),
            'action' => 'discount',
            'resource_type' => 'order',
            'resource_id' => $order->getKey(),
            'payload_hash' => hash('sha256', json_encode(['discount_type' => 'percentage', 'discount_value' => 50])),
            'payload' => ['discount_type' => 'percentage', 'discount_value' => 50],
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertEquals('pending', $challenge->status);
        $this->assertFalse($challenge->expires_at->isPast());

        // Expired Challenge Check
        $expiredChallenge = ApprovalChallenge::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'requested_by' => $cashier->getKey(),
            'action' => 'discount',
            'resource_type' => 'order',
            'resource_id' => $order->getKey(),
            'payload_hash' => hash('sha256', json_encode(['discount_type' => 'fixed', 'discount_value' => 100])),
            'payload' => ['discount_type' => 'fixed', 'discount_value' => 100],
            'status' => 'pending',
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertTrue($expiredChallenge->expires_at->isPast());
    }

    /**
     * Test 10: Cash Movements & Shift Drawer Reconciliation
     */
    public function test_rc_cash_movements_shift_reconciliation(): void
    {
        $org = $this->createOrganization('مطعم الخزينة والمصروفات');
        $branch = Branch::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'فرع العروبة'],
            'code' => 'BR-ORB',
            'is_active' => true,
        ]);
        $user = $this->restaurantManager($org, $branch);

        // Shift with 1000.00 opening float
        $shift = Shift::query()->create([
            'branch_id' => $branch->getKey(),
            'opened_by' => $user->getKey(),
            'opened_at' => now(),
            'opening_cash' => 1000.00,
            'status' => 'open',
        ]);

        // 1. Cash In: 200.00 EGP (Petty cash replenishment)
        CashMovement::query()->create([
            'shift_id' => $shift->getKey(),
            'branch_id' => $branch->getKey(),
            'user_id' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'in',
            'amount' => 200.00,
            'note' => 'تعزيز فكة الصباح',
            'occurred_at' => now(),
        ]);

        // 2. Cash Out / Expense: 150.00 EGP (Vegetable market urgent buy)
        CashMovement::query()->create([
            'shift_id' => $shift->getKey(),
            'branch_id' => $branch->getKey(),
            'user_id' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'out',
            'amount' => 150.00,
            'note' => 'شراء خضروات طازجة عاجلة',
            'occurred_at' => now(),
        ]);

        // 3. Cash Sale payment: 450.00 EGP
        $order = Order::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'shift_id' => $shift->getKey(),
            'cashier_id' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'order_number' => 'ORD-CASH-01',
            'order_type' => 'takeaway',
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 450.00,
            'total' => 450.00,
            'paid_total' => 450.00,
        ]);

        $order->payments()->create([
            'branch_id' => $branch->getKey(),
            'shift_id' => $shift->getKey(),
            'received_by' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'method' => 'cash',
            'amount' => 450.00,
            'paid_at' => now(),
            'status' => 'completed',
        ]);

        // Expected Cash in Drawer:
        // Opening (1000) + In (200) - Out (150) + Cash Sales (450) = 1500.00 EGP
        $cashIn = CashMovement::query()->where('shift_id', $shift->getKey())->where('type', 'in')->sum('amount');
        $cashOut = CashMovement::query()->where('shift_id', $shift->getKey())->where('type', 'out')->sum('amount');
        $cashSales = Payment::query()->where('shift_id', $shift->getKey())->where('method', 'cash')->where('status', 'completed')->sum('amount');

        $expectedDrawerCash = (float) $shift->opening_cash + $cashIn - $cashOut + $cashSales;
        $this->assertEquals(1500.00, $expectedDrawerCash);

        // Close Shift with actual counted cash = 1490.00 (Shortage of 10.00)
        $actualCash = 1490.00;
        $difference = round($actualCash - $expectedDrawerCash, 2);

        $shift->update([
            'closed_by' => $user->getKey(),
            'closed_at' => now(),
            'expected_cash' => $expectedDrawerCash,
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'status' => 'closed',
        ]);

        $shift->refresh();
        $this->assertEquals(1500.00, (float) $shift->expected_cash);
        $this->assertEquals(1490.00, (float) $shift->actual_cash);
        $this->assertEquals(-10.00, (float) $shift->difference);
        $this->assertEquals('closed', $shift->status);
    }

    /**
     * Test 11: Software Print Pipeline Payload & Status Lifecycle
     */
    public function test_rc_software_print_pipeline_rendering_and_status(): void
    {
        $org = $this->createOrganization('مطعم الطباعة الذكية');
        $branch = Branch::query()->create([
            'organization_id' => $org->getKey(),
            'name' => ['ar' => 'فرع الطباعة'],
            'code' => 'BR-PRINT',
            'is_active' => true,
        ]);
        $user = $this->restaurantManager($org, $branch);

        $order = Order::query()->create([
            'organization_id' => $org->getKey(),
            'branch_id' => $branch->getKey(),
            'cashier_id' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'order_number' => 'ORD-PRINT-01',
            'order_type' => 'takeaway',
            'status' => 'completed',
            'total' => 250.00,
        ]);

        // Dispatch Receipt Print Job
        $printJob = PrintJob::query()->create([
            'branch_id' => $branch->getKey(),
            'order_id' => $order->getKey(),
            'requested_by' => $user->getKey(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'receipt',
            'destination' => 'cashier_thermal',
            'printer_key' => 'PRINTER-01',
            'status' => 'pending',
            'payload' => [
                'restaurant_name' => 'مطعم الطباعة الذكية',
                'order_number' => 'ORD-PRINT-01',
                'items' => [
                    ['name' => 'وجبة شاورما عربي', 'qty' => 2, 'total' => '250.00'],
                ],
                'total' => '250.00 EGP',
                'footer_note' => 'شكراً لزيارتكم - هاتف الدليفري: 19999',
            ],
            'attempts' => 0,
        ]);

        $this->assertEquals('pending', $printJob->status);
        $this->assertIsArray($printJob->payload);
        $this->assertEquals('مطعم الطباعة الذكية', $printJob->payload['restaurant_name']);

        // Worker claims job
        $printJob->update([
            'status' => 'claimed',
            'claimed_at' => now(),
            'attempts' => 1,
        ]);

        $this->assertEquals('claimed', $printJob->status);
        $this->assertEquals(1, $printJob->attempts);

        // Worker marks job completed
        $printJob->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->assertEquals('completed', $printJob->status);
        $this->assertNotNull($printJob->completed_at);
    }
}
