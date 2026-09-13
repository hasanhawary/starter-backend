<?php

namespace Tests\Feature\Global;

use App\Models\Country;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Form\app\Models\Form;
use Modules\Form\app\Models\FormSubmission;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\ScheduleEvent;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * `config/discovery.php`'s `sorting` map is keyed by the module key
 * `getModelKey()` produces — singular snake case — because that is what
 * `wrapPaginate()` hands to `resourceSorting()`. A plural key there silently
 * produces an empty `sorting` payload on every listing.
 */
class DiscoverySortingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'en');
    }

    public function test_every_sorting_key_is_the_module_key_of_a_real_model(): void
    {
        $models = [
            'user' => User::class,
            'role' => Role::class,
            'permission' => Permission::class,
            'country' => Country::class,
            'setting' => Setting::class,
            'notification' => Notification::class,
            'activity' => Activity::class,
            'showcase' => Showcase::class,
            'showcase_category' => ShowcaseCategory::class,
            'form' => Form::class,
            'form_submission' => FormSubmission::class,
            'system_event' => SystemEvent::class,
            'notification_event' => NotificationEvent::class,
            'schedule_event' => ScheduleEvent::class,
        ];

        $configured = array_keys(config('discovery.sorting'));

        $this->assertEqualsCanonicalizing(array_keys($models), $configured);

        foreach ($models as $key => $model) {
            $this->assertSame($key, getModelKey($model), "`{$key}` is not the module key of {$model}.");
        }
    }

    public function test_a_listing_returns_a_populated_sorting_map(): void
    {
        $this->actingAsUserWithPermissions(['view-all-showcase']);
        Showcase::factory()->create();

        $sorting = $this->getJson('api/showcases')->json('data.sorting');

        $this->assertNotEmpty($sorting, 'The sorting payload is empty — the config key does not match getModelKey().');

        // Every resource key is present, mapped to its sort column or to null.
        $this->assertSame('status', $sorting['display_status']);
        $this->assertSame('creator.name', $sorting['creator']);
        $this->assertSame('reference', $sorting['reference']);
        $this->assertNull($sorting['tags'], 'An array field must not advertise itself as sortable.');
        $this->assertNull($sorting['buttons']);
    }

    public function test_the_country_listing_advertises_its_translatable_columns(): void
    {
        $this->actingAsUserWithPermissions();
        Country::factory()->create();

        $sorting = $this->getJson('api/countries')->json('data.sorting');

        $this->assertSame('name', $sorting['translation_name']);
        $this->assertSame('nationality', $sorting['translation_nationality']);
        $this->assertNull($sorting['flag']);
    }

    public function test_every_advertised_sort_column_actually_sorts(): void
    {
        // Sorting by a relation's translatable column emits JSON_UNQUOTE, which
        // only MySQL provides; the application runs on MySQL.
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Relation sorting on a translatable column is MySQL-only (JSON_UNQUOTE).');
        }

        $this->actingAsUserWithPermissions(['view-all-showcase']);
        Showcase::factory()->count(3)->create();

        $columns = collect(config('discovery.sorting.showcase'))
            ->mapWithKeys(fn ($value, $key) => is_int($key) ? [$value => $value] : [$key => $value])
            ->values()
            ->unique();

        foreach ($columns as $column) {
            $this->getJson("api/showcases?sort_column={$column}&sort_direction=asc")
                ->assertOk();
        }

        $this->assertGreaterThan(15, $columns->count());
    }

    public function test_declared_columns_exist_on_their_model(): void
    {
        $skip = ['remaining_days'];

        foreach (config('discovery.sorting') as $module => $entries) {
            foreach ($entries as $key => $value) {
                $column = is_int($key) ? $value : $value;

                if (in_array($column, $skip, true) || str_contains($column, '.')) {
                    continue;
                }

                $model = $this->modelFor($module);
                $this->assertContains(
                    $column,
                    Schema::getColumnListing((new $model)->getTable()),
                    "`discovery.sorting.{$module}` points at `{$column}`, which is not a column of ".(new $model)->getTable(),
                );
            }
        }
    }

    private function modelFor(string $module): string
    {
        return [
            'user' => User::class,
            'role' => Role::class,
            'permission' => Permission::class,
            'country' => Country::class,
            'setting' => Setting::class,
            'notification' => Notification::class,
            'activity' => Activity::class,
            'showcase' => Showcase::class,
            'showcase_category' => ShowcaseCategory::class,
            'form' => Form::class,
            'form_submission' => FormSubmission::class,
            'system_event' => SystemEvent::class,
            'notification_event' => NotificationEvent::class,
            'schedule_event' => ScheduleEvent::class,
        ][$module];
    }
}
