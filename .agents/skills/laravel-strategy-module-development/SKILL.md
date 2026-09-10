---
name: laravel-strategy-module-development
description: Create or materially extend a Laravel module using this repository's Statement-style Strategy Pattern. Use when the user explicitly requests a Strategy Pattern module, a status or step workflow matching Statement, Delegation, or Lawsuit, or the Tools/Context/Factory/Strategies structure; do not activate for ordinary CRUD or a single conditional branch.
---

# Laravel Strategy Module Development

Build workflow modules around the repository's established `Tools` pattern while preserving thin controllers, explicit transitions, and atomic writes.

## Activation and Authority

- Apply this skill only when the user explicitly requests the Strategy Pattern or when the target module already uses this pattern. Do not introduce it merely because a method contains a `match`, status check, or a small number of branches.
- Follow the root `AGENTS.md` first. Apply the controller, model, and enum skills whenever their files are affected.
- Read [the canonical Statement-style example](references/statement-strategy-example.md) before creating the module or changing the workflow structure.
- `Modules/Statement` is the architectural authority for this pattern. Inspect it live before editing: its controller, action request, resource, status enum, log type enum, `Tools/Status` tree, policy, scopes, provider, routes, and workflow consumers such as commands. `Modules/Delegation` and `Modules/Lawsuit` implement the same shape and are useful as corroboration.
- Prefer Statement over Delegation as the template. Delegation carries module-specific machinery — the delegatable registry, morph assignments, entity configuration — that a single-domain workflow must not copy.
- Treat these modules strictly as reference implementations, not as base modules or runtime dependencies. A target module must not import their domain classes, configuration, registry, ownership rules, permissions, statuses, or traits merely to reuse the structure.
- Copy responsibilities and naming structure only. Never copy their permissions, statuses, assignments, notifications, or entity registry into another domain.
- Follow `StatementController::takeAction()` for the placement of `DB::transaction()` and its direct Factory/Context orchestration. The transaction boundary belongs to the controller action.

## Module Shape

- Follow the existing Nwidart module layout and the module's canonical `Modules\\{Module}\\App\\...` namespace casing. Register the module provider in `module.json` and its PSR-4 namespace in `composer.json` without adding duplicate casing aliases.
- Keep workflow classes under `app/Tools/{Axis}`. For a status workflow use `{Module}Status`, `{Module}StatusContext`, `{Module}StatusFactory`, and `Strategies/*Status` exactly as the Statement family does.
- Keep the status enum, log type enum, model, Form Requests, Resources, controller, Policy, filters, scopes, providers, routes, migrations, translations, and focused tests inside the module when they belong to that module.
- `app/Services/` is not part of this pattern. A workflow module ships without one unless it has a separate non-workflow responsibility to own — see *Service Only When the Code Needs One*.
- Add `Tools/Export`, `Tools/Report`, registries, aggregators, observers, commands, or schedules only when the requested module actually has those responsibilities. Empty extension points are not parity.
- Prefer the existing module generator and Artisan makers when available, then adapt the generated files to the inspected module conventions.

## Strategy Contract

- Use a string-backed status or step enum and persist it through a string database column, following `.agents/skills/laravel-enum-development/SKILL.md`.
- The abstract status class owns the shared model and actor context and defines `handle()`, `policy()`, `validateRules()`, and `buttons()`. Keep shared notification or logging hooks there only when every concrete strategy uses the same lifecycle.
- The Context holds one selected strategy and delegates the four operations. Centralize cross-strategy behavior such as the root bypass and filtering visible buttons through the target strategy's `policy()`.
- The Factory is a single static `guess(int|string $status, ?Model $model = null, ?User $user = null)` holding one `match`. Map every supported workflow value explicitly and throw `InvalidArgumentException` for an unmapped value; do not scatter class selection across controllers, requests, resources, commands, or services. A status nothing transitions to, such as `Draft`, needs no strategy — short-circuit it at the call site instead.
- Each concrete strategy represents one target transition. It owns only that transition's state mutation, eligibility rules, validation additions, log/event data, next buttons, and related writes. Writes belonging to that one transition — creating a reply, storing its files — stay in a private helper on the strategy itself, as `AnsweredStatus::syncReplayFiles()` does. Do not push them into a service.
- `handle()` captures the old status before the update, writes the new status, records the log through the model's own `log($oldStatus, $type, $message, $notes)` method, then calls `handleNotifications()` last. `policy()` stays a pure null-safe predicate over the model and actor that returns `false` for any unlisted source state.
- `buttons()` describes outgoing target statuses from the strategy representing the current state. The Context resolves each target through the Factory and exposes only targets whose strategy policy allows the actor.
- Pass the model and actor into strategies. Use an explicit nullable actor for system-initiated transitions. Do not make a strategy depend on an unrelated global request, and prefer the injected actor over repeated `auth()` lookups.

