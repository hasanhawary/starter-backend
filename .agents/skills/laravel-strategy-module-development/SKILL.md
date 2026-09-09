---
name: laravel-strategy-module-development
description: Create or materially extend a Laravel module using this repository's Delegation-style Strategy Pattern. Use when the user explicitly requests a Strategy Pattern module, a status or step workflow matching Delegation, or the Tools/Context/Factory/Strategies structure; do not activate for ordinary CRUD or a single conditional branch.
---

# Laravel Strategy Module Development

Build workflow modules around the repository's established `Tools` pattern while preserving thin controllers, explicit transitions, and atomic writes.

## Activation and Authority

- Apply this skill only when the user explicitly requests the Strategy Pattern or when the target module already uses this pattern. Do not introduce it merely because a method contains a `match`, status check, or a small number of branches.
- Follow the root `AGENTS.md` first. Apply the controller, model, and enum skills whenever their files are affected.
- Read [the canonical Delegation-style example](references/delegation-strategy-example.md) before creating the module or changing the workflow structure.
- Inspect the live Delegation reference before editing: its controller, action request, resource, service, status enum, `Tools/Status` tree, policy, scopes, provider, routes, and workflow consumers such as commands.
- Treat Delegation strictly as the newest reference implementation, not as a base module or runtime dependency. A target module must not import Delegation domain classes, configuration, registry, ownership rules, permissions, statuses, or traits merely to reuse its structure.
- Use the same pattern family visible in Delegation, Lawsuit, and Statement, but treat Delegation as the requested architectural authority. Copy responsibilities and naming structure, never its permissions, statuses, assignments, notifications, or entity registry into another domain.
- Follow the live Delegation controller's placement of `DB::transaction()` and its direct Factory/Context orchestration. The transaction boundary belongs to the controller action, not to a service.

## Module Shape

- Follow the existing Nwidart module layout and the module's canonical `Modules\\{Module}\\App\\...` namespace casing. Register the module provider in `module.json` and its PSR-4 namespace in `composer.json` without adding duplicate casing aliases.
- Keep workflow classes under `app/Tools/{Axis}`. For a status workflow use `{Module}Status`, `{Module}StatusContext`, `{Module}StatusFactory`, and `Strategies/*Status` exactly as the Delegation family does.
- Keep the status enum, model, Form Requests, Resources, controller, service, Policy, filters, scopes, providers, routes, migrations, translations, and focused tests inside the module when they belong to that module.
- Add `Tools/Export`, `Tools/Report`, registries, aggregators, observers, commands, or schedules only when the requested module actually has those responsibilities. Empty extension points are not parity.
- Prefer the existing module generator and Artisan makers when available, then adapt the generated files to the inspected module conventions.

## Strategy Contract

- Use a string-backed status or step enum and persist it through a string database column, following `.agents/skills/laravel-enum-development/SKILL.md`.
- The abstract status class owns the shared model and actor context and defines `handle()`, `policy()`, `validateRules()`, and `buttons()`. Keep shared notification or logging hooks there only when every concrete strategy uses the same lifecycle.
- The Context holds one selected strategy and delegates the four operations. Centralize cross-strategy behavior such as the root bypass and filtering visible buttons through the target strategy's `policy()`.
- The Factory is the single status-to-strategy map. Map every supported workflow value explicitly and fail loudly for an unmapped value; do not scatter class selection across controllers, requests, resources, commands, or services.
- Each concrete strategy represents one target transition. It owns only that transition's state mutation, eligibility rules, validation additions, log/event data, next buttons, and related writes.
- `buttons()` describes outgoing target statuses from the strategy representing the current state. The Context resolves each target through the Factory and exposes only targets whose strategy policy allows the actor.
- Pass the model and actor into strategies. Use an explicit nullable actor for system-initiated transitions. Do not make a strategy depend on an unrelated global request, and prefer the injected actor over repeated `auth()` lookups.

## Application Flow

- Validate the raw enum selector before invoking the Factory. Invalid user input must produce a validation response, not an `InvalidArgumentException` or a server error from the Factory.
- Let the action Form Request compose its base rules with the selected strategy's `validateRules()`. A strategy must not replace the selector's base enum rule.
- Keep general resource authorization in the Laravel Policy/Gate layer. Treat a strategy's `policy()` as transition eligibility and enforce it immediately before `handle()` as a domain invariant.
- The controller accepts the validated request, performs the general Gate check, opens `DB::transaction()`, resolves the strategy through the Factory and Context, executes it, and returns the Resource. `Modules/Delegation/app/Http/Controllers/DelegationController::takeAction()` is the shape to copy.
- Do not introduce a service whose methods mirror the controller's actions — a `create()` / `update()` / `changeStep()` trio that only wraps the Factory and a transaction has no responsibility of its own and degenerates into a pass-through layer. A service earns its place only when it owns a cohesive operation that is more than "what this endpoint does": several coordinated writes, row locking against a real race, after-commit work, or a second entry point such as a command or job.
- When such a service does exist, reuse its entry point for HTTP actions, commands, scheduled transitions, and jobs so they do not bypass locking, policy semantics, logging, or atomicity.
- Dispatch notifications and other external effects after commit. `Notification::send()` already satisfies this: it dispatches `SendNotificationJob`, whose constructor calls `$this->afterCommit()`, so calling it from inside a strategy is correct and needs no extra deferral. Avoid changing the application's global locale as a hidden strategy side effect; pass locale/context explicitly or restore it safely.
- Let the Resource use the Context and Factory only to derive permitted next buttons. Do not execute transitions or database writes while serializing.

## Extension and Change Discipline

- Adding a status normally requires one enum case, one concrete strategy, one Factory mapping, predecessor button updates, translations, and tests. Update other layers only when their contracts actually change.
- Keep workflow graph rules in strategies rather than duplicating them in controllers, Policies, Requests, Resources, commands, or frontend conditionals.
- Use configuration-driven registries only when the module genuinely supports multiple host model types as Delegation does. A single-domain workflow does not need `DelegatableRegistry` or an aggregator.
- Do not turn shared base strategies into a grab bag of domain services. Extract a focused collaborator when complex logic is reused or independently testable.

## Verification

- Test every Factory mapping and the unknown-value failure.
- Test each transition from allowed and forbidden source states, allowed and forbidden actors, its dynamic validation rules, writes, logs/events, and next-button visibility.
- Test validation of an invalid selector before Factory resolution, transaction rollback after a later write fails, and concurrency protection for race-prone transitions.
- Test the controller endpoint, Policy/Gate boundary, Resource buttons, and non-HTTP workflow consumers through the same service entry point.
- Verify module discovery, provider registration, route loading, translations, migrations, and scheduled commands only where used.
- Run focused PHPUnit tests, the affected route listing, module discovery when configuration changes, and the backend-required formatter after PHP edits; then review the complete module diff for duplicated workflow rules.
