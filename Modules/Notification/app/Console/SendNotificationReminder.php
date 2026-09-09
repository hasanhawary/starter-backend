<?php

namespace Modules\Notification\app\Console;

use Illuminate\Console\Command;
use Modules\Notification\app\Events\ScheduleNotificationEvent;
use Modules\Notification\app\Models\NotificationEvent;

class SendNotificationReminder extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notification:reminder';

    /**
     * The console command description.
     */
    protected $description = 'Send scheduled notification reminders to users';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $events = NotificationEvent::with(['remindersSetting.verifiableDate', 'systemEvent'])
            ->whereHas('remindersSetting')
            ->whereHas('systemEvent', fn ($query) => $query->active())
            ->get();
        event(new ScheduleNotificationEvent($events));

        $this->info('Reminder notifications dispatched successfully.');
    }
}
