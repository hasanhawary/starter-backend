---
name: laravel-notification-event-development
description: Add, change, debug, or review a backend business event integrated with the repository's Notification module. Use for system-event slugs, variables, recipients, templates, channels, reminders, schedules, notification dispatch points, or queued delivery.
---

# Laravel Notification Event Development

Treat a notification event as one cross-cutting contract from the business action through persisted configuration to queued delivery.

## Authority and Discovery

- Follow the root `AGENTS.md`, and the owning workflow skill.
- Read [the notification integration map](references/notification-integration-map.md) before adding or structurally changing an event.
- Inspect the business action and transaction boundary first, then search the Notification module for the event slug, module/model mapping, variables, receivers, templates, reminders, schedules, translations, seeders, and tests.
- Reuse an existing event when it represents the same business moment. Do not create near-identical per-stage events when one stable event plus variables can express the transition.

## Event Contract

- Add only genuine notifiable business actions to `SystemEventSlugEnum`. Keep stable snake-case backing values and do not add delete events when the module intentionally excludes them.
- Ensure the system event's module and `model_type` match the exact Eloquent model passed at the dispatch point; the delivery service rejects mismatches.
- Define bilingual user-facing event names and templates. Do not accept auto-generated placeholder Arabic copy as a finished production translation.
- Expose only variables that exist at the moment the event fires. Validate every access key against real attributes, casts, translatable fields, enums, and active relations.
- Update event-specific variable exclusions when later-stage data is unavailable or sensitive/noisy fields should not enter messages.
- Add receiver relations and verifiable dates only when the event actually needs them. A reminder/date entry must point to a real casted date or datetime path and preserve relation semantics.
- Keep seeders idempotent and additive where administrator-authored configuration must survive. Never wipe templates, links, or recipients without confirming that replacement is the intended contract.

## Dispatch and Delivery

- Dispatch from the domain action or workflow service that owns the successful state change, not from a Resource or validation layer.
- When the event originates inside a database transaction, ensure queued/external delivery occurs after commit. Reuse the current queued `SendNotificationJob` path instead of sending network traffic inside the transaction.
- Preserve per-channel responsibilities in the existing factory/channel classes. Do not add a channel branch in controllers or business strategies.
- Keep recipient resolution permission- and relation-aware. Deduplicate recipients and avoid N+1 queries or repeated variable resolution per template.
- Preserve scheduled-reminder idempotency, overlap protection, channel narrowing, and the dispatch ledger. Do not let the instant path accidentally send scheduled-only reminders or vice versa.
- Log delivery failures without exposing message content, credentials, or recipient secrets.

## Verification

- Test event lookup, model compatibility, selected variables, bilingual replacement, recipients, channel selection, queue dispatch, after-commit behavior, missing/inactive events, and failed channels as applicable.
- For reminders, test date offsets, related-model dates, inactive dates, deduplication, and repeated scheduler runs.
- Run the smallest relevant seeder/feature/unit tests without destroying administrator-authored data.
- Finish with Pint on only authorized changed PHP files and EA/PhpStorm inspections when available, then verify every enum/JSON/seeder/dispatch reference uses the same slug.
