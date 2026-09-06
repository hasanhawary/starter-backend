<?php

namespace Tests\Feature\Commercial;

use App\Services\Commercial\LicenseService;
use App\Services\Edge\EdgeConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PreAuthEdgeActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_activation_state_reports_activation_required_for_unactivated_edge(): void
    {
        $configuration = app(EdgeConfigurationService::class)->initialize([
            'installation_id' => 'fresh-edge-inst-'.Str::lower(Str::random(6)),
            'edge_name' => 'Main Edge Node',
        ]);

        $response = $this->getJson('/api/v1/activation/state');

        $this->assertSuccessEnvelope($response);
        $response->assertJsonPath('data.activation_required', true)
            ->assertJsonPath('data.installation_ready', true)
            ->assertJsonPath('data.installation_id', $configuration->installation_id)
            ->assertJsonMissingPath('data.license_key')
            ->assertJsonMissingPath('data.private_key');
    }

    public function test_fresh_packaged_installation_activates_with_only_license_key(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 2]);
        $configuration = app(EdgeConfigurationService::class)->initialize([
            'installation_id' => 'package-inst-'.Str::lower(Str::random(6)),
            'edge_name' => 'Cashier POS Terminal',
        ]);

        $response = $this->postJson('/api/v1/activation', [
            'license_key' => $issued['license_key'],
        ]);

        $this->assertSuccessEnvelope($response);
        $response->assertJsonPath('data.activation.status', 'active')
            ->assertJsonPath('data.activation.installation_id', $configuration->installation_id)
            ->assertJsonPath('data.device.name', 'Cashier POS Terminal');

        $this->assertNotEmpty($response->json('data.activation_token'));
        $this->assertDatabaseCount('device_activations', 1);
        $this->assertDatabaseCount('devices', 1);

        $state = $this->getJson('/api/v1/activation/state');
        $state->assertJsonPath('data.activation_required', false)
            ->assertJsonPath('data.usable', true);
    }

    public function test_invalid_license_key_returns_friendly_validation_error(): void
    {
        app(EdgeConfigurationService::class)->initialize([
            'installation_id' => 'test-inst-'.Str::lower(Str::random(6)),
        ]);

        $response = $this->postJson('/api/v1/activation', [
            'license_key' => 'POS-INVALID-FAKE-0000',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.license_key.0', __('api.invalid_license_key'));
    }

    public function test_inactive_or_expired_license_is_rejected(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, [
            'status' => 'suspended',
        ]);
        app(EdgeConfigurationService::class)->initialize([
            'installation_id' => 'test-inst-'.Str::lower(Str::random(6)),
        ]);

        $response = $this->postJson('/api/v1/activation', [
            'license_key' => $issued['license_key'],
        ]);

        $response->assertStatus(403);
    }

    public function test_device_limit_enforcement_returns_conflict(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 1]);

        // First device
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'node-1']);
        $this->withHeaders(['X-Edge-Installation-Id' => 'node-1'])
            ->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])
            ->assertOk();

        // Second device on different installation
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'node-2']);
        $response = $this->withHeaders(['X-Edge-Installation-Id' => 'node-2'])
            ->postJson('/api/v1/activation', ['license_key' => $issued['license_key']]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('device_activations', 1);
    }

    public function test_activation_retry_is_idempotent_and_reuses_same_slot(): void
    {
        $organization = $this->createOrganization();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 1]);
        app(EdgeConfigurationService::class)->initialize(['installation_id' => 'idempotent-node']);

        $first = $this->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])->assertOk();
        $second = $this->postJson('/api/v1/activation', ['license_key' => $issued['license_key']])->assertOk();

        $this->assertSame($first->json('data.activation.id'), $second->json('data.activation.id'));
        $this->assertDatabaseCount('device_activations', 1);
        $this->assertDatabaseCount('devices', 1);
    }
}
