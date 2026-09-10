<?php

namespace Tests\Feature\Global;

use App\Models\Country;
use App\Trait\Global\HasDynamicScopes;
use App\Trait\Global\QuotesSortLiterals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalTraitsTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | HasDynamicScopes
    |--------------------------------------------------------------------------
    */
    private function scopeRunner(): object
    {
        return new class
        {
            use HasDynamicScopes;

            /** @var array<string, string> */
            public array $scopeMap = [];
        };
    }

    public function test_applies_a_requested_scope(): void
    {
        $active = Country::factory()->create(['is_active' => true]);
        Country::factory()->create(['is_active' => false]);

        request()->replace(['scopes' => ['active']]);

        $query = Country::query();
        $this->scopeRunner()->applyScopesFilter($query);

        $this->assertEquals([$active->id], $query->pluck('id')->all());
    }

    public function test_ignores_a_scope_the_model_does_not_declare(): void
    {
        Country::factory()->create();
        Country::factory()->create();

        request()->replace(['scopes' => ['noSuchScope']]);

        $query = Country::query();
        $this->scopeRunner()->applyScopesFilter($query);

        $this->assertSame(2, $query->count());
    }

    public function test_leaves_the_query_untouched_without_requested_scopes(): void
    {
        Country::factory()->create(['is_active' => false]);

        request()->replace([]);

        $query = Country::query();
        $this->scopeRunner()->applyScopesFilter($query);

        $this->assertSame(1, $query->count());
    }

    /*
    |--------------------------------------------------------------------------
    | QuotesSortLiterals
    |--------------------------------------------------------------------------
    */
    public function test_quotes_a_morph_class_literal_for_the_models_own_connection(): void
    {
        $model = new class(Country::class) extends Country
        {
            use QuotesSortLiterals;

            public function __construct(public string $literal = '')
            {
                parent::__construct();
            }

            public function quoted(): string
            {
                return $this->quoteSortLiteral($this->literal);
            }
        };

        $quoted = $model->quoted();

        // Whatever the driver's escaping rules, the result is a single quoted
        // SQL literal that still contains the original class name.
        $this->assertStringStartsWith("'", $quoted);
        $this->assertStringEndsWith("'", $quoted);
        $this->assertStringContainsString('Country', $quoted);
    }
}
