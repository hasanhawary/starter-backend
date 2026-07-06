<?php

namespace Tests\Feature\Help;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_models_rejects_invalid_lookup_contract(): void
    {
        $this->actingAsUserWithPermissions();

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
