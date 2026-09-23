<?php

namespace Tests\Feature\Showcase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;
use Modules\Showcase\app\Enum\ShowcasePriorityEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Modules\Showcase\app\Models\ShowcaseNote;
use Modules\Showcase\app\Models\ShowcaseTag;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * The model layer: every relation shape the module declares, the enum casts and
 * their defaults, the scope trait, and the global traits the models compose.
 */
class ShowcaseRelationsAndTraitsTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Enums
    |--------------------------------------------------------------------------
    */
    public function test_enum_columns_are_stored_as_strings_and_cast_back(): void
    {
        $showcase = Showcase::factory()->create();

        $this->assertSame(ShowcaseStatusEnum::Draft, $showcase->status);
        $this->assertSame(ShowcasePriorityEnum::Medium, $showcase->priority);
        $this->assertSame(ShowcaseVisibilityEnum::Internal, $showcase->visibility);

        $this->assertDatabaseHas('showcases', [
            'id' => $showcase->id,
            'status' => 'draft',
            'priority' => 'medium',
            'visibility' => 'internal',
        ]);
    }

    public function test_enum_lists_carry_translated_labels_colors_and_icons(): void
    {
        app()->setLocale('en');

        $status = collect(ShowcaseStatusEnum::getList())->firstWhere('value', 'in_review');

        $this->assertSame('In Review', $status['label']);
        $this->assertSame('#F59E0B', $status['color']);
        $this->assertSame('mdi-clock-outline', $status['icon']);

        app()->setLocale('ar');
        $this->assertSame('قيد المراجعة', ShowcaseStatusEnum::resolve('in_review'));
    }

    public function test_every_enum_case_has_both_translations(): void
    {
        $enums = [
            'showcase_status' => ShowcaseStatusEnum::cases(),
            'showcase_priority' => ShowcasePriorityEnum::cases(),
            'showcase_visibility' => ShowcaseVisibilityEnum::cases(),
            'showcase_note_type' => ShowcaseNoteTypeEnum::cases(),
        ];

        foreach ($enums as $key => $cases) {
            foreach ($cases as $case) {
                foreach (['en', 'ar'] as $locale) {
                    app()->setLocale($locale);
                    $label = __("enums.$key.{$case->value}");

                    $this->assertNotSame("enums.$key.{$case->value}", $label, "Missing $locale label for $key.{$case->value}");
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */
    public function test_a_category_nests_under_a_parent_and_owns_its_showcases(): void
    {
        $parent = ShowcaseCategory::factory()->create();
        $child = ShowcaseCategory::factory()->childOf($parent)->create();
        Showcase::factory()->count(2)->create(['showcase_category_id' => $child->id]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertSame($parent->id, $child->parent->id);
        $this->assertSame(2, $child->showcases()->count());
    }

    public function test_tags_attach_through_a_pivot_that_carries_its_own_payload(): void
    {
        $showcase = Showcase::factory()->create();
        $primary = ShowcaseTag::factory()->create();
        $secondary = ShowcaseTag::factory()->create();

        $showcase->tags()->sync([
            $primary->id => ['is_primary' => true],
            $secondary->id => ['is_primary' => false],
        ]);

        $this->assertSame(2, $showcase->tags()->count());
        $this->assertSame([$primary->id], $showcase->primaryTag()->pluck('showcase_tags.id')->all());
        // The inverse side resolves the same link.
        $this->assertTrue($primary->showcases->contains($showcase));
    }

    public function test_notes_are_polymorphic_across_both_models_that_use_the_trait(): void
    {
        $showcase = Showcase::factory()->create();
        $category = ShowcaseCategory::factory()->create();

        $showcase->addNote('On the record', ShowcaseNoteTypeEnum::Risk->value);
        $category->addNote('On the category');

        $this->assertSame(1, $showcase->notes()->count());
        $this->assertSame(1, $category->notes()->count());
        $this->assertSame(Showcase::class, $showcase->notes()->first()->notable_type);
        $this->assertSame(ShowcaseNoteTypeEnum::Risk, $showcase->notes()->first()->type);
        // morphTo resolves back to the owning record.
        $this->assertTrue($showcase->notes()->first()->notable->is($showcase));
    }

    public function test_latest_note_and_pinned_notes_narrow_the_thread(): void
    {
        $showcase = Showcase::factory()->create();
        ShowcaseNote::factory()->for($showcase, 'notable')->create(['body' => 'first']);
        ShowcaseNote::factory()->for($showcase, 'notable')->pinned()->create(['body' => 'second']);

        $this->assertSame('second', $showcase->latestNote->body);
        $this->assertSame(['second'], $showcase->pinnedNotes()->pluck('body')->all());
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function test_visible_to_hides_a_private_record_from_everyone_but_its_owner(): void
    {
        $owner = $this->createUser();
        $stranger = $this->createUser();

        $private = Showcase::factory()->visibility(ShowcaseVisibilityEnum::Private)->ownedBy($owner->id)->create();
        $internal = Showcase::factory()->visibility(ShowcaseVisibilityEnum::Internal)->create();
        $public = Showcase::factory()->visibility(ShowcaseVisibilityEnum::Public)->create();

        $this->assertEqualsCanonicalizing(
            [$private->id, $internal->id, $public->id],
            Showcase::query()->visibleTo($owner->id)->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$internal->id, $public->id],
            Showcase::query()->visibleTo($stranger->id)->pluck('id')->all(),
        );

        // A guest sees only what is public.
        $this->assertSame([$public->id], Showcase::query()->visibleTo(null)->pluck('id')->all());
    }

    public function test_published_and_expiring_within_select_the_expected_records(): void
    {
        $published = Showcase::factory()->published()->create(['expires_at' => now()->addDays(3)]);
        Showcase::factory()->create(['expires_at' => now()->addDays(90)]);

        $this->assertSame([$published->id], Showcase::query()->published()->pluck('id')->all());
        $this->assertSame([$published->id], Showcase::query()->expiringWithin(7)->pluck('id')->all());
    }

    public function test_owned_by_defaults_to_the_authenticated_user(): void
    {
        $owner = $this->createUser();
        $mine = Showcase::factory()->ownedBy($owner->id)->create();
        Showcase::factory()->create();

        $this->assertSame([$mine->id], Showcase::query()->ownedBy($owner->id)->pluck('id')->all());

        Sanctum::actingAs($owner);
        $this->assertSame([$mine->id], Showcase::query()->ownedBy()->pluck('id')->all());
    }

    /*
    |--------------------------------------------------------------------------
    | Representation scopes
    |--------------------------------------------------------------------------
    */
    public function test_the_listing_scope_resolves_the_resource_data_without_a_query_per_row(): void
    {
        $viewer = $this->createUser();
        Showcase::factory()->count(3)->create()->each(fn (Showcase $s) => $s->pinUsers()->attach($viewer->id));

        Sanctum::actingAs($viewer);

        DB::enableQueryLog();
        $records = Showcase::query()->withListingData()->get();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // One query for the records plus a fixed set for the eager loads —
        // it must not grow with the number of rows.
        $this->assertLessThanOrEqual(7, $queries);

        foreach ($records as $record) {
            $this->assertTrue($record->relationLoaded('category'));
            $this->assertTrue($record->relationLoaded('owner'));
            $this->assertTrue($record->relationLoaded('creator'));
            $this->assertTrue($record->relationLoaded('tags'));
            $this->assertSame(1, $record->pin_users_count);
            $this->assertTrue((bool) $record->is_pinned);
        }
    }

    public function test_the_detail_loader_adds_the_note_thread_to_one_resolved_record(): void
    {
        $viewer = $this->createUser();
        $showcase = Showcase::factory()->create();
        $showcase->notes()->create(['body' => 'A note', 'author_id' => $viewer->id]);

        Sanctum::actingAs($viewer);

        $loaded = $showcase->loadDetailData();

        $this->assertTrue($loaded->relationLoaded('notes'));
        $this->assertTrue($loaded->notes->first()->relationLoaded('author'));
        $this->assertSame(0, $loaded->pin_users_count);
        $this->assertFalse((bool) $loaded->is_pinned);
    }

    public function test_the_pinned_flag_is_resolved_per_viewer(): void
    {
        $pinner = $this->createUser();
        $other = $this->createUser();
        $showcase = Showcase::factory()->create();
        $showcase->pinUsers()->attach($pinner->id);

        $this->assertTrue((bool) Showcase::query()->withListingData($pinner->id)->first()->is_pinned);
        $this->assertFalse((bool) Showcase::query()->withListingData($other->id)->first()->is_pinned);
    }

    /*
    |--------------------------------------------------------------------------
    | Global traits
    |--------------------------------------------------------------------------
    */
    public function test_the_observer_generates_a_unique_reference_and_derives_the_publication_date(): void
    {
        $showcase = Showcase::factory()->create();

        $this->assertNotNull($showcase->reference);
        $this->assertNull($showcase->published_at);

        $showcase->update(['status' => ShowcaseStatusEnum::Published->value]);
        $this->assertNotNull($showcase->refresh()->published_at);

        // Leaving the published state clears the date again.
        $showcase->update(['status' => ShowcaseStatusEnum::Archived->value]);
        $this->assertNull($showcase->refresh()->published_at);
    }

    public function test_lifecycle_events_are_written_to_the_activity_log_under_the_model_name(): void
    {
        Activity::query()->delete();

        $showcase = Showcase::factory()->create();
        $showcase->update(['priority' => ShowcasePriorityEnum::High->value]);
        $showcase->delete();

        $activities = Activity::query()->where('subject_type', Showcase::class)->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted'], $activities->pluck('event')->all());
        $this->assertSame(['Showcase'], $activities->pluck('log_name')->unique()->all());
        $this->assertSame([$showcase->id], $activities->pluck('subject_id')->unique()->all());
    }

    public function test_the_model_declares_which_attributes_stay_out_of_the_audit_trail(): void
    {
        $showcase = Showcase::factory()->create();

        // Uploaded paths and free-form payloads are noise in an audit trail.
        $this->assertSame(['cover', 'metadata', 'views_count'], $showcase->logExceptAttributes);
        $this->assertSame(
            $showcase->logExceptAttributes,
            array_values(array_intersect($showcase->getActivitylogOptions()->logExceptAttributes, $showcase->logExceptAttributes)),
        );
    }

    public function test_a_category_still_holding_showcases_cannot_be_deleted(): void
    {
        $category = ShowcaseCategory::factory()->create();
        Showcase::factory()->create(['showcase_category_id' => $category->id]);

        $this->assertSame(['showcases', 'children'], $category->preventDeleteRelations());
        $this->assertTrue($category->showcases()->exists());
    }

    public function test_pinning_is_recorded_per_user(): void
    {
        $showcase = Showcase::factory()->create();
        $first = $this->createUser();
        $second = $this->createUser();

        $showcase->pinUsers()->attach([$first->id]);

        $this->assertTrue($showcase->isPinnedBy($first->id));
        $this->assertFalse($showcase->isPinnedBy($second->id));
        $this->assertSame([$showcase->id], Showcase::query()->pinnedBy($first->id)->pluck('id')->all());
    }

    public function test_remaining_days_is_derived_from_the_expiry_date(): void
    {
        $showcase = Showcase::factory()->create(['expires_at' => now()->addDays(5)]);

        $this->assertSame(5, $showcase->remainingDays());
        $this->assertNull(Showcase::factory()->create(['expires_at' => null])->remainingDays());
    }
}