## Application Flow

- Validate the raw enum selector before invoking the Factory. Invalid user input must produce a validation response, not an `InvalidArgumentException` or a server error from the Factory.
- Let the action Form Request compose its base rules with the selected strategy's `validateRules()`. A strategy must not replace the selector's base enum rule.
- Keep general resource authorization in the Laravel Policy/Gate layer. Treat a strategy's `policy()` as transition eligibility and enforce it immediately before `handle()` as a domain invariant.
- The controller accepts the validated request, performs the general Gate check where the endpoint needs one, opens `DB::transaction()`, resolves the strategy through the Factory, checks `! isRoot() && ! $statusClass->policy()`, executes `handle()`, and returns the Resource from inside the transaction closure. `Modules/Statement/app/Http/Controllers/StatementController::takeAction()` is the shape to copy.
- Re-read the row with `lockForUpdate()->firstOrFail()` inside the transaction when the transition is race-prone, as `takeAction()` does.
- Do not introduce a service to hold the transition. The strategies already own the transitions and the controller already owns the transaction; a `transition()`, `changeStep()`, or `create()`/`update()` service that only re-reads the row, resolves the Factory, and opens `DB::transaction()` has no responsibility of its own and is a pass-through layer.
- Dispatch notifications and other external effects after commit. `Notification::send()` already satisfies this: it dispatches `SendNotificationJob`, whose constructor calls `$this->afterCommit()`, so calling it from inside a strategy is correct and needs no extra deferral. Avoid changing the application's global locale as a hidden strategy side effect; pass locale/context explicitly or restore it safely.
- Let the Resource use the Context and Factory only to derive permitted next buttons. Do not execute transitions or database writes while serializing.

## Service Only When the Code Needs One

- Default to no service. A status workflow is not a reason for one: the strategies own the transitions, the model owns its own `log()` and relation methods, and the controller owns the transaction.
- Write a service only when the module has a distinct operation of its own to own — substantial coordinated writes, media handling, notifications, after-commit work, or a second entry point such as a command, job, or MCP tool. `StatementService` exists for exactly that reason: creating and updating the record with its logs, departments, users, files, and lifecycle entry, which is separate work from the transitions in `Tools/Status`.
- Never create a service because the pattern seems to call for one, because the controller action looks long, or to give the module structural parity with another module. An `app/Services/` directory with nothing real to own is not parity.
- When a service is justified, build it to the Statement shape in `.agents/skills/laravel-controller-development/SKILL.md`: injected as `protected XService $service`, domain-named methods such as `saveX()` and `syncX()` rather than one method per controller action, and no `DB::transaction()` of its own.
- When such a service exists, route HTTP actions, commands, scheduled transitions, and jobs through the same entry point so they do not bypass its invariants, logging, or atomicity.

## Extension and Change Discipline

- Adding a status normally requires one enum case, one concrete strategy, one Factory mapping, predecessor button updates, translations, and tests. Update other layers only when their contracts actually change.
- Keep workflow graph rules in strategies rather than duplicating them in controllers, Policies, Requests, Resources, commands, or frontend conditionals.
- Use configuration-driven registries only when the module genuinely supports multiple host model types as Delegation does. A single-domain workflow such as Statement does not need `DelegatableRegistry` or an aggregator.
- Do not turn shared base strategies into a grab bag of domain services. Extract a focused collaborator when complex logic is reused or independently testable.

## Verification

- Test every Factory mapping and the unknown-value failure.
- Test each transition from allowed and forbidden source states, allowed and forbidden actors, its dynamic validation rules, writes, logs/events, and next-button visibility.
- Test validation of an invalid selector before Factory resolution, rollback of the controller transaction after a later write fails, and concurrency protection for race-prone transitions.
- Test the controller endpoint, Policy/Gate boundary, Resource buttons, and non-HTTP workflow consumers such as commands, asserting they reach the same strategies rather than a parallel code path.
- Verify module discovery, provider registration, route loading, translations, migrations, and scheduled commands only where used.
- Run focused PHPUnit tests, the affected route listing, module discovery when configuration changes, and the backend-required formatter after PHP edits; then review the complete module diff for duplicated workflow rules.
