<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SystemEventSlugEnum: string
{
    use EnumMethods;

    // Only the business actions that are allowed to send notifications are kept here.
    // Reminder/scheduled events and every other action are intentionally excluded.

    // Country Events
    //
    // Deleting a country is not notifiable, and restoring it is the undo of an action
    // that never notified, so neither has an event. Activating and deactivating both
    // come from the one toggle endpoint and expose an identical variable set, so they
    // are one event whose `is_active` variable carries the state it landed on.
    case CreateCountry = 'create_country';
    case UpdateCountry = 'update_country';
    case ToggleActiveCountry = 'toggle_active_country';
}
