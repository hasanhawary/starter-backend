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

    // Showcase Events
    //
    // Publishing is its own moment rather than a variant of `update_showcase`:
    // it is the transition the audience is waiting for, and it is the only one
    // that guarantees `published_at` holds a value. Deleting and restoring a
    // showcase stay unnotifiable, matching the country rule above.
    case CreateShowcase = 'create_showcase';
    case UpdateShowcase = 'update_showcase';
    case PublishShowcase = 'publish_showcase';
    case ToggleActiveShowcase = 'toggle_active_showcase';
}
