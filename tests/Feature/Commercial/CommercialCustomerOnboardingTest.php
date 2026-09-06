<?php

namespace Tests\Feature\Commercial;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommercialCustomerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function commercialAdmin(): User
    {
        return $this->actingAsUserWithPermissions([
            'manage-commercial-license',
            'manage-commercial-plans',
            'manage-commercial-organizations',
        ]);
    }

    public function test_commercial_admin_can_create_customer_organization_and_initial_admin(): void
    {
        $this->commercialAdmin();

        $response = $this->postJson('/api/v1/commercial/organizations', [
            'name' => [
                'ar' => 'مطعم الأصيل للمأكولات',
                'en' => 'Al Aseel Restaurant',
            ],
            'slug' => 'al-aseel-'.Str::lower(Str::random(6)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'contact_name' => 'محمد الأصيل',
            'contact_phone' => '01099887766',
            'contact_email' => 'contact@al-aseel.com',
            'notes' => 'عميل تجاري VIP - عقد سنوي',
            'initial_admin_name' => 'مدير المطعم',
            'initial_admin_email' => 'admin@al-aseel.com',
            'initial_admin_password' => 'AseelPass!2026',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.organization.name.ar', 'مطعم الأصيل للمأكولات');
        $response->assertJsonPath('data.organization.contact_phone', '01099887766');
        $response->assertJsonPath('data.initial_user.email', 'admin@al-aseel.com');
        $response->assertJsonPath('data.initial_user.temporary_password', 'AseelPass!2026');

        $orgId = $response->json('data.organization.id');
        $this->assertDatabaseHas('organizations', ['id' => $orgId, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['email' => 'admin@al-aseel.com']);
    }

    public function test_settings_pos_alone_cannot_create_organization_or_issue_license(): void
    {
        $user = $this->actingAsUserWithPermissions(['settings-pos']);
        $org = $this->createOrganization('Unauthorized Org');

        $this->postJson('/api/v1/commercial/organizations', [
            'name' => ['ar' => 'محاولة اختراق'],
        ])->assertForbidden();

        $this->postJson("/api/v1/commercial/organizations/{$org->getKey()}/licenses", [
            'plan_code' => 'standard',
        ])->assertForbidden();

        $this->getJson('/api/v1/commercial/organizations')->assertForbidden();
    }

    public function test_commercial_admin_can_issue_initial_license_with_one_time_key_and_audit(): void
    {
        $this->commercialAdmin();
        $org = $this->createOrganization('مطعم المشويات');
        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'starter_test_'.Str::random(4),
            'name' => ['ar' => 'الباقة الأساسية', 'en' => 'Starter'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true, 'tables' => true, 'inventory' => false],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
        ]);

        $response = $this->postJson("/api/v1/commercial/organizations/{$org->getKey()}/licenses", [
            'plan_id' => $plan->getKey(),
            'max_devices' => 3,
            'max_branches' => 2,
            'expires_at' => now()->addYear()->toDateTimeString(),
            'grace_period_days' => 14,
            'notes' => 'ترخيص افتتاحي للمطعم',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'license' => ['id', 'organization_id', 'plan_code', 'max_devices', 'max_branches', 'key_last_four'],
                'license_key',
            ],
        ]);

        $licenseKey = $response->json('data.license_key');
        $this->assertStringStartsWith('POS-', $licenseKey);

        $licenseId = $response->json('data.license.id');
        $this->assertDatabaseHas('licenses', [
            'id' => $licenseId,
            'organization_id' => $org->getKey(),
            'status' => 'active',
            'max_devices' => 3,
            'max_branches' => 2,
        ]);

        // Verify audit event
        $this->assertDatabaseHas('commercial_license_events', [
            'license_id' => $licenseId,
            'event' => 'LICENSE_ISSUED',
        ]);
    }

    public function test_cannot_issue_duplicate_active_license_for_same_organization(): void
    {
        $this->commercialAdmin();
        $org = $this->createOrganization('مطعم التكرار');
        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'plan_dup_'.Str::random(4),
            'name' => ['ar' => 'باقة الاختبار'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true],
            'default_limits' => ['max_devices' => 1, 'max_branches' => 1],
        ]);

        // Issue first license
        $this->postJson("/api/v1/commercial/organizations/{$org->getKey()}/licenses", [
            'plan_id' => $plan->getKey(),
        ])->assertCreated();

        // Attempting to issue a second active license fails
        $this->postJson("/api/v1/commercial/organizations/{$org->getKey()}/licenses", [
            'plan_id' => $plan->getKey(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['license']);
    }

    public function test_customer_list_supports_search_and_pagination(): void
    {
        $this->commercialAdmin();

        $orgAlpha = Organization::create([
            'name' => ['ar' => 'مطعم ألفا المميز', 'en' => 'Alpha Special'],
            'slug' => 'alpha-special-'.Str::lower(Str::random(4)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'settings' => ['contact_phone' => '01011112222', 'contact_name' => 'أحمد ألفا'],
            'onboarding_status' => 'COMPLETED',
            'is_active' => true,
        ]);

        $orgBeta = Organization::create([
            'name' => ['ar' => 'مطعم بيتا السريع', 'en' => 'Beta Fast'],
            'slug' => 'beta-fast-'.Str::lower(Str::random(4)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'settings' => ['contact_phone' => '01033334444', 'contact_name' => 'محمود بيتا'],
            'onboarding_status' => 'NOT_STARTED',
            'is_active' => true,
        ]);

        // Search by name
        $this->getJson('/api/v1/commercial/organizations?'.http_build_query(['search' => 'ألفا']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $orgAlpha->getKey());

        // Search by phone
        $this->getJson('/api/v1/commercial/organizations?'.http_build_query(['search' => '01033334444']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $orgBeta->getKey());

        // Filter by onboarding status
        $this->getJson('/api/v1/commercial/organizations?'.http_build_query(['onboarding_status' => 'COMPLETED']))
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $orgAlpha->getKey());
    }

    public function test_customer_detail_exposes_comprehensive_status_without_leaking_plaintext_keys(): void
    {
        $this->commercialAdmin();
        $org = $this->createOrganization('مطعم التفاصيل');

        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'plan_detail_'.Str::random(4),
            'name' => ['ar' => 'باقة التفاصيل'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true, 'tables' => true],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
        ]);

        $issueRes = $this->postJson("/api/v1/commercial/organizations/{$org->getKey()}/licenses", [
            'plan_id' => $plan->getKey(),
        ]);
        $licenseKey = $issueRes->json('data.license_key');

        $detailRes = $this->getJson("/api/v1/commercial/organizations/{$org->getKey()}");
        $detailRes->assertOk();
        $detailRes->assertJsonPath('data.id', $org->getKey());
        $detailRes->assertJsonPath('data.active_license.plan_code', $plan->code);
        $detailRes->assertJsonPath('data.derived_status', 'PROVISIONED');

        // Plaintext key must NOT be in detail response
        $detailString = $detailRes->getContent();
        $this->assertStringNotContainsString($licenseKey, $detailString);
    }

    public function test_end_to_end_provisioned_customer_can_activate_login_and_reach_setup(): void
    {
        $this->commercialAdmin();

        // 1. Pilot operator provisions customer & initial admin
        $orgRes = $this->postJson('/api/v1/commercial/organizations', [
            'name' => ['ar' => 'مطعم التجربة الشاملة', 'en' => 'E2E Restaurant'],
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'initial_admin_name' => 'أحمد المدير',
            'initial_admin_email' => 'e2e.manager@pilot.local',
            'initial_admin_password' => 'PilotPass!2026',
        ])->assertCreated();

        $orgId = $orgRes->json('data.organization.id');

        $plan = Plan::query()->create([
            'id' => (string) Str::uuid(),
            'code' => 'plan_e2e_'.Str::random(4),
            'name' => ['ar' => 'باقة التجربة'],
            'status' => 'active',
            'version' => 1,
            'default_entitlements' => ['pos' => true, 'tables' => true],
            'default_limits' => ['max_devices' => 2, 'max_branches' => 1],
        ]);

        // 2. Issue License
        $licRes = $this->postJson("/api/v1/commercial/organizations/{$orgId}/licenses", [
            'plan_id' => $plan->getKey(),
            'max_devices' => 2,
        ])->assertCreated();

        $licenseKey = $licRes->json('data.license_key');

        // 3. Edge activation with issued license key
        $activateRes = $this->postJson('/api/v1/activation', [
            'license_key' => $licenseKey,
            'device_name' => 'Cashier Main 01',
            'device_type' => 'pos',
            'fingerprint' => [
                'machine_id' => 'MACHINE-E2E-12345',
                'hostname' => 'POS-TERMINAL-01',
                'platform' => 'mac',
                'cpu_model' => 'Apple M3',
                'core_count' => 8,
                'total_memory_bytes' => 17179869184,
                'disk_serial' => 'DISK-E2E-9988',
                'mac_addresses' => ['00:11:22:33:44:55'],
            ],
        ]);
        $activateRes->assertOk();
        $activateRes->assertJsonPath('data.can_work_online', true);
        $activateRes->assertJsonPath('data.license.status', 'active');

        // 4. Initial admin login using handed-off credentials
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'e2e.manager@pilot.local',
            'password' => 'PilotPass!2026',
        ]);
        $loginRes->assertOk();
        $loginRes->assertJsonPath('data.user.email', 'e2e.manager@pilot.local');

        $token = $loginRes->json('data.token');

        // 5. User accesses setup endpoint with auth token
        $loggedInUser = User::query()->where('email', 'e2e.manager@pilot.local')->first();
        Sanctum::actingAs($loggedInUser, ['*']);

        $setupRes = $this->getJson('/api/v1/setup');
        $setupRes->assertOk();
        $setupRes->assertJsonPath('data.organization.id', $orgId);
    }
}
