# Delegation Module Structure Reference

Inspect the modules present under `Modules/` for the live structural example. This document identifies responsibilities, not code to copy — the module it was originally written against is not part of this repository.

## Structural Map

- `module.json`: the module identity and actual service provider.
- `composer.json`: module package/autoload metadata where the repository uses it.
- `app/Providers/DelegationServiceProvider.php`: config, translations, migrations, policies, observers, schedules, and optional report/export integration.
- `app/Providers/RouteServiceProvider.php` and `routes/api.php`: route loading, middleware, names, bindings, and endpoint ownership.
- `app/Http/Controllers`: HTTP orchestration and Resources.
- `app/Http/Requests`: payload validation and normalization.
- `app/Http/Resources`: API representation, permissions, and workflow buttons.
- `app/Models`, `app/Scopes`, `app/Filters`, and `app/Policies`: persistence, query boundaries, request filtering, and authorization.
- `app/Services`: cohesive writes and domain operations. One `XService` per module, with `store()` and `update()` as two separate public methods plus domain-named steps such as `publish()` and `manageX()` — never a transaction, and never a relation write (those belong to the model). See the service shape in `.agents/skills/laravel-controller-development/SKILL.md`.
- `app/Tools`: optional domain patterns such as status strategies, reports, and exports.
- `app/Support`, `app/Rules`, `app/Traits`, `app/Observers`, and `app/Console`: specialized responsibilities only when the domain needs them.
- `config`, `database`, and `lang`: module-owned configuration, schema/seed data, and localized user-facing text.

## What Is Not Generic

The following Delegation concepts belong to its domain and must not be copied into an unrelated module merely for parity:

- `DelegatableRegistry` and `DelegatableAggregator`;
- delegation ownership definitions and the `assignedTo` contract;
- delegation permissions, scopes, assignments, observers, schedules, statuses, and notifications;
- `Tools/Status` or any Strategy Pattern structure when the target workflow does not require it;
- reports, exports, commands, or translation-merging helpers that the target module does not use.

Compare at least one other module that shares the target feature's actual capabilities. Delegation decides the current structural baseline; the closest domain decides the concrete behavior.
