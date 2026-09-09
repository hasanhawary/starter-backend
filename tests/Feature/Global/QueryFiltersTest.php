<?php

namespace Tests\Feature\Global;

use App\Filters\Global\OrderByFilter;
use App\Filters\Global\OrderColumnFilter;
use App\Filters\Global\SearchFilter;
use App\Models\Country;
use App\Models\User;
use App\Services\Global\QueryHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pipeline\Pipeline;
use Tests\TestCase;

class QueryFiltersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run one filter over a fresh query the way a controller pipeline does.
     */
    private function pipe(Builder $query, string $filter): Builder
    {
        return app(Pipeline::class)
            ->send($query)
            ->through([$filter])
            ->thenReturn();
    }

    /*
    |--------------------------------------------------------------------------
    | SearchFilter
    |--------------------------------------------------------------------------
    */
    public function test_search_filter_matches_the_name_case_insensitively(): void
    {
        $match = $this->createUser(['name' => 'Amina Khaled']);
        $this->createUser(['name' => 'Omar Saleh']);

        request()->merge(['search' => 'amina']);

        $results = $this->pipe(User::query(), SearchFilter::class)->pluck('id');

        $this->assertEquals([$match->id], $results->all());
    }

    public function test_search_filter_matches_an_id_by_prefix(): void
    {
        $first = $this->createUser(['name' => 'First']);
        $this->createUser(['name' => 'Second']);

        request()->merge(['search' => (string) $first->id]);

        $results = $this->pipe(User::query(), SearchFilter::class)->pluck('id');

        $this->assertContains($first->id, $results->all());
    }

    public function test_search_filter_leaves_the_query_untouched_without_a_term(): void
    {
        $this->createUser();
        $this->createUser();

        request()->replace([]);

        $this->assertSame(2, $this->pipe(User::query(), SearchFilter::class)->count());
    }

    public function test_apply_id_search_ignores_a_non_numeric_term(): void
    {
        $this->createUser();

        $query = User::query();
        QueryHelper::applyIdSearch($query, 'not-a-number');

        $this->assertSame(1, $query->count());
    }

    /*
    |--------------------------------------------------------------------------
    | OrderColumnFilter
    |--------------------------------------------------------------------------
    */
    public function test_order_column_filter_is_a_no_op_without_an_order_column(): void
    {
        $this->createUser();

        // `users` has no `order` column, so the filter must not add an order by.
        $query = $this->pipe(User::query(), OrderColumnFilter::class);

        $this->assertSame([], $query->getQuery()->orders ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | OrderByFilter
    |--------------------------------------------------------------------------
    */
    public function test_order_by_filter_sorts_by_a_plain_column(): void
    {
        $a = $this->createUser(['name' => 'Aaa']);
        $z = $this->createUser(['name' => 'Zzz']);

        request()->replace(['sort_column' => 'name', 'sort_direction' => 'asc']);

        $results = $this->pipe(User::query(), OrderByFilter::class)->pluck('id');

        $this->assertEquals([$a->id, $z->id], $results->all());
    }

    public function test_order_by_filter_falls_back_to_id_desc_for_an_unknown_column(): void
    {
        $first = $this->createUser();
        $second = $this->createUser();

        request()->replace(['sort_column' => 'no_such_column']);

        $results = $this->pipe(User::query(), OrderByFilter::class)->pluck('id');

        $this->assertEquals([$second->id, $first->id], $results->all());
    }

    public function test_order_by_filter_rejects_an_invalid_direction(): void
    {
        $first = $this->createUser();
        $second = $this->createUser();

        request()->replace(['sort_column' => 'id', 'sort_direction' => 'sideways']);

        $results = $this->pipe(User::query(), OrderByFilter::class)->pluck('id');

        $this->assertEquals([$second->id, $first->id], $results->all());
    }

    public function test_order_by_filter_sorts_by_a_related_column(): void
    {
        $zoe = $this->createUser(['name' => 'Zoe']);
        $adam = $this->createUser(['name' => 'Adam']);

        $byZoe = $this->createUser(['name' => 'Record A', 'created_by' => $zoe->id]);
        $byAdam = $this->createUser(['name' => 'Record B', 'created_by' => $adam->id]);

        request()->replace(['sort_column' => 'creator.name', 'sort_direction' => 'asc']);

        $results = $this->pipe(
            User::query()->whereIn('id', [$byZoe->id, $byAdam->id]),
            OrderByFilter::class
        )->pluck('id');

        $this->assertEquals([$byAdam->id, $byZoe->id], $results->all());
    }

    public function test_order_by_filter_resolves_a_bare_relation_name_to_its_name_column(): void
    {
        $zoe = $this->createUser(['name' => 'Zoe']);
        $adam = $this->createUser(['name' => 'Adam']);

        $byZoe = $this->createUser(['created_by' => $zoe->id]);
        $byAdam = $this->createUser(['created_by' => $adam->id]);

        request()->replace(['sort_column' => 'creator', 'sort_direction' => 'asc']);

        $results = $this->pipe(
            User::query()->whereIn('id', [$byZoe->id, $byAdam->id]),
            OrderByFilter::class
        )->pluck('id');

        $this->assertEquals([$byAdam->id, $byZoe->id], $results->all());
    }

    public function test_order_by_filter_sorts_an_enum_column_by_its_translated_label(): void
    {
        // Labels: male => "Male", female => "Female". Sorting by the raw value
        // would put `female` first ascending; by label it is "Female" < "Male",
        // which happens to agree — so assert descending, where the raw order
        // (male, female) and the label order (Male, Female) also agree but the
        // CASE expression is what produces it.
        $male = $this->createUser(['gender' => 'male']);
        $female = $this->createUser(['gender' => 'female']);

        request()->replace(['sort_column' => 'gender', 'sort_direction' => 'asc']);

        $results = $this->pipe(User::query(), OrderByFilter::class)->pluck('id');

        $this->assertEquals([$female->id, $male->id], $results->all());
    }

    public function test_order_by_filter_strips_a_display_prefix(): void
    {
        $male = $this->createUser(['gender' => 'male']);
        $female = $this->createUser(['gender' => 'female']);

        request()->replace(['sort_column' => 'display_gender', 'sort_direction' => 'asc']);

        $results = $this->pipe(User::query(), OrderByFilter::class)->pluck('id');

        $this->assertEquals([$female->id, $male->id], $results->all());
    }

    public function test_order_by_filter_sorts_a_translatable_column_by_the_active_locale(): void
    {
        app()->setLocale('en');

        $zebra = Country::factory()->create(['name' => ['en' => 'Zambia', 'ar' => 'ألف']]);
        $alpha = Country::factory()->create(['name' => ['en' => 'Algeria', 'ar' => 'ياء']]);

        request()->replace(['sort_column' => 'name', 'sort_direction' => 'asc']);

        $results = $this->pipe(Country::query(), OrderByFilter::class)->pluck('id');

        $this->assertEquals([$alpha->id, $zebra->id], $results->all());
    }
}
