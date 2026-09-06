<?php

namespace Tests\Feature\Commercial;

use App\Models\DeviceActivation;
use App\Services\Commercial\LicenseService;
use App\Services\Edge\EdgeConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LicenseRecoveryAndTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_replacement_generates_valid_recovery_token_and_event(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 1]);
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'orig-pc-1']);

        $res = $this->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])->assertOk();
        $activationId = $res->json('data.activation.id');
        $activation = DeviceActivation::findOrFail($activationId);

        $authResult = app(LicenseService::class)->authorizeReplacement(
            $activation,
            'Motherboard fried after power surge'
        );

        $this->assertNotEmpty($authResult['token']);
        $this->assertStringStartsWith('ROT-', $authResult['token']);
        $this->assertTrue($authResult['expires_at']->isFuture());

        $this->assertDatabaseHas('commercial_license_events', [
            'license_id' => $issued['license']->getKey(),
            'device_activation_id' => $activationId,
            'event' => 'REPLACEMENT_AUTHORIZED',
        ]);
    }

    public function test_successful_installation_recovery_replaces_old_device_without_consuming_extra_slot(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 1]);

        // 1. Initial activation on old PC
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'old-machine-inst']);
        $first = $this->withHeaders(['X-Edge-Installation-Id' => 'old-machine-inst'])
            ->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])
            ->assertOk();
        $oldActivationId = $first->json('data.activation.id');
        $oldActivation = DeviceActivation::findOrFail($oldActivationId);

        // 2. Authorize replacement
        $authResult = app(LicenseService::class)->authorizeReplacement(
            $oldActivation,
            'Old hardware replaced with new terminal'
        );
        $recoveryToken = $authResult['token'];

        // 3. New machine recovers license using recovery token
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'new-replacement-inst']);
        $recoveryResponse = $this->withHeaders(['X-Edge-Installation-Id' => 'new-replacement-inst'])
            ->postJson('/api/v1/activation/recover', [
                'license_key' => $issued['license_key'],
                'recovery_token' => $recoveryToken,
            ]);

        $this->assertSuccessEnvelope($recoveryResponse);
        $newActivationId = $recoveryResponse->json('data.activation.id');

        // 4. Invariants verification
        $this->assertNotSame($oldActivationId, $newActivationId);

        $oldFresh = DeviceActivation::findOrFail($oldActivationId);
        $this->assertSame('replaced', $oldFresh->status);
        $this->assertNotNull($oldFresh->revoked_at);
        $this->assertSame('new-replacement-inst', $oldFresh->metadata['replacement']['replaced_by_installation_id']);

        $newFresh = DeviceActivation::findOrFail($newActivationId);
        $this->assertSame('active', $newFresh->status);
        $this->assertSame('new-replacement-inst', $newFresh->installation_id);

        // Max devices was 1, active activations count must be exactly 1
        $this->assertSame(1, $issued['license']->activations()->where('status', 'active')->count());
        $this->assertSame(2, $issued['license']->activations()->count()); // History preserved

        // Event recorded
        $this->assertDatabaseHas('commercial_license_events', [
            'license_id' => $issued['license']->getKey(),
            'device_activation_id' => $newActivationId,
            'event' => 'INSTALLATION_REPLACED',
        ]);
    }

    public function test_reusing_recovery_token_is_rejected_due_to_replay_protection(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 1]);

        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-1']);
        $first = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-1'])
            ->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])
            ->assertOk();
        $activation = DeviceActivation::findOrFail($first->json('data.activation.id'));

        $authResult = app(LicenseService::class)->authorizeReplacement($activation, 'Transfer to backup');
        $token = $authResult['token'];

        // First transfer succeeds
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-2']);
        $this->withHeaders(['X-Edge-Installation-Id' => 'pc-2'])
            ->postJson('/api/v1/activation/recover', [
                'license_key' => $issued['license_key'],
                'recovery_token' => $token,
            ])
            ->assertOk();

        // Second transfer with same token fails
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-3']);
        $response = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-3'])
            ->postJson('/api/v1/activation/recover', [
                'license_key' => $issued['license_key'],
                'recovery_token' => $token,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.recovery_token.0', __('api.invalid_recovery_token'));
    }

    public function test_expired_recovery_token_is_rejected(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 1]);

        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-orig']);
        $first = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-orig'])
            ->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])
            ->assertOk();
        $activation = DeviceActivation::findOrFail($first->json('data.activation.id'));

        $authResult = app(LicenseService::class)->authorizeReplacement($activation, 'Transfer');
        $token = $authResult['token'];

        // Travel 65 minutes into future (token expired)
        Carbon::setTestNow(now()->addMinutes(65));

        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-expired']);
        $response = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-expired'])
            ->postJson('/api/v1/activation/recover', [
                'license_key' => $issued['license_key'],
                'recovery_token' => $token,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.recovery_token.0', __('api.recovery_token_expired'));

        Carbon::setTestNow(null);
    }

    public function test_cross_tenant_token_is_rejected(): void
    {
        $orgA = $this->createOrganization('Org A');
        $licenseA = app(LicenseService::class)->issue($orgA);

        $orgB = $this->createOrganization('Org B');
        $licenseB = app(LicenseService::class)->issue($orgB);

        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-a']);
        $first = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-a'])
            ->postJson('/api/v1/activation', ['license_key' => $licenseA['license_key']])
            ->assertOk();
        $activationA = DeviceActivation::findOrFail($first->json('data.activation.id'));

        $authResult = app(LicenseService::class)->authorizeReplacement($activationA, 'Transfer');

        // Try to use token from license A on license B
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-attacker']);
        $response = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-attacker'])
            ->postJson('/api/v1/activation/recover', [
                'license_key' => $licenseB['license_key'],
                'recovery_token' => $authResult['token'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.recovery_token.0', __('api.invalid_recovery_token'));
    }

    public function test_suspended_or_revoked_license_rejects_recovery(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization);

        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-active']);
        $first = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-active'])
            ->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])
            ->assertOk();
        $activation = DeviceActivation::findOrFail($first->json('data.activation.id'));

        $authResult = app(LicenseService::class)->authorizeReplacement($activation, 'Transfer');

        // Suspend license
        $issued['license']->update(['status' => 'suspended']);

        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'pc-new']);
        $response = $this->withHeaders(['X-Edge-Installation-Id' => 'pc-new'])
            ->postJson('/api/v1/activation/recover', [
                'license_key' => $issued['license_key'],
                'recovery_token' => $authResult['token'],
            ]);

        $response->assertStatus(403);
    }

    public function test_commercial_admin_api_endpoints_for_replacement_and_deactivation(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 2]);
        $branch = $organization->branches()->firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => ['ar' => 'الرئيسي', 'en' => 'Main'], 'currency' => 'EGP', 'timezone' => 'Africa/Cairo', 'is_active' => true]
        );
        $user = $this->createUser();
        $user->employee()->create([
            'branch_id' => $branch->getKey(),
            'employee_number' => 'EMP-001',
            'is_active' => true,
        ]);
        $this->givePermissions($user, ['settings-pos', 'authorize-license-recovery']);
        Sanctum::actingAs($user);

        $actRes = $this->withHeaders(['X-Edge-Installation-Id' => 'admin-test-pc'])
            ->postJson('/api/v1/activation', [
                'license_key' => $issued['license_key'],
                'branch_id' => $branch->getKey(),
            ])
            ->assertOk();
        $activationId = $actRes->json('data.activation.id');

        // 1. Authorize replacement via API
        $authRes = $this->actingAs($user)
            ->withHeaders([
                'X-Edge-Installation-Id' => 'admin-test-pc',
                'X-Pos-Branch-Id' => $branch->getKey(),
            ])
            ->postJson("/api/v1/commercial/activations/{$activationId}/authorize-replacement", [
                'reason' => 'Damaged hard drive',
            ]);

        $this->assertSuccessEnvelope($authRes);
        $this->assertNotEmpty($authRes->json('data.recovery_token'));

        // 2. List activations via API
        $listRes = $this->actingAs($user)
            ->withHeaders([
                'X-Edge-Installation-Id' => 'admin-test-pc',
                'X-Pos-Branch-Id' => $branch->getKey(),
            ])
            ->getJson('/api/v1/commercial/activations');

        $this->assertSuccessEnvelope($listRes);
        $this->assertCount(1, $listRes->json('data'));

        // 3. Deactivate via API
        $deactRes = $this->actingAs($user)
            ->withHeaders([
                'X-Edge-Installation-Id' => 'admin-test-pc',
                'X-Pos-Branch-Id' => $branch->getKey(),
            ])
            ->postJson("/api/v1/commercial/activations/{$activationId}/deactivate", [
                'reason' => 'Temporarily decommissioning terminal',
            ]);

        $this->assertSuccessEnvelope($deactRes);
        $this->assertSame('deactivated', DeviceActivation::findOrFail($activationId)->status);
    }
}
