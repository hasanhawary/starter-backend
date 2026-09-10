<?php

namespace Tests\Feature\Notification;

use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Enum\NotificationChannelEnum;
use Modules\Notification\app\Enum\SystemEventModuleEnum;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\NotificationReceiver;
use Modules\Notification\app\Models\NotificationVerifiableDate;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Models\Variable;
use Modules\Notification\database\seeders\NotificationDatabaseSeeder;
use Modules\Notification\database\seeders\SystemEventVariableSeeder;
use Tests\TestCase;

/**
 * The catalogue covers exactly one module — country — and seeding it is what
 * removes anything left over from a module that is no longer in it.
 */
class NotificationCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SystemEventVariableSeeder::flushCache();
    }

    public function test_country_is_the_only_module_in_the_catalogue(): void
    {
        $this->assertSame(
            ['country'],
            array_map(fn (SystemEventModuleEnum $module) => $module->value, SystemEventModuleEnum::cases()),
        );

        $this->assertSame(
            ['create_country', 'toggle_active_country', 'update_country'],
            collect(SystemEventSlugEnum::cases())->map->value->sort()->values()->all(),
        );
    }

    public function test_seeding_creates_every_country_event_against_the_country_model(): void
    {
        $this->seedCatalogue();

        $events = SystemEvent::query()->get();

        $this->assertSame(
            ['create_country', 'toggle_active_country', 'update_country'],
            $events->map(fn (SystemEvent $event) => $this->slugOf($event))->sort()->values()->all(),
        );

        foreach ($events as $event) {
            $this->assertSame('country', $this->moduleOf($event));
            $this->assertSame(Country::class, $event->model_type);
            $this->assertNotSame('', (string) ($event->getTranslations('name')['ar'] ?? ''));
            $this->assertNotSame('', (string) ($event->getTranslations('name')['en'] ?? ''));
        }
    }

    public function test_seeding_drops_events_of_a_module_that_left_the_catalogue(): void
    {
        // Written through the query builder on purpose: `module` and `event_slug` are
        // enum-cast, so a row of a module the catalogue has dropped is exactly the row
        // Eloquent can no longer produce — only an older enum could have left it here.
        $staleId = DB::table('system_events')->insertGetId([
            'module' => 'cause',
            'event_slug' => 'create_cause',
            'model_type' => 'App\\Models\\Cause',
            'name' => json_encode(['en' => 'Create Cause', 'ar' => 'إنشاء قضية'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $staleNotificationEventId = DB::table('notification_events')->insertGetId([
            'system_event_id' => $staleId,
            'name' => json_encode(['en' => 'Create Cause Notification', 'ar' => 'إشعار إنشاء قضية'], JSON_UNESCAPED_UNICODE),
            'is_reminder' => false,
            'type' => 'notification',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedCatalogue();

        $this->assertDatabaseMissing('system_events', ['id' => $staleId]);
        $this->assertDatabaseMissing('notification_events', ['id' => $staleNotificationEventId]);
    }

    public function test_seeding_drops_variables_receivers_and_dates_left_by_another_module(): void
    {
        $staleVariable = Variable::query()->create([
            'module' => 'cause',
            'model_type' => 'App\Models\Cause',
            'access_key' => 'cause_number',
            'type' => 'column',
            'name' => ['en' => 'Cause Number', 'ar' => 'رقم القضية'],
        ]);

        $staleReceiver = NotificationReceiver::query()->create([
            'module' => 'cause',
            'type' => 'relation',
            'relation' => 'team',
            'name' => ['en' => 'Cause Team', 'ar' => 'فريق القضية'],
        ]);

        $staleDate = NotificationVerifiableDate::query()->create([
            'module' => 'cause',
            'model_type' => 'App\Models\Cause',
            'access_key' => 'date',
            'type' => 'date',
            'name' => ['en' => 'Cause Date', 'ar' => 'تاريخ القضية'],
        ]);

        $this->seedCatalogue();

        $this->assertDatabaseMissing('variables', ['id' => $staleVariable->id]);
        $this->assertDatabaseMissing('notification_receivers', ['id' => $staleReceiver->id]);
        $this->assertDatabaseMissing('notification_verifiable_dates', ['id' => $staleDate->id]);

        $this->assertSame(['country'], Variable::query()->distinct()->pluck('module')->all());
        $this->assertSame(['country'], NotificationReceiver::query()->distinct()->pluck('module')->all());
    }

    public function test_country_events_expose_the_country_columns_and_never_the_flag(): void
    {
        $this->seedCatalogue();

        $event = $this->systemEvent('create_country');
        $accessKeys = $event->variables()->pluck('access_key')->sort()->values()->all();

        $this->assertSame(
            ['code', 'created_at', 'is_active', 'name', 'nationality', 'phone_code', 'phone_length'],
            $accessKeys,
        );

        $this->assertNotContains('flag', $accessKeys);
        $this->assertTrue(Variable::query()->where('access_key', 'flag')->exists());
    }

    public function test_written_country_copy_is_bilingual_and_addressed_by_variable_id(): void
    {
        $this->seedCatalogue();

        $event = $this->systemEvent('toggle_active_country');
        $template = $event->notificationEvents()->first()
            ->templates()
            ->where('channel', NotificationChannelEnum::Notification->value)
            ->firstOrFail();

        $nameId = $event->variables()->where('access_key', 'name')->value('variables.id');
        $isActiveId = $event->variables()->where('access_key', 'is_active')->value('variables.id');

        $this->assertStringContainsString('تغيير تفعيل الدولة {{'.$nameId.'}}', $template->getTranslations('title')['ar']);
        $this->assertStringContainsString('Activation changed for {{'.$nameId.'}}', $template->getTranslations('title')['en']);
        $this->assertStringContainsString('{{'.$isActiveId.'}}', $template->getTranslations('body')['ar']);
        $this->assertStringContainsString('{{'.$isActiveId.'}}', $template->getTranslations('body')['en']);

        // Copy is authored against access keys; none may survive un-stamped.
        foreach (['title', 'body'] as $field) {
            foreach ($template->getTranslations($field) as $text) {
                $this->assertStringNotContainsString('{{name}}', $text);
                $this->assertStringNotContainsString('{{is_active}}', $text);
            }
        }
    }

    public function test_country_audience_is_addressable_by_role(): void
    {
        $this->seedCatalogue();

        $receivers = NotificationReceiver::query()->where('module', 'country')->get();

        $this->assertCount(1, $receivers);
        $this->assertSame('role', $receivers->first()->type);
        $this->assertNull($receivers->first()->relation);
    }

    public function test_country_reminders_hang_off_created_at(): void
    {
        $this->seedCatalogue();

        $dates = NotificationVerifiableDate::query()->where('module', 'country')->get();

        $this->assertSame(['created_at'], $dates->pluck('access_key')->all());
        $this->assertSame(Country::class, $dates->first()->model_type);
    }

    public function test_seeding_twice_leaves_the_same_catalogue(): void
    {
        $this->seedCatalogue();

        $first = SystemEvent::query()->pluck('id')->sort()->values()->all();
        $templateIds = NotificationEvent::query()->first()->templates()->pluck('id')->sort()->values()->all();

        $this->seedCatalogue();

        $this->assertSame($first, SystemEvent::query()->pluck('id')->sort()->values()->all());
        $this->assertSame($templateIds, NotificationEvent::query()->first()->templates()->pluck('id')->sort()->values()->all());
        $this->assertSame(3, SystemEvent::query()->count());
    }

    private function seedCatalogue(): void
    {
        SystemEventVariableSeeder::flushCache();

        $this->seed(NotificationDatabaseSeeder::class);
    }

    private function systemEvent(string $slug): SystemEvent
    {
        return SystemEvent::query()->where('event_slug', $slug)->firstOrFail();
    }

    private function slugOf(SystemEvent $event): string
    {
        return $event->event_slug instanceof \BackedEnum ? $event->event_slug->value : (string) $event->event_slug;
    }

    private function moduleOf(SystemEvent $event): string
    {
        return $event->module instanceof \BackedEnum ? $event->module->value : (string) $event->module;
    }
}
