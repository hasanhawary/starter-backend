---
name: status-pattern
description: "Implement or review Laravel status workflows using the Wakeb Legal Strategy pattern across Form Request validation, transition authorization, handlers, and resource buttons."
license: MIT
metadata:
  pack: laravel-boost-ai-pack
  reference: wakeb-legal/Modules/Statement
---

# Status Strategy Pattern

Follow the real Wakeb Legal Statement Strategy pattern. The same factory and concrete strategies supply request rules, target-action policy, execution, and current-state resource buttons. Do not replace it with a controller switch, a static request containing all status fields, or a generic update permission.

Read [the workflow reference](references/wakeb-legal-statement.md) before implementing. It maps every strategy, transition, payload, side effect, and source caveat. [The snapshot index](references/snapshot-index.md) links full, unmodified source files captured from `D:\laragon\www\wakeb-legal` on 2026-09-16; [the manifest](references/snapshot-manifest.json) records paths and SHA-256 hashes. Snapshots use `.php.txt` to remain reference material, not executable application code.

## Required architecture

- `Tools/Status/StatementStatus.php`: an abstract strategy base with optional model/actor constructor context, getters, `handle(?array $params = []): void`, `policy(): bool`, `validateRules(): array`, and `buttons(): array`. It also owns shared notification delegation. This reference uses an abstract class, not the former Request-status interface.
- `StatementStatusFactory::guess(int|string $status, ?Model $model = null, ?User $user = null)`: one strict `match` mapping scalar enum values to concrete strategies. Unknown values throw; no successful no-op default. Pass backed enum values, not enum objects.
- `StatementStatusContext`: delegates execution, validation, and policy, and filters candidate buttons using each **target** strategy's policy with the same model and actor. Preserve the project's explicit root bypass when adapting this workflow.
- `Strategies/*Status.php`: each target owns its validation, authorization, mutation/logging/side effects, and candidate buttons for when that status is current. Keep nontrivial shared copying/assignment logic in `StatementService`.

## Form Request

Resolve the requested **target** through the factory and context's `validateRules()`. Merge shared discriminator/notes rules first, then target rules so required target notes override nullable shared notes. Keep translations, `attributes()`, and `messages()` in the request: this strategy contract has no `messages()` method. The snapshot constructs validation strategies without model or actor; rules must work in that mode.

All request-field and transition-prerequisite validation belongs to the Form Request, delegated through the target strategy's `validateRules()` when target-specific. A strategy `handle()` must only mutate state and dispatch its logging/side effects; it must never call `Validator`, perform imperative input checks, or throw `ValidationException` for field-level 422 errors. When target rules depend on persisted route state, resolve the validation strategy with the route-bound model (and actor when needed) and express the checks as declarative or focused custom rules. Keep validation-only construction without a model safe for factory/resource callers.

Validate scalar type and supported actionable status before calling the strict factory in new implementations. The snapshot resolves inside `rules()` before Laravel validates `status`; missing, array, unknown, or enum-valid but unmapped `draft` input can throw instead of producing 422. Preserve the architecture without reproducing this gap; do not add a permissive fallback.

## Authorization and execution

`StatementController::takeAction` opens `DB::transaction`, reloads the bound record with `lockForUpdate()`, constructs the target strategy with that locked model and authenticated actor, checks `policy()` unless root, and calls `handle()` with request data. It returns the refreshed details resource and `api.action_taken_successfully`. Keep authorization against the locked current state before mutation.

Distinguish the two policy responsibilities accurately:

- `StatementPolicy` covers CRUD, assignment, ownership, delegation, and resource `can_update`/`can_delete` flags. It does **not** resolve the status factory and has no `takeAction` method in this snapshot.
- Concrete strategies' `policy()` methods authorize transitions; the controller and context consume them. If the destination project requires a Gate transition method, make it delegate to this same strategy policy using model, actor, and target; do not duplicate its transition rules.

Capture the previous enum status, mutate, then use the model's `log()` helper with the action type, delimiter message, and optional notes. Keep database effects under the controller transaction. `SentPendingStatus` deliberately logs null as the old status; redirects create a new statement with its own lifecycle logs as well as an old-statement action link. Do not impose a global one-log-only rule.

Notifications and uploads occur in the reference handlers; a database rollback does not prove those external effects are reversible. Inspect the destination delivery/upload behavior when adapting, and use its commit/cleanup conventions.

## Resource

Resolve the **current** status to obtain candidate `buttons()`. For each candidate `key`, the context resolves the **target** strategy and filters by its policy; root gets all candidates. Keep the contract `key`, translated `label`, `type: modal` and reindex filtered results. Buttons inform presentation; execution still authorizes independently.

`StatementResource` includes buttons only in `details`, skips Draft, and returns no buttons for a missing status. Summary and details both include CRUD Gate action flags. Use `whenLoaded()` for related projections and preserve the existing response contract.

## Implementation and verification

1. Inspect the destination enum/casts, request base, route/auth middleware, policies, resources, model log relationships, notification integration, and existing workflow tests. Use the snapshots for structure, not to import Statement-specific permissions into another domain.
2. Define actionable targets and their allowed source states, actor requirements, payloads, and effects. Keep the factory as the sole construction map; current-state buttons are not an authorization allowlist.
3. Add or adapt the abstract base, factory, context, concrete strategies, request delegation, locked execution, and resource delegation together. Add a Gate wrapper only if the destination uses it.
4. Verify invalid/missing/array/unmapped target input returns 422; required target fields; unauthorized users and invalid source states; root/delegated behavior; successful effects and logs; rollback of database effects; stale/concurrent transitions; and button visibility matching target policies.
5. Test each real transition and rerun relevant existing tests. Check translations and eager loading. Report source limitations separately from tested destination behavior.

Do not reintroduce the old fikrah-gadd Request workflow, `rules()`/`messages()` strategy interface, or pre-transaction authorization as if they were this reference. Do not silently fix the frozen snapshots; document adaptations in the destination implementation.
