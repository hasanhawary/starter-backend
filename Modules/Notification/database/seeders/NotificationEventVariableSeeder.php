<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Models\NotificationEvent;

class NotificationEventVariableSeeder extends Seeder
{
    /**
     * Sync each notification event with the variables of its system event, through
     * the polymorphic variable_assignments pivot.
     *
     * A notification event is tied to a system event (system_event_id) and inherits
     * exactly the variables that system event exposes — the module/model variables
     * minus the ones that hold no value yet when the event fires (see
     * SystemEventVariableSeeder). Attaching is additive so hand-made links survive,
     * while the variables that no longer belong to the event are detached.
     */
    public function run(): void
    {
        DB::transaction(function () {
            NotificationEvent::with('systemEvent')->chunkById(200, function ($events) {
                foreach ($events as $notificationEvent) {
                    $systemEvent = $notificationEvent->systemEvent;

                    if (! $systemEvent) {
                        continue;
                    }

                    $allowedIds = SystemEventVariableSeeder::variableIdsFor($systemEvent);

                    $staleIds = $notificationEvent->variables()
                        ->pluck('variables.id')
                        ->diff($allowedIds);

                    if ($staleIds->isNotEmpty()) {
                        $notificationEvent->variables()->detach($staleIds->all());
                    }

                    if ($allowedIds->isNotEmpty()) {
                        $notificationEvent->variables()->syncWithoutDetaching($allowedIds->all());
                    }
                }
            });
        });
    }
}
