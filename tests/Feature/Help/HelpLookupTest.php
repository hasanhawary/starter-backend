<?php

namespace Tests\Feature\Help;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_models_requires_read_help_permission(): void
    {
        $this->actingAsUserWithPermissions();

        $this->getJson('/api/help-models')->assertForbidden();

        $this->actingAsUserWithPermissions(['read-help']);

        $this->assertSuccessEnvelope($this->getJson('/api/help-models'));
    }

    public function test_help_models_rejects_invalid_lookup_contract(): void
    {
        $this->actingAsUserWithPermissions(['read-help']);

        $query = http_build_query([
            'tables' => [
                [
                    'name' => 'users',
                    'extra' => 'password',
                ],
            ],
        ]);

        $this->getJson("/api/help-models?{$query}")
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['tables.0.extra']]);
    }
}
