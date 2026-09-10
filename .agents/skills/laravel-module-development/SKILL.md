---
name: laravel-module-development
description: Create, materially extend, or review an Nwidart Laravel module in this project. Use for module boundaries, structure, providers, config, routes, discovery, namespaces, or cross-module wiring. This skill does not imply a Strategy Pattern.
---

# Laravel Module Development

Build the smallest coherent module that follows the repository's current module structure without treating any example module as a framework dependency.

## Authority and Reference

- Follow the root `AGENTS.md`.
- Read [the current module structure reference](references/delegation-module-structure.md) before creating a module or materially changing its wiring.
- `Delegation` is the newest structural reference in this repository. It demonstrates placement and wiring only; it is not a base module and other modules do not depend on it by default.
- Apply `.agents/skills/laravel-strategy-module-development/SKILL.md` only when the user requests a Strategy Pattern or the target already owns such a workflow.
- When moving an existing app feature into a module, also apply the backend-local `feature-modularization` skill. That migration workflow is distinct from ordinary module creation or extension.

## Discovery and Boundary

- Inspect `composer.json`, `modules_statuses.json`, the target `module.json`, provider, routes, config, and the closest live module before designing the change.
- Search the app and every module for existing ownership, duplicate implementations, route names, bindings, config keys, model morph aliases, contracts, and consumers.
- Keep feature-owned models, requests, resources, policies, scopes, filters, services, rules, events, jobs, commands, and tools inside the owning module.
- Keep genuinely shared application models and cross-domain infrastructure outside the module. Use established config indirection for shared class references when the surrounding modules do so.
- Avoid circular module dependencies. Do not move a class merely because the new module calls it.
- Preserve public routes, payloads, morph types, table names, config keys, and serialized class names unless the task explicitly changes them.

## Module Shape

- Use the repository's `Modules/{Module}` layout and the canonical `Modules\{Module}\App\...` namespace casing for new module PHP classes. Preserve compatible existing casing while changing legacy modules unless normalization is explicitly in scope.
- Register the real service provider in `module.json`, keep PSR-4 discovery coherent, and enable a new module in `modules_statuses.json` only after its runtime wiring exists.
- The provider registers only responsibilities the module owns: bindings, configuration, routes, migrations, translations, policies, observers, events, commands, schedules, report/export integration, or morph maps as applicable.
- Create only the directories and extension points the current feature needs. Do not copy empty providers, views, assets, commands, reports, exports, schedules, or registries for visual parity.
- Keep controllers as HTTP orchestration and delegate atomic mutations to cohesive services under the controller skill. When the module needs a service, follow the Statement shape documented there: one `XService` per module, `DB::transaction()` in the controller action, domain-named service methods rather than one method per endpoint.
- Keep configuration declarative. Do not place request-dependent business logic or mutable runtime state in config files.
- Register scheduled work and side effects deliberately: make repeatable commands idempotent, prevent unsafe overlap where required, and dispatch external effects after commit.

## Verification

- Verify module discovery, provider boot, config merge, route loading and names, translations, policy registration, migrations, and bindings only for the capabilities changed.
- Run focused PHPUnit tests and the relevant `php artisan route:list` or module discovery command.
- Finish with the PHP quality gate in the root `AGENTS.md`: Laravel Pint on only the authorized changed PHP files, followed by EA/PhpStorm inspections when available.
- Review the complete diff for accidental app/module duplication, circular dependencies, copied Delegation domain rules, and unrelated scaffolding.
