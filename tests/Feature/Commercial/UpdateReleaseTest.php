<?php

namespace Tests\Feature\Commercial;

use App\Enum\Commercial\RollbackStrategyEnum;
use App\Enum\Commercial\UpdateStateEnum;
use App\Models\Branch;
use App\Models\EdgeConfiguration;
use App\Models\Organization;
use App\Models\Release;
use App\Services\Commercial\ReleaseCompatibilityService;
use App\Services\Commercial\ReleaseManifestService;
use App\Services\Commercial\ReleaseService;
use App\Services\Edge\EdgeConfigurationService;
use App\Services\Edge\MigrationOrchestrator;
use App\Services\Edge\OperationalWindowService;
use App\Services\Edge\UpdateBackupService;
use App\Services\Edge\UpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class UpdateReleaseTest extends TestCase
{
    use RefreshDatabase;

    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();

        $keyPair = sodium_crypto_sign_keypair();
        $this->rootPath = storage_path('framework/testing/update-'.Str::uuid());
        config([
            'edge.mode' => 'development',
            'edge.paths.backups' => $this->rootPath.'/backups',
            'edge.paths.updates' => $this->rootPath.'/updates',
            'edge.paths.releases' => $this->rootPath.'/releases',
            'commercial.releases.signing_key_id' => 'release-test-key',
            'commercial.releases.signing_private_key' => 'base64:'.base64_encode(sodium_crypto_sign_secretkey($keyPair)),
            'commercial.releases.trusted_public_keys' => [
                'release-test-key' => 'base64:'.base64_encode(sodium_crypto_sign_publickey($keyPair)),
            ],
            'commercial.releases.minimum_free_space_bytes' => 1,
            'commercial.releases.backup_retention' => 3,
        ]);
        Http::fake(['http://127.0.0.1:9105/health' => Http::response(['status' => 'ok'])]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->rootPath);

        parent::tearDown();
    }

    public function test_release_manifest_is_canonical_signed_and_tamper_evident(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, ['product_version' => '1.1.0']);
        $manifest = app(ReleaseService::class)->clientManifest($release);
        $manifestService = app(ReleaseManifestService::class);

        $this->assertTrue($manifestService->verify($manifest)['valid']);
        $manifest['payload']['package']['sha256'] = str_repeat('0', 64);
        $this->assertSame('INVALID_SIGNATURE', $manifestService->verify($manifest)['reason']);

        $unknownKey = app(ReleaseService::class)->clientManifest($release);
        $unknownKey['kid'] = 'unknown-key';
        $this->assertSame('UNKNOWN_SIGNING_KEY', $manifestService->verify($unknownKey)['reason']);
        $this->assertSame('STABLE', app(ReleaseCompatibilityService::class)->channelFor($configuration));
    }

    public function test_release_publishing_requires_the_internal_publish_permission(): void
    {
        $this->actingAsUserWithPermissions([]);
        $this->getJson('/api/v1/commercial/releases')->assertForbidden();

        $this->actingAsUserWithPermissions(['publish-release']);
        $this->getJson('/api/v1/commercial/releases')->assertOk();
    }

    public function test_release_lifecycle_publishes_pauses_and_revokes_without_exposing_private_material(): void
    {
        [, $package] = $this->createEdgeAndPackage();
        $release = app(ReleaseService::class)->create($this->releaseData($package), $this->createUser());

        $this->assertSame('DRAFT', $release->status);
        $release = app(ReleaseService::class)->publish($release, $this->createUser());
        $this->assertSame('PUBLISHED', $release->status);
        $this->assertNotEmpty($release->signature);
        $this->assertStringNotContainsString('PRIVATE', json_encode(app(ReleaseService::class)->clientManifest($release), JSON_THROW_ON_ERROR));

        $this->assertSame('PAUSED', app(ReleaseService::class)->pause($release)->status);
        $this->assertSame('REVOKED', app(ReleaseService::class)->revoke($release)->status);
        $this->assertDatabaseHas('commercial_release_events', ['event' => 'release_published']);
        $this->assertDatabaseHas('commercial_release_events', ['event' => 'release_paused']);
        $this->assertDatabaseHas('commercial_release_events', ['event' => 'release_revoked']);
    }

    public function test_beta_channel_and_deterministic_rollout_are_isolated_from_stable(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $stable = $this->publishRelease($package, ['product_version' => '1.1.0', 'channel' => 'STABLE']);
        $beta = $this->publishRelease($package, [
            'release_id' => 'beta-release',
            'product_version' => '1.2.0-beta.1',
            'channel' => 'BETA',
            'rollout' => ['percentage' => 100],
        ]);

        $this->assertSame($stable->release_id, app(UpdateService::class)->check($configuration)['release']['payload']['release_id']);

        $configuration->device->update(['metadata' => ['update_channel' => 'BETA']]);
        $result = app(UpdateService::class)->check($configuration->fresh(['branch', 'device']));
        $this->assertSame($beta->release_id, $result['release']['payload']['release_id']);

        $beta->update(['rollout' => ['percentage' => 0]]);
        $this->assertSame('UP_TO_DATE', app(UpdateService::class)->check($configuration->fresh(['branch', 'device']))['status']);
    }

    public function test_version_compatibility_rejects_downgrades_and_requires_intermediate_updates(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, [
            'product_version' => '2.0.0',
            'minimum_current_version' => '1.5.0',
            'minimum_supported_version' => '1.5.0',
        ]);
        $compatibility = app(ReleaseCompatibilityService::class);

        $this->assertFalse($compatibility->isEligible($release, $configuration, '1.0.0'));
        $this->assertSame('INTERMEDIATE_UPDATE_REQUIRED', $compatibility->compatibilityFailure($release, '1.0.0'));
        $this->assertSame('DOWNGRADE_NOT_ALLOWED', $compatibility->compatibilityFailure($release, '2.1.0'));
    }

    public function test_successful_update_verifies_download_creates_backup_switches_runtime_and_commits(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $schemaVersion = app(MigrationOrchestrator::class)->currentSchemaVersion();
        $release = $this->publishRelease($package, [
            'product_version' => '1.1.0',
            'schema_version' => $schemaVersion,
            'compatibility' => ['migration_required' => false, 'rollback_strategy' => RollbackStrategyEnum::AppOnly->value],
        ]);
        $updateService = app(UpdateService::class);
        $attempt = $updateService->createAttempt($configuration, $release);
        $attempt = $updateService->download($attempt);
        $result = $updateService->install($attempt);

        $this->assertSame('COMPLETED', $result['status']);
        $configuration = $configuration->fresh();
        $this->assertSame('1.1.0', $configuration->metadata['current_version']);
        $this->assertSame('1.1.0', $configuration->metadata['last_known_good_version']);
        $this->assertSame(UpdateStateEnum::Completed->value, $attempt->fresh()->state);
        $this->assertDatabaseHas('update_backups', ['status' => 'READY', 'update_attempt_id' => $attempt->getKey()]);
        $this->assertFileExists($attempt->fresh()->staged_package);
    }

    public function test_checksum_failure_stops_before_installation(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, ['product_version' => '1.1.0']);
        File::put($package, 'tampered package');
        $attempt = app(UpdateService::class)->createAttempt($configuration, $release);

        try {
            app(UpdateService::class)->download($attempt);
            $this->fail('The tampered package should have been rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('PACKAGE_CHECKSUM_MISMATCH', $exception->getMessage());
        }

        $this->assertSame(UpdateStateEnum::Failed->value, $attempt->fresh()->state);
        $this->assertSame('1.0.0', $configuration->fresh()->metadata['current_version'] ?? '1.0.0');
        $this->assertDatabaseCount('update_backups', 0);
    }

    public function test_open_shift_and_payment_operation_defer_installation_without_blocking_download(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, ['product_version' => '1.1.0']);
        $attempt = app(UpdateService::class)->download(app(UpdateService::class)->createAttempt($configuration, $release));
        app(OperationalWindowService::class)->markStarted($configuration, 'payment_in_progress');

        $result = app(UpdateService::class)->install($attempt);

        $this->assertSame('DEFERRED', $result['status']);
        $this->assertSame(UpdateStateEnum::WaitingForSafeWindow->value, $attempt->fresh()->state);
        $this->assertSame('payment_in_progress', $attempt->fresh()->metadata['safe_window']['code']);
    }

    public function test_recovery_after_restart_never_blindly_reruns_an_interrupted_migration(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, ['product_version' => '1.1.0']);
        $attempt = app(UpdateService::class)->createAttempt($configuration, $release);
        $attempt->update(['state' => UpdateStateEnum::Migrating->value]);

        $recovered = app(UpdateService::class)->recover($configuration);

        $this->assertSame(UpdateStateEnum::RecoveryRequired->value, $recovered->state);
        $this->assertSame('MIGRATION_INTERRUPTED', $recovered->last_error_code);
    }

    public function test_post_update_business_writes_block_automatic_database_restore(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, [
            'product_version' => '1.1.0',
            'compatibility' => ['rollback_strategy' => RollbackStrategyEnum::AppAndDatabaseRestore->value],
        ]);
        $attempt = app(UpdateService::class)->createAttempt($configuration, $release);
        $attempt->update(['rollback_strategy' => RollbackStrategyEnum::AppAndDatabaseRestore->value]);
        app(UpdateService::class)->markBusinessWrite($attempt);

        $rolledBack = app(UpdateService::class)->rollback($attempt);

        $this->assertSame(UpdateStateEnum::RecoveryRequired->value, $rolledBack->state);
        $this->assertSame('POST_UPDATE_WRITES_REQUIRE_SUPPORT_RECOVERY', $rolledBack->last_error_code);
        $this->assertSame('1.0.0', $configuration->fresh()->metadata['current_version'] ?? '1.0.0');
    }

    public function test_backup_failure_aborts_before_runtime_installation(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, ['product_version' => '1.1.0']);
        $attempt = app(UpdateService::class)->download(app(UpdateService::class)->createAttempt($configuration, $release));
        $backup = Mockery::mock(UpdateBackupService::class);
        $backup->shouldReceive('create')->once()->andThrow(new \RuntimeException('BACKUP_VALIDATION_FAILED'));
        $this->app->instance(UpdateBackupService::class, $backup);

        try {
            app(UpdateService::class)->install($attempt);
            $this->fail('The backup failure should abort the update.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('BACKUP_VALIDATION_FAILED', $exception->getMessage());
        }

        $this->assertSame(UpdateStateEnum::Failed->value, $attempt->fresh()->state);
        $this->assertSame('1.0.0', $configuration->fresh()->metadata['current_version'] ?? '1.0.0');
    }

    public function test_migration_failure_rolls_back_application_only_when_safe(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $release = $this->publishRelease($package, [
            'product_version' => '1.1.0',
            'compatibility' => ['migration_required' => true, 'rollback_strategy' => RollbackStrategyEnum::AppOnly->value],
        ]);
        $attempt = app(UpdateService::class)->download(app(UpdateService::class)->createAttempt($configuration, $release));
        $migration = Mockery::mock(MigrationOrchestrator::class);
        $migration->shouldReceive('migrate')->once()->andThrow(new \RuntimeException('MIGRATION_FAILED'));
        $this->app->instance(MigrationOrchestrator::class, $migration);

        try {
            app(UpdateService::class)->install($attempt);
            $this->fail('The migration failure should be detected.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('MIGRATION_FAILED', $exception->getMessage());
        }

        $this->assertSame(UpdateStateEnum::RolledBack->value, $attempt->fresh()->state);
        $this->assertSame('1.0.0', $configuration->fresh()->metadata['current_version'] ?? '1.0.0');
    }

    public function test_health_failure_rolls_back_before_marking_new_version_known_good(): void
    {
        [$configuration, $package] = $this->createEdgeAndPackage();
        $configuration->update(['metadata' => [...($configuration->metadata ?? []), 'update_health_override' => 'FAIL']]);
        $release = $this->publishRelease($package, ['product_version' => '1.1.0']);
        $attempt = app(UpdateService::class)->download(app(UpdateService::class)->createAttempt($configuration, $release));

        try {
            app(UpdateService::class)->install($attempt);
            $this->fail('The health failure should trigger rollback.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('POST_UPDATE_HEALTH_FAILED', $exception->getMessage());
        }

        $this->assertSame(UpdateStateEnum::RolledBack->value, $attempt->fresh()->state);
        $this->assertNull($configuration->fresh()->metadata['last_known_good_version'] ?? null);
    }

    /**
     * @return array{0: EdgeConfiguration, 1: string}
     */
    private function createEdgeAndPackage(): array
    {
        $organization = Organization::create([
            'name' => ['ar' => 'اختبار', 'en' => 'Test'],
            'slug' => 'update-'.Str::lower(Str::random(8)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'is_active' => true,
        ]);
        $branch = Branch::create([
            'organization_id' => $organization->getKey(),
            'name' => ['ar' => 'الفرع', 'en' => 'Branch'],
            'code' => 'UP-'.Str::upper(Str::random(4)),
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'is_active' => true,
        ]);
        $configuration = app(EdgeConfigurationService::class)->initialize([
            'installation_id' => 'update-edge-'.Str::lower(Str::random(8)),
            'installation_fingerprint' => 'update-host-a',
            'organization_id' => $organization->getKey(),
            'branch_id' => $branch->getKey(),
        ]);
        $package = $this->rootPath.'/package-'.Str::uuid().'.bin';
        File::ensureDirectoryExists(dirname($package));
        File::put($package, 'signed Pilot POS package '.Str::uuid());

        return [$configuration->fresh(['branch', 'device']), $package];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publishRelease(string $package, array $overrides = []): Release
    {
        $release = app(ReleaseService::class)->create($this->releaseData($package, $overrides), $this->createUser());

        return app(ReleaseService::class)->publish($release, $this->createUser());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function releaseData(string $package, array $overrides = []): array
    {
        return [
            'release_id' => 'release-'.Str::lower(Str::random(8)),
            'product_version' => '1.1.0',
            'channel' => 'STABLE',
            'desktop_version' => '1.1.0',
            'edge_version' => '1.1.0',
            'print_agent_minimum_version' => '1.0.0',
            'print_agent_recommended_version' => '1.1.0',
            'schema_version' => app(MigrationOrchestrator::class)->currentSchemaVersion(),
            'package_reference' => $package,
            'release_notes' => ['ar' => 'تحديث آمن', 'en' => 'Secure update'],
            'mandatory' => false,
            'rollout' => ['percentage' => 100],
            ...$overrides,
        ];
    }
}
