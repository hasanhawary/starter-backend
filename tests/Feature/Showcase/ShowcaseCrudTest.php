<?php

namespace Tests\Feature\Showcase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Showcase\app\Enum\ShowcasePriorityEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Modules\Showcase\app\Models\ShowcaseTag;
use Tests\TestCase;

/**
 * The HTTP contract of the module: authorization, the response envelope, the
 * service-backed write path, and the HasDeleteMethods / HasToggleActiveMethods
 * lifecycle endpoints.
 */
class ShowcaseCrudTest extends TestCase
{
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'create-showcase', 'update-showcase', 'delete-showcase',
        'restore-showcase', 'force-delete-showcase', 'toggle-active-showcase',
        'view-all-showcase', 'view-own-showcase', 'publish-showcase', 'archive-showcase', 'pin-showcase',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // LanguageMiddleware falls back to Arabic without this header, which would
        // decide which translation the Resource returns as `translation_name`.
        $this->withHeader('Accept-Language', 'en');
    }

    /**
     * `UniqueCheck` searches a translatable column with JSON_UNQUOTE/JSON_EXTRACT,
     * which SQLite has no functions for. Writes that run that rule are therefore
     * only verifiable on MySQL, the driver the application actually runs on.
     */
    private function skipWithoutMysql(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Translatable uniqueness is MySQL-only (JSON_UNQUOTE).');
        }
    }

    public function test_the_listing_requires_authentication(): void
    {
        $this->getJson('api/showcases')->assertUnauthorized();
    }

    public function test_the_listing_requires_a_view_permission(): void
    {
        $this->actingAsUserWithPermissions([]);

        $this->getJson('api/showcases')->assertForbidden();
    }

    public function test_the_listing_returns_the_success_envelope(): void
    {
        $this->actingAsUserWithPermissions(['view-all-showcase']);
        Showcase::factory()->count(3)->create();

        $response = $this->getJson('api/showcases');

        $this->assertSuccessEnvelope($response);
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_storing_creates_the_record_with_its_generated_reference_and_tags(): void
    {
        $this->skipWithoutMysql();

        $user = $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);
        $category = ShowcaseCategory::factory()->create();
        $tags = ShowcaseTag::factory()->count(2)->create();

        $response = $this->postJson('api/showcases', [
            'name' => ['en' => 'First Showcase', 'ar' => 'العرض الأول'],
            'description' => ['en' => 'Description', 'ar' => 'وصف'],
            'showcase_category_id' => $category->id,
            'priority' => ShowcasePriorityEnum::High->value,
            'visibility' => ShowcaseVisibilityEnum::Internal->value,
            'tag_ids' => $tags->pluck('id')->all(),
            'primary_tag_id' => $tags->first()->id,
            'note' => 'Opening note',
        ]);

        $this->assertSuccessEnvelope($response);

        $showcase = Showcase::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/^SHC-\d{4}-[A-Z0-9]{6}$/', $showcase->reference);
        $this->assertSame(ShowcaseStatusEnum::Draft, $showcase->status);
        $this->assertNull($showcase->published_at);
        // CreatedByObserver stamps the authenticated user.
        $this->assertSame($user->id, $showcase->created_by);
        $this->assertSame(2, $showcase->tags()->count());
        $this->assertTrue((bool) $showcase->tags()->where('showcase_tags.id', $tags->first()->id)->first()->pivot->is_primary);
        // The opening note went through the polymorphic thread.
        $this->assertSame('Opening note', $showcase->notes()->value('body'));
    }

    public function test_storing_rejects_an_unknown_category(): void
    {
        $this->skipWithoutMysql();

        $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);

        $this->postJson('api/showcases', [
            'name' => ['en' => 'Bad', 'ar' => 'سيئ'],
            'showcase_category_id' => 9999,
        ])->assertStatus(422);

        $this->assertDatabaseCount('showcases', 0);
    }

    public function test_updating_without_a_tag_key_leaves_the_relation_untouched(): void
    {
        $this->skipWithoutMysql();

        $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);
        $tags = ShowcaseTag::factory()->count(2)->create();
        $showcase = Showcase::factory()->create();
        $showcase->tags()->sync($tags->pluck('id')->all());

        $response = $this->putJson("api/showcases/{$showcase->id}", [
            'name' => ['en' => 'Renamed', 'ar' => 'مُعاد التسمية'],
            'showcase_category_id' => $showcase->showcase_category_id,
        ]);

        $this->assertSuccessEnvelope($response);
        $this->assertSame('Renamed', $showcase->refresh()->getTranslation('name', 'en'));
        $this->assertSame(2, $showcase->tags()->count());
    }

    public function test_the_lifecycle_endpoints_soft_delete_restore_and_force_delete(): void
    {
        $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);
        $showcase = Showcase::factory()->create();

        $this->assertSuccessEnvelope($this->deleteJson('api/showcases/delete', ['ids' => [$showcase->id]]));
        $this->assertSoftDeleted('showcases', ['id' => $showcase->id]);

        $this->assertSuccessEnvelope($this->postJson('api/showcases/restore', ['ids' => [$showcase->id]]));
        $this->assertDatabaseHas('showcases', ['id' => $showcase->id, 'deleted_at' => null]);

        $this->deleteJson('api/showcases/delete', ['ids' => [$showcase->id]]);
        $this->assertSuccessEnvelope($this->deleteJson('api/showcases/force-delete', ['ids' => [$showcase->id]]));
        $this->assertDatabaseCount('showcases', 0);
    }

    public function test_deleting_records_the_user_who_deleted_them(): void
    {
        $user = $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);
        $showcase = Showcase::factory()->create();

        $this->deleteJson('api/showcases/delete', ['ids' => [$showcase->id]]);

        $this->assertSame($user->id, Showcase::withTrashed()->find($showcase->id)->deleted_by);
    }

    public function test_toggle_active_flips_the_flag(): void
    {
        $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);
        $showcase = Showcase::factory()->create(['is_active' => true]);

        $this->assertSuccessEnvelope($this->putJson('api/showcases/toggle-active', ['ids' => [$showcase->id]]));

        $this->assertFalse($showcase->refresh()->is_active);
    }

    public function test_pinning_toggles_the_pin_for_the_current_user_only(): void
    {
        $user = $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);
        $showcase = Showcase::factory()->create();

        $this->assertSuccessEnvelope($this->postJson("api/showcases/{$showcase->id}/pin"));
        $this->assertDatabaseHas('showcase_user_pins', ['showcase_id' => $showcase->id, 'user_id' => $user->id]);

        $this->assertSuccessEnvelope($this->postJson("api/showcases/{$showcase->id}/pin"));
        $this->assertDatabaseCount('showcase_user_pins', 0);
    }

    public function test_the_listing_sorts_by_every_column_discovery_advertises(): void
    {
        $this->actingAsUserWithPermissions(['view-all-showcase']);
        Showcase::factory()->count(2)->create();

        // `remaining_days` is a derived sort key backed by a SQL expression, so it
        // only resolves on MySQL; the rest are plain, relation and enum columns.
        foreach (['reference', 'translation_name', 'display_status', 'creator', 'expires_at'] as $column) {
            $this->getJson("api/showcases?sort_column={$column}&sort_direction=asc")
                ->assertOk();
        }
    }

    public function test_the_listing_sorts_by_the_derived_remaining_days_key(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('`remaining_days` is a DATEDIFF() expression, which only MySQL provides.');
        }

        $this->actingAsUserWithPermissions(['view-all-showcase']);

        $soon = Showcase::factory()->create(['expires_at' => now()->addDays(2)]);
        $later = Showcase::factory()->create(['expires_at' => now()->addDays(40)]);

        $ids = $this->getJson('api/showcases?sort_column=remaining_days&sort_direction=asc')->json('data.data.*.id');

        $this->assertSame([$soon->id, $later->id], $ids);
    }

    public function test_the_listing_filters_by_status_priority_category_and_tag(): void
    {
        $this->actingAsUserWithPermissions(['view-all-showcase']);

        $tag = ShowcaseTag::factory()->create();
        $wanted = Showcase::factory()->critical()->published()->create();
        $wanted->tags()->attach($tag->id);
        Showcase::factory()->create();

        // The select options discovery declares are submitted through the
        // shared `advanced[]` contract, the same as every other module.
        $filters = [
            'status' => ShowcaseStatusEnum::Published->value,
            'priority' => ShowcasePriorityEnum::Critical->value,
            'showcase_category_id' => $wanted->showcase_category_id,
            'tag_id' => $tag->id,
        ];

        foreach ($filters as $key => $value) {
            $query = http_build_query(['advanced' => [['key' => $key, 'value' => [$value]]]]);

            $response = $this->getJson("api/showcases?{$query}");

            $this->assertSuccessEnvelope($response);
            $this->assertSame([$wanted->id], $response->json('data.data.*.id'), "Advanced filter `{$key}` returned the wrong records.");
        }
    }

    public function test_an_advanced_filter_accepts_a_grouped_multi_select(): void
    {
        $this->actingAsUserWithPermissions(['view-all-showcase']);

        $wanted = Showcase::factory()->published()->create();
        Showcase::factory()->create();

        // BaseFilter::normalizeAdvancedFilters() flattens the array of arrays a
        // grouped option submits into the union AdvancedFilter can use.
        $query = http_build_query([
            'advanced' => [[
                'key' => 'status',
                'value' => [[ShowcaseStatusEnum::Published->value], [ShowcaseStatusEnum::Archived->value]],
            ]],
        ]);

        $response = $this->getJson("api/showcases?{$query}");

        $this->assertSuccessEnvelope($response);
        $this->assertSame([$wanted->id], $response->json('data.data.*.id'));
    }

    public function test_the_listing_flags_map_to_the_models_named_scopes(): void
    {
        $user = $this->actingAsUserWithPermissions(['view-all-showcase', 'pin-showcase']);

        $mine = Showcase::factory()->ownedBy($user->id)->create(['expires_at' => now()->addDays(3)]);
        $published = Showcase::factory()->published()->create(['expires_at' => now()->addDays(90)]);
        $mine->pinUsers()->attach($user->id);

        $cases = [
            'mine=1' => $mine->id,
            'pinned=1' => $mine->id,
            'published=1' => $published->id,
            'expiring_within=7' => $mine->id,
        ];

        foreach ($cases as $flag => $expectedId) {
            $response = $this->getJson("api/showcases?{$flag}");

            $this->assertSuccessEnvelope($response);
            $this->assertSame([$expectedId], $response->json('data.data.*.id'), "Flag {$flag} returned the wrong records.");
        }
    }

    public function test_a_private_record_is_hidden_from_a_user_who_neither_owns_nor_created_it(): void
    {
        $owner = $this->createUser();
        $private = Showcase::factory()->visibility(ShowcaseVisibilityEnum::Private)->ownedBy($owner->id)->create();
        $visible = Showcase::factory()->visibility(ShowcaseVisibilityEnum::Public)->create();

        $this->actingAsUserWithPermissions(['view-all-showcase']);

        $response = $this->getJson('api/showcases');

        $this->assertSame([$visible->id], $response->json('data.data.*.id'));
        $this->getJson("api/showcases/{$private->id}")->assertForbidden();
    }

    public function test_the_resource_exposes_translations_enum_labels_and_the_pin_flag(): void
    {
        $this->actingAsUserWithPermissions(self::ALL_PERMISSIONS);
        $showcase = Showcase::factory()->critical()->create([
            'name' => ['en' => 'Visible', 'ar' => 'ظاهر'],
        ]);

        $response = $this->getJson("api/showcases/{$showcase->id}");

        $this->assertSuccessEnvelope($response);
        $response
            ->assertJsonPath('data.translation_name', 'Visible')
            ->assertJsonPath('data.name.ar', 'ظاهر')
            ->assertJsonPath('data.priority', ShowcasePriorityEnum::Critical->value)
            ->assertJsonPath('data.display_priority', 'Critical')
            ->assertJsonPath('data.is_pinned', false)
            ->assertJsonPath('data.reference', $showcase->reference);
    }
}
