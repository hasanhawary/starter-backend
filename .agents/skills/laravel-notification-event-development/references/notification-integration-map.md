# Notification Integration Map

Read the live files relevant to the requested event; not every event needs every entry.

## Catalogue and Persistence

- `Modules/Notification/app/Enum/SystemEventSlugEnum.php`: allowed business-event identifiers.
- `Modules/Notification/app/Enum/SystemEventModuleEnum.php`: supported event domains.
- `Modules/Notification/database/seeders/data/system_events.json`: explicit bilingual event definitions and model types.
- `Modules/Notification/database/seeders/NotificationSystemEventSeeder.php`: enum backfill, exclusions, and idempotent persistence.
- `Modules/Notification/database/seeders/data/variables.json`: variable catalogue.
- `Modules/Notification/database/seeders/data/system_event_variables.json`: global and event-specific exclusions.
- `Modules/Notification/database/seeders/SystemEventVariableSeeder.php`: variable attachment rules.
- `Modules/Notification/database/seeders/data/receivers.json`: relation-based recipients.
- `Modules/Notification/database/seeders/data/verifiable_dates.json`: reminder/calendar date paths.
- `Modules/Notification/database/seeders/data/default_notification_event_templates.json` and `DefaultNotificationEventTemplatesSeeder.php`: default channel templates and reminder settings.

## Runtime

- `Modules/Notification/app/Tools/NotificationManager.php`: public facade target.
- `Modules/Notification/app/Tools/Services/Notification/NotificationEventService.php`: queued and scheduled delivery orchestration.
- `Modules/Notification/app/Tools/Services/Notification/VariableResolver.php`: translations, enum labels, booleans, dates, and relation paths.
- `Modules/Notification/app/Tools/Factory/NotificationChannelFactory.php` and `app/Tools/Channels/*`: delivery channel ownership.
- `Modules/Notification/app/Jobs/SendNotificationJob.php`: queue, retry, and after-commit behavior.
- `Modules/Notification/Providers/NotificationServiceProvider.php`: bindings, schedules, and lifecycle synchronization.

## Business Dispatch Reference

Search for `Notification::send` using the chosen slug. Read the complete surrounding write transaction and any Strategy/Step class. The call site is correct only when the passed model has reached the state whose variables and recipients the event definition expects.
