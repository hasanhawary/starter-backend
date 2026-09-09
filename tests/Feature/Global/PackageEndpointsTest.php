<?php

namespace Tests\Feature\Global;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Reverb\Pulse\Recorders\ReverbConnections;
use Laravel\Reverb\Pulse\Recorders\ReverbMessages;
use Tests\TestCase;

/**
 * Smoke tests for the endpoints each installed package owns, so an upgrade that
 * moves a route or renames a config key fails here rather than in production.
 */
class PackageEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | report-builder
    |--------------------------------------------------------------------------
    */
    public function test_the_report_endpoint_renders_the_user_page(): void
    {
        // UserReport groups by DATE_FORMAT(), which only MySQL provides.
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('The user report is MySQL-only (DATE_FORMAT).');
        }

        $this->actingAsUserWithPermissions();

        $response = $this->getJson('/api/report?page=user');

        $this->assertSuccessEnvelope($response);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_the_package_does_not_also_register_its_own_report_route(): void
    {
        $this->assertFalse(config('report.routes.enabled'));

        $reportRoutes = collect(app('router')->getRoutes())
            ->filter(fn ($route) => $route->uri() === 'api/report');

        $this->assertCount(1, $reportRoutes);
    }

    /*
    |--------------------------------------------------------------------------
    | export-builder
    |--------------------------------------------------------------------------
    */
    public function test_the_direct_export_endpoint_streams_a_user_export(): void
    {
        $this->actingAsUserWithPermissions();
        $this->createUser(['name' => 'Exported User']);

        // The spreadsheet writer streams through its own output buffers, so the
        // request is run inside one of ours and the body is discarded.
        $level = ob_get_level();
        ob_start();

        try {
            $response = $this->get('/api/export-direct?page=user&type=xlsx');
            $response->streamedContent();
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('spreadsheet', (string) $response->headers->get('content-type'));
    }

    public function test_the_export_module_routes_are_registered(): void
    {
        $this->assertTrue(config('export.module.enabled'));
        $this->assertTrue(config('export.module.routes.enabled'));

        $uris = collect(app('router')->getRoutes())->map->uri();

        foreach (['api/export', 'api/export-direct', 'api/export-log'] as $uri) {
            $this->assertContains($uri, $uris);
        }
    }

    public function test_export_column_headings_resolve_from_the_export_lang_file(): void
    {
        $this->assertSame('export', config('export.trans_file'));
        $this->assertSame('Email', trans('export.email', [], 'en'));
        $this->assertSame('البريد الإلكتروني', trans('export.email', [], 'ar'));
    }

    public function test_the_export_namespace_points_at_the_directory_holding_the_exports(): void
    {
        $namespace = config('export.namespace');

        $this->assertSame('App\\Tools\\Export', $namespace);
        $this->assertTrue(class_exists($namespace.'\\UserExport'));
    }

    /*
    |--------------------------------------------------------------------------
    | lookup-manager
    |--------------------------------------------------------------------------
    */
    public function test_the_help_models_endpoint_answers(): void
    {
        $this->actingAsUserWithPermissions();
        $this->createUser(['name' => 'Lookup Target']);

        $query = http_build_query(['tables' => [['name' => 'users']]]);

        $this->assertSuccessEnvelope($this->getJson("/api/help-models?{$query}"));
    }

    public function test_the_help_enums_endpoint_answers(): void
    {
        $this->actingAsUserWithPermissions();

        $query = http_build_query(['enums' => [['name' => 'user.user_gender']]]);
        $response = $this->getJson("/api/help-enums?{$query}");

        $this->assertSuccessEnvelope($response);
    }

    public function test_a_lookup_method_outside_the_whitelist_is_refused(): void
    {
        $allowed = config('lookup.enums.allowed_methods');

        $this->assertContains('getList', $allowed);
        $this->assertNotContains('cases', $allowed);
    }

    /*
    |--------------------------------------------------------------------------
    | media-manager
    |--------------------------------------------------------------------------
    */
    public function test_the_chunk_upload_endpoint_is_registered(): void
    {
        $this->actingAsUserWithPermissions();

        // No payload: the endpoint must answer with validation errors, not 404.
        $this->postJson('/api/chunk-file', [])->assertUnprocessable();
    }

    /*
    |--------------------------------------------------------------------------
    | pulse / log-viewer
    |--------------------------------------------------------------------------
    */
    public function test_pulse_records_reverb_traffic(): void
    {
        $recorders = array_keys(config('pulse.recorders'));

        $this->assertContains(ReverbConnections::class, $recorders);
        $this->assertContains(ReverbMessages::class, $recorders);
    }

    public function test_the_log_viewer_is_mounted_under_the_api_prefix(): void
    {
        $this->assertSame('api/log-viewer', config('log-viewer.route_path'));

        $mounted = collect(app('router')->getRoutes())
            ->contains(fn ($route) => str_starts_with($route->uri(), 'api/log-viewer'));

        $this->assertTrue($mounted);
    }
}
