<?php

namespace Tests\Feature\Global;

use App\Http\Controllers\API\BaseController;
use App\Models\Country;
use App\Models\User;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * A country that refuses to be deleted while any user still points at it as
 * their phone code, so `preventDeleteRelations()` has something to guard.
 */
class GuardedCountry extends Country
{
    protected $table = 'countries';

    public function accounts(): HasMany
    {
        return $this->hasMany(User::class, 'phone_code_id');
    }

    /**
     * @return array<int|string, string>
     */
    public function preventDeleteRelations(): array
    {
        return ['accounts'];
    }
}

class GuardedCountryController extends BaseController
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->setDeleteModel(GuardedCountry::class)->enableDeletePolicy(false);
    }

    public function destroyGuarded(): JsonResponse
    {
        return $this->destroy();
    }
}

class DeleteMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix('api')->middleware('api')
            ->delete('_test/guarded-countries', [GuardedCountryController::class, 'destroyGuarded']);
    }

    /*
    |--------------------------------------------------------------------------
    | guardLinkedRelations
    |--------------------------------------------------------------------------
    */
    public function test_delete_is_blocked_while_a_declared_relation_still_has_records(): void
    {
        $country = Country::factory()->create();
        $this->createUser(['phone_code_id' => $country->id]);

        $this->deleteJson('/api/_test/guarded-countries', ['ids' => [$country->id]])
            ->assertForbidden()
            ->assertJsonPath('message', __('validation.not_allowed_to_delete_linked'));

        $this->assertDatabaseHas('countries', ['id' => $country->id, 'deleted_at' => null]);
    }

    public function test_delete_proceeds_when_no_declared_relation_has_records(): void
    {
        $country = Country::factory()->create();

        $this->deleteJson('/api/_test/guarded-countries', ['ids' => [$country->id]])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertSoftDeleted('countries', ['id' => $country->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | restoredData
    |--------------------------------------------------------------------------
    */
    public function test_restore_returns_the_single_restored_record(): void
    {
        $this->actingAsUserWithPermissions(['restore-country']);

        $country = Country::factory()->create();
        $country->delete();

        $response = $this->postJson('/api/countries/restore', ['ids' => [$country->id]]);

        $this->assertSuccessEnvelope($response);
        $response->assertJsonPath('data.id', $country->id);
        $this->assertDatabaseHas('countries', ['id' => $country->id, 'deleted_at' => null]);
    }

    public function test_restore_returns_a_list_when_several_records_are_restored(): void
    {
        $this->actingAsUserWithPermissions(['restore-country']);

        $first = Country::factory()->create();
        $second = Country::factory()->create();
        $first->delete();
        $second->delete();

        $response = $this->postJson('/api/countries/restore', ['ids' => [$first->id, $second->id]]);

        $this->assertSuccessEnvelope($response);
        $response->assertJsonCount(2, 'data');
    }

    public function test_delete_reports_a_missing_record(): void
    {
        $this->actingAsUserWithPermissions(['delete-country']);

        $this->deleteJson('/api/countries/delete', ['ids' => [9999]])
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', __('api.record_not_found'));
    }
}
