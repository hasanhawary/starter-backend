<?php

namespace Tests\Feature\Showcase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use Modules\Notification\app\Jobs\SendNotificationJob;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusContext;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusFactory;
use Modules\Showcase\app\Tools\Status\Strategies\ArchivedStatus;
use Modules\Showcase\app\Tools\Status\Strategies\DraftStatus;
use Modules\Showcase\app\Tools\Status\Strategies\InReviewStatus;
use Modules\Showcase\app\Tools\Status\Strategies\PublishedStatus;
use Tests\TestCase;

/**
 * The status workflow: the Factory mapping, each transition from an allowed and
 * a forbidden source state, the actor rules, the dynamic validation each
 * strategy adds, and the buttons the Resource advertises.
 *
 * Transition matrix
 * -----------------
 *  draft      → in_review   update-showcase + owns/view-all
 *  in_review  → published   publish-showcase          (notifies, sets published_at)
 *  in_review  → draft       publish-showcase          (notes required, writes a decision note)
 *  in_review  → archived    archive-showcase          (terminal, deactivates)
 *  published  → archived    archive-showcase          (terminal, deactivates)
 */
class ShowcaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private const REVIEWER = ['publish-showcase', 'archive-showcase', 'view-all-showcase'];

    private const AUTHOR = ['update-showcase', 'view-own-showcase'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'en');
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */
    public function test_the_factory_maps_every_status_to_its_strategy(): void
    {
        $expected = [
            ShowcaseStatusEnum::Draft->value => DraftStatus::class,
            ShowcaseStatusEnum::InReview->value => InReviewStatus::class,
            ShowcaseStatusEnum::Published->value => PublishedStatus::class,
            ShowcaseStatusEnum::Archived->value => ArchivedStatus::class,
        ];

        // Every enum case is mapped — a new status cannot be added without one.
        $this->assertSame(
            collect(ShowcaseStatusEnum::cases())->map->value->sort()->values()->all(),
            collect(array_keys($expected))->sort()->values()->all(),
        );

        foreach ($expected as $status => $class) {
            $this->assertInstanceOf($class, ShowcaseStatusFactory::guess($status));
        }
    }

    public function test_the_factory_rejects_an_unmapped_status(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ShowcaseStatusFactory::guess('no_such_status');
    }

    public function test_an_invalid_selector_fails_validation_before_it_reaches_the_factory(): void
    {
        $this->actingAsUserWithPermissions(self::REVIEWER);
        $showcase = Showcase::factory()->create();

        $this->postJson("api/showcases/{$showcase->id}/take-action", ['status' => 'no_such_status'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['status']]);
    }

    /*
    |--------------------------------------------------------------------------
    | draft → in_review
    |--------------------------------------------------------------------------
    */
    public function test_the_author_submits_a_draft_for_review(): void
    {
        $author = $this->createUser();
        $showcase = Showcase::factory()->ownedBy($author->id)->create();
        $reviewer = $this->createUser();

        $this->actingAsUserWithPermissions(self::AUTHOR, $author);

        $response = $this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::InReview->value,
            'reviewer_id' => $reviewer->id,
            'notes' => 'Ready for a look.',
        ]);

        $this->assertSuccessEnvelope($response);

        $showcase->refresh();
        $this->assertSame(ShowcaseStatusEnum::InReview, $showcase->status);
        // The transition claims the record for the reviewer who will decide it.
        $this->assertSame($reviewer->id, $showcase->owner_id);
        $this->assertTransitionLogged($showcase, ShowcaseStatusEnum::Draft, ShowcaseStatusEnum::InReview);
    }

    public function test_a_stranger_cannot_submit_someone_elses_draft(): void
    {
        $showcase = Showcase::factory()->ownedBy($this->createUser()->id)->create();

        $this->actingAsUserWithPermissions(self::AUTHOR);

        $this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::InReview->value,
        ])->assertForbidden();

        $this->assertSame(ShowcaseStatusEnum::Draft, $showcase->refresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | in_review → published
    |--------------------------------------------------------------------------
    */
    public function test_the_reviewer_publishes_a_reviewed_record(): void
    {
        Bus::fake();

        $this->actingAsUserWithPermissions(self::REVIEWER);
        $showcase = Showcase::factory()->create(['status' => ShowcaseStatusEnum::InReview->value]);

        $response = $this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::Published->value,
            'visibility' => ShowcaseVisibilityEnum::Public->value,
        ]);

        $this->assertSuccessEnvelope($response);

        $showcase->refresh();
        $this->assertSame(ShowcaseStatusEnum::Published, $showcase->status);
        $this->assertSame(ShowcaseVisibilityEnum::Public, $showcase->visibility);
        // The observer derives the date from the status the strategy wrote.
        $this->assertNotNull($showcase->published_at);
        $this->assertTransitionLogged($showcase, ShowcaseStatusEnum::InReview, ShowcaseStatusEnum::Published);

        Bus::assertDispatched(SendNotificationJob::class);
    }

    public function test_a_draft_cannot_be_published_directly(): void
    {
        Bus::fake();

        $this->actingAsUserWithPermissions(self::REVIEWER);
        $showcase = Showcase::factory()->create();

        $this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::Published->value,
        ])->assertForbidden();

        $this->assertSame(ShowcaseStatusEnum::Draft, $showcase->refresh()->status);
        Bus::assertNotDispatched(SendNotificationJob::class);
    }

    public function test_an_author_without_the_publish_permission_cannot_publish(): void
    {
        $author = $this->createUser();
        $showcase = Showcase::factory()->ownedBy($author->id)->create(['status' => ShowcaseStatusEnum::InReview->value]);

        $this->actingAsUserWithPermissions(self::AUTHOR, $author);

        $this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::Published->value,
        ])->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | in_review → draft
    |--------------------------------------------------------------------------
    */
    public function test_returning_a_record_requires_a_reason_and_records_it(): void
    {
        $this->actingAsUserWithPermissions(self::REVIEWER);
        $showcase = Showcase::factory()->create(['status' => ShowcaseStatusEnum::InReview->value]);

        // The strategy's own rule makes `notes` required for this transition only.
        $this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::Draft->value,
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['notes']]);

        $response = $this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::Draft->value,
            'notes' => 'The summary needs sources.',
        ]);

        $this->assertSuccessEnvelope($response);
        $this->assertSame(ShowcaseStatusEnum::Draft, $showcase->refresh()->status);
        $this->assertTransitionLogged($showcase, ShowcaseStatusEnum::InReview, ShowcaseStatusEnum::Draft);

        // The reason is kept as a decision note the author can read.
        $this->assertSame(
            ['The summary needs sources.'],
            $showcase->notes()->ofType(ShowcaseNoteTypeEnum::Decision->value)->pluck('body')->all(),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | → archived
    |--------------------------------------------------------------------------
    */
    public function test_archiving_deactivates_the_record_and_is_terminal(): void
    {
        $this->actingAsUserWithPermissions(self::REVIEWER);
        $showcase = Showcase::factory()->published()->create();

        $this->assertSuccessEnvelope($this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => ShowcaseStatusEnum::Archived->value,
        ]));

        $showcase->refresh();
        $this->assertSame(ShowcaseStatusEnum::Archived, $showcase->status);
        $this->assertFalse($showcase->is_active);
        // Leaving `published` clears the date the observer derives.
        $this->assertNull($showcase->published_at);

        $this->assertSame([], $this->buttonsFor($showcase));
    }

    public function test_an_archived_record_accepts_no_further_transition(): void
    {
        $this->actingAsUserWithPermissions(self::REVIEWER);
        $showcase = Showcase::factory()->archived()->create();

        foreach (ShowcaseStatusEnum::cases() as $target) {
            $this->postJson("api/showcases/{$showcase->id}/take-action", [
                'status' => $target->value,
                'notes' => 'Anything.',
            ])->assertForbidden();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | The guard is the Context's, not the controller's
    |--------------------------------------------------------------------------
    */
    public function test_the_context_refuses_a_forbidden_transition_to_any_caller(): void
    {
        $showcase = Showcase::factory()->create();

        // No HTTP request involved: a command or a job hits the same guard.
        $this->actingAsUserWithPermissions(self::AUTHOR);

        $this->expectException(HttpResponseException::class);

        (new ShowcaseStatusContext)
            ->setStatus(ShowcaseStatusFactory::guess(ShowcaseStatusEnum::Published->value, $showcase, auth()->user()))
            ->handle([]);
    }

    public function test_the_context_refusal_leaves_the_record_untouched(): void
    {
        $showcase = Showcase::factory()->create();
        $this->actingAsUserWithPermissions(self::AUTHOR);

        try {
            (new ShowcaseStatusContext)
                ->setStatus(ShowcaseStatusFactory::guess(ShowcaseStatusEnum::Archived->value, $showcase, auth()->user()))
                ->handle([]);
        } catch (HttpResponseException $exception) {
            $this->assertSame(403, $exception->getResponse()->getStatusCode());
        }

        $this->assertSame(ShowcaseStatusEnum::Draft, $showcase->refresh()->status);
        $this->assertSame(0, $showcase->notes()->transitions()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */
    public function test_the_buttons_advertise_exactly_the_transitions_the_actor_may_take(): void
    {
        $reviewer = $this->actingAsUserWithPermissions(self::REVIEWER);
        $showcase = Showcase::factory()->ownedBy($reviewer->id)->create(['status' => ShowcaseStatusEnum::InReview->value]);

        $this->assertEqualsCanonicalizing(
            [
                ShowcaseStatusEnum::Published->value,
                ShowcaseStatusEnum::Draft->value,
                ShowcaseStatusEnum::Archived->value,
            ],
            $this->buttonsFor($showcase),
        );
    }

    public function test_the_buttons_hide_a_transition_the_actor_lacks_the_permission_for(): void
    {
        $author = $this->actingAsUserWithPermissions(self::AUTHOR);
        $showcase = Showcase::factory()->ownedBy($author->id)->create(['status' => ShowcaseStatusEnum::InReview->value]);

        // No publish or archive permission, so only the reviewer's own targets vanish.
        $this->assertSame([], $this->buttonsFor($showcase));
    }

    public function test_the_resource_exposes_the_same_buttons_the_endpoint_accepts(): void
    {
        $author = $this->actingAsUserWithPermissions(self::AUTHOR);
        $showcase = Showcase::factory()->ownedBy($author->id)->create();

        $advertised = $this->getJson("api/showcases/{$showcase->id}")->json('data.buttons.*.key');

        $this->assertSame([ShowcaseStatusEnum::InReview->value], $advertised);

        // What the Resource advertises, the endpoint must accept.
        $this->assertSuccessEnvelope($this->postJson("api/showcases/{$showcase->id}/take-action", [
            'status' => $advertised[0],
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    /**
     * @return array<int, string>
     */
    private function buttonsFor(Showcase $showcase): array
    {
        return collect(
            (new ShowcaseStatusContext)
                ->setStatus(ShowcaseStatusFactory::guess($showcase->status->value, $showcase, auth()->user()))
                ->buttons()
        )->pluck('key')->all();
    }

    private function assertTransitionLogged(Showcase $showcase, ShowcaseStatusEnum $from, ShowcaseStatusEnum $to): void
    {
        $log = $showcase->notes()->transitions()->latest('id')->firstOrFail();

        $this->assertSame($from, $log->from_status);
        $this->assertSame($to, $log->to_status);
        $this->assertSame(auth()->id(), $log->author_id);

        // The body is stored delimiter-encoded and translated when it is read.
        $this->assertStringStartsWith('status_changed|', $log->body);
        $this->assertStringContainsString($to->selfResolve(), transWithParams($log->body, 'showcase::logs'));
    }
}
