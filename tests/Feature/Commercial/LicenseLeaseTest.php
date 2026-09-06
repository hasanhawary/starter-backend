<?php

namespace Tests\Feature\Commercial;

use App\Models\Branch;
use App\Models\DeviceActivation;
use App\Models\EdgeConfiguration;
use App\Models\EdgeDeviceCredential;
use App\Models\Employee;
use App\Models\Organization;
use App\Services\Commercial\EntitlementService;
use App\Services\Commercial\LicenseLeaseService;
use App\Services\Commercial\LicenseLeaseStore;
use App\Services\Commercial\LicenseLeaseVerifier;
use App\Services\Commercial\LicenseService;
use App\Services\Edge\EdgeConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class LicenseLeaseTest extends TestCase
{
    use RefreshDatabase;

    private string $licensePath;

    private string $deviceCredential;

    protected function setUp(): void
    {
        parent::setUp();

        $keyPair = sodium_crypto_sign_keypair();
        $this->licensePath = storage_path('framework/testing/license-'.Str::uuid());
        config([
            'edge.mode' => 'edge-production',
            'edge.paths.license' => $this->licensePath,
            'commercial.license.signing_key_id' => 'test-key-1',
            'commercial.license.signing_private_key' => 'base64:'.base64_encode(sodium_crypto_sign_secretkey($keyPair)),
            'commercial.license.trusted_public_keys' => [
                'test-key-1' => 'base64:'.base64_encode(sodium_crypto_sign_publickey($keyPair)),
            ],
            'commercial.license.lease_duration_seconds' => 3600,
            'commercial.license.refresh_window_seconds' => 7200,
            'commercial.license.clock_tolerance_seconds' => 120,
            'commercial.license.max_forward_jump_seconds' => 86400,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->licensePath);
        $this->travelBack();

        parent::tearDown();
    }

    public function test_signed_lease_verifies_and_tampering_is_rejected_without_replacing_the_current_lease(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge(['payroll' => false]);
        $leaseService = app(LicenseLeaseService::class);
        $lease = $leaseService->issueAndPersist($activation, $configuration);

        $this->assertSame(LicenseLeaseVerifier::ACTIVE, app(LicenseLeaseVerifier::class)->current($configuration)['state']);

        $tampered = $lease;
        $tampered['payload']['entitlements']['payroll'] = true;
        $result = app(LicenseLeaseVerifier::class)->persistVerified($tampered, $configuration);

        $this->assertSame(LicenseLeaseVerifier::INVALID, $result['state']);
        $this->assertSame($lease['payload']['lease_id'], app(LicenseLeaseStore::class)->readLease()['payload']['lease_id']);
        $this->assertSame('INVALID_SIGNATURE', $result['reason']);
    }

    public function test_an_older_valid_lease_cannot_replace_a_newer_lease(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge();
        $this->travelTo('2026-08-16 12:00:00');
        $older = app(LicenseLeaseService::class)->issue($activation);
        app(LicenseLeaseVerifier::class)->persistVerified($older, $configuration);

        $this->travelTo('2026-08-16 12:05:00');
        $newer = app(LicenseLeaseService::class)->issue($activation->fresh(['license', 'device']));
        app(LicenseLeaseVerifier::class)->persistVerified($newer, $configuration);
        $replay = app(LicenseLeaseVerifier::class)->persistVerified($older, $configuration);

        $this->assertSame('STALE_LEASE', $replay['reason']);
        $this->assertSame($newer['payload']['lease_id'], app(LicenseLeaseStore::class)->readLease()['payload']['lease_id']);
    }

    public function test_lease_lifecycle_supports_active_grace_and_restricted_states(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge([], gracePeriodDays: 1);
        $this->travelTo('2026-08-16 12:00:00');
        app(LicenseLeaseService::class)->issueAndPersist($activation, $configuration);

        $this->assertSame(LicenseLeaseVerifier::ACTIVE, app(LicenseLeaseVerifier::class)->current($configuration)['state']);

        $this->travelTo('2026-08-16 13:30:00');
        $this->assertSame(LicenseLeaseVerifier::GRACE, app(LicenseLeaseVerifier::class)->current($configuration)['state']);

        $this->travelTo('2026-08-18 13:30:00');
        $this->assertSame(LicenseLeaseVerifier::RESTRICTED, app(LicenseLeaseVerifier::class)->current($configuration)['state']);
    }

    public function test_binding_validation_rejects_device_branch_organization_and_type_mismatches(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge();
        $lease = app(LicenseLeaseService::class)->issueAndPersist($activation, $configuration);

        $configuration->device_id = (string) Str::uuid();
        $this->assertSame('DEVICE_ID_MISMATCH', app(LicenseLeaseVerifier::class)->verify($lease, $configuration)['reason']);

        $configuration->device_id = $activation->device_id;
        $configuration->branch_id = (string) Str::uuid();
        $this->assertSame('BRANCH_ID_MISMATCH', app(LicenseLeaseVerifier::class)->verify($lease, $configuration)['reason']);

        $configuration->branch_id = $activation->device->branch_id;
        $configuration->organization_id = (string) Str::uuid();
        $this->assertSame('ORGANIZATION_ID_MISMATCH', app(LicenseLeaseVerifier::class)->verify($lease, $configuration)['reason']);

        $configuration->organization_id = $activation->license->organization_id;
        $configuration->device->type = 'register';
        $this->assertSame('DEVICE_TYPE_MISMATCH', app(LicenseLeaseVerifier::class)->verify($lease, $configuration)['reason']);
    }

    public function test_clock_rollback_requires_time_verification_but_small_drift_is_allowed(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge();
        $this->travelTo('2026-08-16 12:00:00');
        app(LicenseLeaseService::class)->issueAndPersist($activation, $configuration);

        $this->travelTo('2026-08-16 12:00:30');
        $this->assertSame(LicenseLeaseVerifier::ACTIVE, app(LicenseLeaseVerifier::class)->current($configuration)['state']);

        $this->travelTo('2026-08-15 12:00:00');
        $result = app(LicenseLeaseVerifier::class)->current($configuration);
        $this->assertSame(LicenseLeaseVerifier::TIME_VERIFICATION_REQUIRED, $result['state']);
        $this->assertSame('ROLLBACK_DETECTED', $result['reason']);
    }

    public function test_signed_suspension_revocation_and_device_revocation_are_distinguished(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge();
        $leaseService = app(LicenseLeaseService::class);
        $leaseService->issueAndPersist($activation, $configuration);

        $activation->license->update(['status' => 'suspended']);
        $suspendedLease = $leaseService->issue($activation->fresh(['license', 'device']));
        app(LicenseLeaseVerifier::class)->persistVerified($suspendedLease, $configuration);
        $this->assertSame(LicenseLeaseVerifier::SUSPENDED, app(LicenseLeaseVerifier::class)->current($configuration)['state']);

        $activation->license->update(['status' => 'revoked']);
        $revokedLease = $leaseService->issue($activation->fresh(['license', 'device']));
        app(LicenseLeaseVerifier::class)->persistVerified($revokedLease, $configuration);
        $this->assertSame(LicenseLeaseVerifier::REVOKED, app(LicenseLeaseVerifier::class)->current($configuration)['state']);

        $activation->device->update(['is_active' => false]);
        $result = app(LicenseLeaseVerifier::class)->current($configuration->fresh(['device']));
        $this->assertSame(LicenseLeaseVerifier::INVALID, $result['state']);
        $this->assertSame('DEVICE_REVOKED', $result['reason']);
    }

    public function test_unknown_key_and_corrupt_or_missing_lease_never_fabricate_entitlements(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge(['payroll' => true]);
        $lease = app(LicenseLeaseService::class)->issueAndPersist($activation, $configuration);
        $unknownKey = [...$lease, 'kid' => 'unknown-key'];

        $this->assertSame('UNKNOWN_SIGNING_KEY', app(LicenseLeaseVerifier::class)->verify($unknownKey, $configuration)['reason']);

        File::put($this->licensePath.'/current-lease.json', '{not-json');
        $this->assertSame('CORRUPT_LEASE', app(LicenseLeaseVerifier::class)->current($configuration)['reason']);

        File::delete($this->licensePath.'/current-lease.json');
        $this->assertSame('MISSING_LEASE', app(LicenseLeaseVerifier::class)->current($configuration)['reason']);
    }

    public function test_cloud_outage_retains_the_last_valid_lease_and_is_not_revocation(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge();
        $lease = app(LicenseLeaseService::class)->issueAndPersist($activation, $configuration);
        $configuration->update(['cloud_endpoint' => 'https://cloud.test']);
        Http::fake(['https://cloud.test/*' => Http::failedConnection()]);

        $result = app(LicenseLeaseService::class)->refresh($configuration->fresh());

        $this->assertSame('CLOUD_UNAVAILABLE', $result['status']);
        $this->assertSame(LicenseLeaseVerifier::ACTIVE, $result['state']);
        $this->assertNotSame(LicenseLeaseVerifier::REVOKED, $result['state']);
        $this->assertSame($lease['payload']['lease_id'], app(LicenseLeaseStore::class)->readLease()['payload']['lease_id']);
    }

    public function test_invalid_cloud_lease_response_retains_the_last_valid_lease(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge();
        $lease = app(LicenseLeaseService::class)->issueAndPersist($activation, $configuration);
        $configuration->update(['cloud_endpoint' => 'https://cloud.test']);
        $tampered = $lease;
        $tampered['signature'] = 'invalid-tampered-signature';
        Http::fake(['https://cloud.test/*' => Http::response(['data' => ['lease' => $tampered]])]);

        $result = app(LicenseLeaseService::class)->refresh($configuration->fresh());

        $this->assertSame('INVALID_RESPONSE', $result['status']);
        $this->assertSame($lease['payload']['lease_id'], app(LicenseLeaseStore::class)->readLease()['payload']['lease_id']);
    }

    public function test_payroll_entitlement_is_enforced_by_the_backend_and_permissions_remain_independent(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge(['payroll' => false]);
        app(LicenseLeaseService::class)->issueAndPersist($activation, $configuration);
        $user = $this->createUser(['name' => 'License Manager']);
        Employee::create([
            'user_id' => $user->getKey(),
            'branch_id' => $configuration->branch_id,
            'employee_number' => 'LIC-001',
            'name' => 'License Manager',
            'is_active' => true,
            'status' => 'active',
        ]);
        $this->actingAsUserWithPermissions(['view-payroll'], $user);

        $response = $this->withHeaders($this->edgeHeaders($configuration))
            ->getJson('/api/v1/payroll');

        $response->assertForbidden()
            ->assertJsonPath('data.error_code', 'ENTITLEMENT_REQUIRED')
            ->assertJsonPath('data.entitlement', 'payroll');
    }

    public function test_entitlement_normalization_preserves_data_when_a_module_is_disabled(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge(['inventory' => false]);
        $leaseService = app(LicenseLeaseService::class);
        $leaseService->issueAndPersist($activation, $configuration);

        $this->assertFalse(app(EntitlementService::class)->normalize(
            app(LicenseLeaseStore::class)->readLease()['payload']['entitlements'],
        )['inventory']);
        $this->assertDatabaseHas('licenses', ['id' => $activation->license_id]);
    }

    public function test_manager_can_update_entitlements_and_status_for_their_organization(): void
    {
        [$configuration, $activation] = $this->createLicensedEdge();
        $user = $this->createUser(['name' => 'License Owner']);
        Employee::create([
            'user_id' => $user->getKey(),
            'branch_id' => $configuration->branch_id,
            'employee_number' => 'LIC-002',
            'name' => 'License Owner',
            'is_active' => true,
            'status' => 'active',
        ]);
        $this->actingAsUserWithPermissions(['settings-pos', 'manage-commercial-license'], $user);
        $headers = ['X-Branch-Id' => $configuration->branch_id];

        $this->withHeaders($headers)
            ->putJson('/api/v1/commercial/licenses/'.$activation->license_id.'/entitlements', [
                'entitlements' => ['pos' => true, 'payroll' => false],
            ])
            ->assertOk()
            ->assertJsonPath('data.features.payroll', false);

        $this->withHeaders($headers)
            ->postJson('/api/v1/commercial/licenses/'.$activation->license_id.'/status', ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('licenses', ['id' => $activation->license_id, 'status' => 'suspended']);
    }

    /**
     * @param  array<string, bool>  $features
     * @return array{0: EdgeConfiguration, 1: DeviceActivation}
     */
    private function createLicensedEdge(array $features = [], int $gracePeriodDays = 14): array
    {
        [$organization, $branch] = $this->createOrganizationAndBranch();
        $license = app(LicenseService::class)->issue($organization, [
            'features' => $features,
            'grace_period_days' => $gracePeriodDays,
        ])['license'];
        $configuration = app(EdgeConfigurationService::class)->initialize([
            'installation_id' => 'license-edge-'.Str::lower(Str::random(8)),
            'installation_fingerprint' => 'license-host-a',
            'organization_id' => $organization->getKey(),
            'branch_id' => $branch->getKey(),
        ]);
        $activation = DeviceActivation::create([
            'license_id' => $license->getKey(),
            'device_id' => $configuration->device_id,
            'installation_id' => $configuration->installation_id,
            'activation_token_hash' => Hash::make('activation-token'),
            'activation_credential' => 'activation-token',
            'status' => 'active',
            'activated_at' => now(),
            'last_checkin_at' => now(),
            'offline_grace_expires_at' => now()->addDays($gracePeriodDays),
            'metadata' => [],
        ]);
        $credential = EdgeDeviceCredential::create([
            'edge_configuration_id' => $configuration->getKey(),
            'device_id' => $configuration->device_id,
            'branch_id' => $configuration->branch_id,
            'device_type' => 'edge',
            'credential_hash' => Hash::make('edge-credential'),
            'status' => 'active',
            'rotated_at' => now(),
            'metadata' => [],
        ]);
        $this->deviceCredential = $credential->getKey().'.edge-credential';
        $credential->update(['credential_hash' => Hash::make($this->deviceCredential)]);

        return [$configuration->fresh(['device']), $activation->fresh(['license', 'device'])];
    }

    /**
     * @return array{0: Organization, 1: Branch}
     */
    private function createOrganizationAndBranch(): array
    {
        $organization = Organization::create([
            'name' => ['ar' => 'اختبار', 'en' => 'Test'],
            'slug' => 'license-'.Str::lower(Str::random(8)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'is_active' => true,
        ]);
        $branch = Branch::create([
            'organization_id' => $organization->getKey(),
            'name' => ['ar' => 'الفرع', 'en' => 'Branch'],
            'code' => 'BR-'.Str::upper(Str::random(4)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'is_active' => true,
        ]);

        return [$organization, $branch];
    }

    /**
     * @return array<string, string>
     */
    private function edgeHeaders(EdgeConfiguration $configuration): array
    {
        return [
            'X-Edge-Installation-Id' => $configuration->installation_id,
            'X-Edge-Installation-Fingerprint' => 'license-host-a',
            'X-Branch-Id' => $configuration->branch_id,
            'X-Device-Id' => $configuration->device_id,
            'X-Device-Credential' => $this->deviceCredential,
        ];
    }
}
