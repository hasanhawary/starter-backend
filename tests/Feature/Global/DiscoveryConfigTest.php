<?php

namespace Tests\Feature\Global;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryConfigTest extends TestCase
{
    use RefreshDatabase;

    private function fetchFilters(): array
    {
        $query = http_build_query([
            'configs' => [
                ['name' => 'discovery', 'keys' => ['filters']],
            ],
        ]);

        // The application defaults to Arabic, so ask for English explicitly.
        $response = $this->withHeaders(['Accept-Language' => 'en'])->getJson("/api/help-configs?{$query}");
        $this->assertSuccessEnvelope($response);

        return $response->json('data.discovery.filters.users');
    }

    public function test_help_configs_returns_discovery_labels_translated_in_english(): void
    {
        $this->actingAsUserWithPermissions();

        $filters = $this->fetchFilters();

        $this->assertSame(__('api.filter.global.created_at'), $filters[0]['label']);
        $this->assertSame('Creation Date Range', $filters[0]['label']);
    }

    public function test_help_configs_returns_discovery_labels_translated_in_arabic(): void
    {
        $this->actingAsUserWithPermissions();

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/help-configs?'.http_build_query([
                'configs' => [['name' => 'discovery', 'keys' => ['filters']]],
            ]));

        $this->assertSuccessEnvelope($response);
        $this->assertSame(
            'النطاق الزمني لتاريخ الإنشاء',
            $response->json('data.discovery.filters.users.0.label')
        );
    }

    public function test_nested_option_labels_are_translated_too(): void
    {
        $this->actingAsUserWithPermissions();

        $filters = $this->fetchFilters();

        $this->assertSame('Filter Type', $filters['advanced']['label']);
        $this->assertSame('Status', $filters['advanced']['options'][0]['label']);
    }

    public function test_a_config_outside_the_whitelist_is_not_exposed(): void
    {
        $this->actingAsUserWithPermissions();

        $response = $this->getJson('/api/help-configs?'.http_build_query([
            'configs' => [['name' => 'database']],
        ]));

        $this->assertSuccessEnvelope($response);
        $this->assertSame([], $response->json('data.database'));
    }
}
