<?php

namespace Tests\Feature\Commercial;

use App\Models\Branch;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\Organization;
use App\Services\Commercial\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_license_activates_a_branch_device_and_supports_status_heartbeat(): void
    {
        [$organization, $branch] = $this->createOrganizationAndBranch();
        $issued = app(LicenseService::class)->issue($organization, ['plan' => 'standard', 'max_devices' => 2]);

        $activation = $this->postJson('/api/v1/activation', [
            'license_key' => $issued['license_key'],
            'branch_id' => $branch->getKey(),
            'installation_id' => 'windows-installation-001',
            'device_name' => 'Front Counter',
            'platform' => 'windows',
            'app_version' => '1.0.0',
        ]);

        $this->assertSuccessEnvelope($activation);
        $activation->assertJsonPath('data.license.plan', 'standard')
            ->assertJsonPath('data.device.branch_id', $branch->getKey())
            ->assertJsonPath('data.activation.installation_id', 'windows-installation-001');
        $token = $activation->json('data.activation_token');

        $this->assertNotEmpty($token);
        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseCount('device_activations', 1);
        $this->assertDatabaseMissing('licenses', ['key_hash' => $issued['license_key']]);

        $this->withHeaders(['X-Activation-Token' => $token])
            ->getJson('/api/v1/activation/status')
            ->assertJsonPath('data.can_work_online', true)
            ->assertJsonPath('data.activation.status', 'active');

        $this->travelTo(now()->addMinute());
        $heartbeat = $this->withHeaders(['X-Activation-Token' => $token])
            ->postJson('/api/v1/activation/heartbeat');

        $heartbeat->assertOk();
        $this->assertNotNull($heartbeat->json('data.activation.last_checkin_at'));
        $this->travelBack();
    }

    public function test_invalid_license_key_cannot_activate_a_device(): void
    {
        [, $branch] = $this->createOrganizationAndBranch();

        $this->postJson('/api/v1/activation', [
            'license_key' => 'POS-INVALID-KEY-0000',
            'branch_id' => $branch->getKey(),
            'installation_id' => 'invalid-installation',
            'device_name' => 'Unknown Device',
            'platform' => 'windows',
        ])->assertUnprocessable()->assertJsonPath('errors.license_key.0', 'مفتاح الترخيص غير صالح');

        $this->assertDatabaseCount('device_activations', 0);
    }

    public function test_a_license_cannot_exceed_its_device_limit(): void
    {
        [$organization, $branch] = $this->createOrganizationAndBranch();
        $issued = app(LicenseService::class)->issue($organization, ['max_devices' => 1]);

        $this->postJson('/api/v1/activation', [
            'license_key' => $issued['license_key'],
            'branch_id' => $branch->getKey(),
            'installation_id' => 'first-installation',
            'device_name' => 'First Device',
            'platform' => 'windows',
        ])->assertOk();

        $this->postJson('/api/v1/activation', [
            'license_key' => $issued['license_key'],
            'branch_id' => $branch->getKey(),
            'installation_id' => 'second-installation',
            'device_name' => 'Second Device',
            'platform' => 'windows',
        ])->assertStatus(409);

        $this->assertDatabaseCount('device_activations', 1);
    }

    public function test_an_invalid_activation_token_cannot_read_runtime_status(): void
    {
        $this->withHeaders(['X-Activation-Token' => 'not-a-real-token'])
            ->getJson('/api/v1/activation/status')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'رمز التفعيل غير صالح أو ملغى');
    }

    public function test_reactivating_an_installation_rotates_its_token_without_creating_a_second_device(): void
    {
        [$organization, $branch] = $this->createOrganizationAndBranch();
        $issued = app(LicenseService::class)->issue($organization);
        $payload = [
            'license_key' => $issued['license_key'],
            'branch_id' => $branch->getKey(),
            'installation_id' => 'repeat-installation',
            'device_name' => 'Front Counter',
            'platform' => 'windows',
        ];

        $first = $this->postJson('/api/v1/activation', $payload)->assertOk();
        $second = $this->postJson('/api/v1/activation', $payload)->assertOk();

        $this->assertNotSame($first->json('data.activation_token'), $second->json('data.activation_token'));
        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseCount('device_activations', 1);
        $this->assertSame($first->json('data.device.id'), $second->json('data.device.id'));
        $this->assertInstanceOf(DeviceActivation::class, DeviceActivation::query()->first());
        $this->assertInstanceOf(Device::class, Device::query()->first());
    }

    /**
     * @return array{0: Organization, 1: Branch}
     */
    private function createOrganizationAndBranch(): array
    {
        $organization = Organization::create([
            'name' => ['ar' => 'اختبار', 'en' => 'Test'],
            'slug' => 'commercial-'.Str::lower(Str::random(8)),
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
}
