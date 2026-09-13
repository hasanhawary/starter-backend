<?php

namespace Tests\Feature\Showcase;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Modules\Notification\app\Enum\SystemEventModuleEnum;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Jobs\SendNotificationJob;
use Modules\Notification\app\Models\NotificationReceiver;
use Modules\Notification\app\Models\NotificationVerifiableDate;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Models\Variable;
use Modules\Notification\database\seeders\NotificationDatabaseSeeder;
use Modules\Notification\database\seeders\SystemEventVariableSeeder;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Services\ShowcaseService;
use Modules\Showcase\app\Tools\Status\Strategies\ArchivedStatus;
use Modules\Showcase\app\Tools\Status\Strategies\PublishedStatus;
use Tests\TestCase;

/**
 * The module's notification contract: the catalogue entries it owns, and the
 * dispatch points that fire them after the surrounding write has committed.
 */
class ShowcaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SystemEventVariableSeeder::flushCache();
    }

    /*
    |--------------------------------------------------------------------------
    | Catalogue
    |--------------------------------------------------------------------------
    */
    public function test_the_module_owns_four_system_event_slugs(): void
    {
        $this->assertContains(SystemEventModuleEnum::Showcase, SystemEventModuleEnum::cases());

        $slugs = collect(SystemEventSlugEnum::cases())
            ->map->value
            ->filter(fn (string $slug) => str_contains($slug, 'showcase'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            ['create_showcase', 'publish_showcase', 'toggle_active_showcase', 'update_showcase'],
            $slugs,
        );
    }

    public function test_seeding_registers_the_showcase_events_against_the_module_model(): void
    {
        $this->seedCatalogue();

        $events = SystemEvent::query()->where('module', 'showcase')->get();

        $this->assertCount(4, $events);

        foreach ($events as $event) {
            $this->assertSame(Showcase::class, $event->model_type);
            $this->assertNotSame('', (string) ($event->getTranslations('name')['en'] ?? ''));
            $this->assertNotSame('', (string) ($event->getTranslations('name')['ar'] ?? ''));
        }
    }

    public function test_every_seeded_variable_points_at_a_real_column_or_relation(): void
    {
        $this->seedCatalogue();

        $showcase = Showcase::factory()->create();
        $columns = Schema::getColumnListing($showcase->getTable());

        foreach (Variable::query()->where('module', 'showcase')->get() as $variable) {
            $key = $variable->access_key;

            if (! str_contains($key, '.')) {
                $this->assertContains($key, $columns, "Variable {$key} is not a column of {$showcase->getTable()}.");

                continue;
            }

            // A dotted key walks a relation, e.g. `category.name`.
            [$relation, $attribute] = explode('.', $key, 2);

            $this->assertTrue(method_exists($showcase, $relation), "Variable {$key} names a relation that does not exist.");

            $related = $showcase->{$relation}()->getRelated();
            $this->assertContains(
                $attribute,
                Schema::getColumnListing($related->getTable()),
                "Variable {$key} is not a column of {$related->getTable()}.",
            );
        }
    }

    public function test_the_seeded_receivers_and_dates_point_at_real_relations_and_columns(): void
    {
        $this->seedCatalogue();

        $showcase = Showcase::factory()->create();

        $relations = NotificationReceiver::query()
            ->where('module', 'showcase')
            ->whereNotNull('relation')
            ->pluck('relation');

        $this->assertEqualsCanonicalizing(['owner', 'creator'], $relations->all());

        foreach ($relations as $relation) {
            $this->assertTrue(method_exists($showcase, $relation), "Missing relation {$relation}.");
        }

        $dates = NotificationVerifiableDate::query()->where('module', 'showcase')->get();

        $this->assertEqualsCanonicalizing(
            ['expires_at', 'published_at', 'created_at'],
            $dates->pluck('access_key')->all(),
        );

        $showcase->forceFill(['published_at' => now()])->save();

        foreach ($dates as $date) {
            // A reminder can only hang off a column that really holds a date.
            $this->assertInstanceOf(
                CarbonInterface::class,
                $showcase->refresh()->{$date->access_key},
                "Verifiable date {$date->access_key} is not a date on the Showcase model.",
            );
        }
    }

    public function test_a_draft_event_does_not_expose_the_publication_date(): void
    {
        $this->seedCatalogue();

        $createEvent = SystemEvent::query()->where('event_slug', SystemEventSlugEnum::CreateShowcase->value)->firstOrFail();

        $this->assertNotContains('published_at', $createEvent->variables->pluck('access_key')->all());
        $this->assertContains('reference', $createEvent->variables->pluck('access_key')->all());
    }

    public function test_the_cover_and_metadata_columns_never_become_notification_variables(): void
    {
        $this->seedCatalogue();

        $keys = Variable::query()->where('module', 'showcase')->pluck('access_key')->all();

        $this->assertNotContains('cover', $keys);
        $this->assertNotContains('metadata', $keys);
    }

    /*
    |--------------------------------------------------------------------------
    | Dispatch
    |--------------------------------------------------------------------------
    */
    public function test_creating_a_record_queues_its_notification(): void
    {
        Queue::fake();

        app(ShowcaseService::class)->store(Showcase::factory()->make()->getAttributes());

        Queue::assertPushed(
            SendNotificationJob::class,
            fn (SendNotificationJob $job) => $job->eventName === SystemEventSlugEnum::CreateShowcase->value,
        );
    }

    /**
     * The deferral itself cannot be asserted through a fake: `QueueFake::push()`
     * records the job directly and never reaches `Queue::enqueueUsing()`, where
     * the after-commit handling lives, and `Bus::fake()` intercepts even earlier.
     * So assert the contract the application actually owns — the job declares
     * `afterCommit`, which is why the service needs no `DB::afterCommit()`
     * wrapper of its own — and let the framework honour it.
     */
    public function test_the_notification_job_declares_itself_after_commit(): void
    {
        Queue::fake();

        app(ShowcaseService::class)->store(Showcase::factory()->make()->getAttributes());

        Queue::assertPushed(
            SendNotificationJob::class,
            fn (SendNotificationJob $job) => $job->afterCommit === true,
        );
    }

    public function test_a_rolled_back_write_persists_nothing(): void
    {
        $showcase = Showcase::factory()->make();

        try {
            DB::transaction(function () use ($showcase) {
                app(ShowcaseService::class)->store($showcase->getAttributes());

                throw new \RuntimeException('later step failed');
            });
        } catch (\RuntimeException) {
            // expected
        }

        // The record is gone, and the queued notification goes with it: the job
        // declares `afterCommit`, so the dispatcher discards it on rollback.
        $this->assertDatabaseCount('showcases', 0);
    }

    public function test_the_publish_transition_queues_the_publish_event(): void
    {
        Queue::fake();

        $showcase = Showcase::factory()->create(['status' => ShowcaseStatusEnum::InReview->value]);

        // Publishing is a workflow transition, so the strategy owns the notification.
        (new PublishedStatus($showcase, $this->createUser()))->handle([]);

        Queue::assertPushed(
            SendNotificationJob::class,
            fn (SendNotificationJob $job) => $job->eventName === SystemEventSlugEnum::PublishShowcase->value,
        );
    }

    public function test_flipping_activation_queues_the_toggle_event_wherever_it_happens(): void
    {
        Queue::fake();

        $showcase = Showcase::factory()->create(['is_active' => true]);

        // No endpoint and no service call: the observer sees the column change.
        $showcase->update(['is_active' => false]);

        Queue::assertPushed(
            SendNotificationJob::class,
            fn (SendNotificationJob $job) => $job->eventName === SystemEventSlugEnum::ToggleActiveShowcase->value,
        );
    }

    public function test_a_transition_that_deactivates_does_not_also_announce_an_activation_change(): void
    {
        Queue::fake();

        $showcase = Showcase::factory()->published()->create();

        // Archiving deactivates as part of its own work and announces itself.
        (new ArchivedStatus($showcase, $this->createUser()))->handle([]);

        $this->assertFalse($showcase->refresh()->is_active);

        Queue::assertNotPushed(
            SendNotificationJob::class,
            fn (SendNotificationJob $job) => $job->eventName === SystemEventSlugEnum::ToggleActiveShowcase->value,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function seedCatalogue(): void
    {
        $this->seed(NotificationDatabaseSeeder::class);
    }
}
