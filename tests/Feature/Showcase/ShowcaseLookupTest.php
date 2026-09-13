<?php

namespace Tests\Feature\Showcase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Modules\Showcase\app\Models\ShowcaseTag;
use Tests\TestCase;

/**
 * The module is reachable through the shared lookup and discovery endpoints the
 * frontend builds its filter forms from, without the module registering
 * endpoints of its own.
 */
class ShowcaseLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'en');
    }

    public function test_help_enums_resolves_the_module_enums_with_labels_colors_and_icons(): void
    {
        $this->actingAsUserWithPermissions();

        $query = http_build_query([
            'enums' => [
                ['name' => 'showcase_status', 'module' => 'showcase', 'method' => 'getList'],
                ['name' => 'showcase_priority', 'module' => 'showcase', 'method' => 'getList'],
            ],
        ]);

        $response = $this->getJson("/api/help-enums?{$query}");

        $this->assertSuccessEnvelope($response);

        $status = collect($response->json('data.showcase_status'));

        $this->assertSame(
            ['draft', 'in_review', 'published', 'archived'],
            $status->pluck('value')->all(),
        );
        $this->assertSame('In Review', $status->firstWhere('value', 'in_review')['label']);
        $this->assertSame('#F59E0B', $status->firstWhere('value', 'in_review')['color']);
        $this->assertSame('mdi-clock-outline', $status->firstWhere('value', 'in_review')['icon']);

        $this->assertSame(
            ['low', 'medium', 'high', 'critical'],
            collect($response->json('data.showcase_priority'))->pluck('value')->all(),
        );
    }

    public function test_help_models_resolves_the_module_models_and_honours_their_scopes(): void
    {
        $this->actingAsUserWithPermissions();

        $active = ShowcaseCategory::factory()->create(['name' => ['en' => 'Active One', 'ar' => 'نشط']]);
        ShowcaseCategory::factory()->inactive()->create();
        ShowcaseTag::factory()->create(['name' => ['en' => 'Featured', 'ar' => 'مميز']]);

        $query = http_build_query([
            'tables' => [
                ['name' => 'showcase_categories', 'module' => 'showcase', 'scopes' => ['active']],
                ['name' => 'showcase_tags', 'module' => 'showcase'],
            ],
        ]);

        $response = $this->getJson("/api/help-models?{$query}");

        $this->assertSuccessEnvelope($response);
        $this->assertSame(
            [['id' => $active->id, 'name' => 'Active One']],
            $response->json('data.showcase_categories'),
        );
        $this->assertSame('Featured', $response->json('data.showcase_tags.0.name'));
    }

    public function test_the_discovery_config_exposes_the_showcase_filter_form_with_translated_labels(): void
    {
        $this->actingAsUserWithPermissions();

        $query = http_build_query([
            'configs' => [['name' => 'discovery', 'keys' => ['filters', 'sorting']]],
        ]);

        $response = $this->getJson("/api/help-configs?{$query}");

        $this->assertSuccessEnvelope($response);

        $filters = $response->json('data.discovery.filters.showcases');
        $sorting = $response->json('data.discovery.sorting.showcases');

        $this->assertNotEmpty($filters);
        $this->assertContains('reference', $sorting);

        // Labels are stored as translation keys in config and resolved per request.
        $labels = collect($filters['advanced']['options'])->pluck('label');
        $this->assertContains('Status', $labels);
        $this->assertContains('Priority', $labels);
        $this->assertFalse($labels->contains(fn (string $label) => str_starts_with($label, 'api.')));
    }
}
