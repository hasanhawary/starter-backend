<?php

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_reads_require_read_country_permission(): void
    {
        $this->createCountry();

        $this->actingAsUserWithPermissions();

        $this->getJson('/api/countries')->assertForbidden();

        $this->actingAsUserWithPermissions(['read-country']);

        $this->assertSuccessEnvelope($this->getJson('/api/countries'));
    }

    public function test_report_rejects_user_without_report_home_permission(): void
    {
        $this->actingAsUserWithPermissions();

        $this->getJson('/api/report?types[]=cards')->assertForbidden();
    }
}
