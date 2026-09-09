<?php

namespace Modules\Notification\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Notification\app\Enum\ScheduleEventTypeEnum;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\ScheduleEvent;

class ScheduleEventSeeder extends Seeder
{
    /**
     * Seed a reminder and a calendar schedule event for every notification event,
     * so each system event surfaces on both the reminders feed and the calendar.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command?->warn('No users found. Skipping ScheduleEventSeeder.');

            return;
        }

        $count = 0;

        NotificationEvent::with('systemEvent')->chunkById(100, function ($events) use ($user, &$count) {
            foreach ($events as $event) {
                $systemEvent = $event->systemEvent;

                if (! $systemEvent) {
                    continue;
                }

                $enName = $systemEvent->name['en'] ?? Str::headline($systemEvent->event_slug->value);
                $arName = $systemEvent->name['ar'] ?? ('إشعار '.$systemEvent->event_slug->value);

                foreach ($this->scheduleTypes() as $type => $config) {
                    ScheduleEvent::updateOrCreate(
                        [
                            'notification_event_id' => $event->id,
                            'type' => $type,
                            'receiver_type' => User::class,
                            'receiver_id' => $user->id,
                        ],
                        [
                            'title' => [
                                'en' => $config['title_prefix']['en'].$enName,
                                'ar' => $config['title_prefix']['ar'].$arName,
                            ],
                            'body' => [
                                'en' => $config['body']['en'],
                                'ar' => $config['body']['ar'],
                            ],
                            'date_time' => now()->addDays($config['offset_days'])->startOfHour(),
                            'status' => 'pending',
                            'source_type' => $systemEvent->model_type,
                            'source_id' => null,
                        ]
                    );

                    $count++;
                }
            }
        });

        $this->command?->info("ScheduleEventSeeder completed successfully. Seeded {$count} schedule events.");
    }

    /**
     * @return array<string, array{title_prefix: array{en: string, ar: string}, body: array{en: string, ar: string}, offset_days: int}>
     */
    private function scheduleTypes(): array
    {
        return [
            ScheduleEventTypeEnum::Reminder->value => [
                'title_prefix' => ['en' => 'Reminder: ', 'ar' => 'تذكير: '],
                'body' => [
                    'en' => 'This is a scheduled reminder for this event.',
                    'ar' => 'هذا تذكير مجدول لهذا الحدث.',
                ],
                'offset_days' => 1,
            ],
            ScheduleEventTypeEnum::Calendar->value => [
                'title_prefix' => ['en' => 'Calendar: ', 'ar' => 'التقويم: '],
                'body' => [
                    'en' => 'This event is scheduled on the calendar.',
                    'ar' => 'هذا الحدث مجدول في التقويم.',
                ],
                'offset_days' => 2,
            ],
        ];
    }
}
