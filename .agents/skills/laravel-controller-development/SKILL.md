---
name: laravel-controller-development
description: Create, change, or review Laravel API controllers and endpoint orchestration in this project. Use for controller actions, index queries, scopes, Pipeline filters, authorization, transactional writes, services, lifecycle actions, and controller-size concerns.
---

# Laravel Controller Development

Keep controllers as HTTP orchestration boundaries. Put query policy, domain behavior, and atomic writes in the project layers that own them.

## Authority and References

- Follow the root `AGENTS.md` before this skill.
- Read the root `AGENTS.md`, the nearest controller, request, resource, model scopes, filters, policy, service, routes, and feature tests before editing.
- Read [the canonical controller example](references/controller-example.md) before creating a controller or materially changing its structure.
- For a user-requested Delegation-style Strategy Pattern module, also apply `.agents/skills/laravel-strategy-module-development/SKILL.md`.
- For CRUD work, also read `.claude/skills/crud-resource.md` and its controller reference. For list filtering or authorization, read `.claude/skills/filters.md` or `.claude/skills/permissions.md`.
- For route work, apply `.agents/skills/laravel-route-development/SKILL.md`; treat `.agents/skills/laravel-route-development/SKILL.md` as secondary project evidence when it is relevant.
- Treat large legacy controllers as behavioral evidence, not as structural templates. Use the current simple CRUD controllers as references for direct single-model writes, and service-backed modules such as Delegation only when the target operation has comparable domain complexity.

## Controller Boundary

- Limit actions to HTTP concerns: accept a typed Form Request or route-bound model, invoke action-level authorization, compose a query, perform a simple single-model mutation or delegate a justified non-trivial operation, and format the established Resource response.
- Do not place domain decisions, state transitions, media handling, notification composition, multi-model writes, or reusable payload preparation in a controller. Relation synchronization belongs to the model that owns the relation.
- Do not hide business logic in private controller helpers. Move it to one cohesive existing or domain service; keep reusable query constraints in scopes.
- Constructor work is limited to dependency injection, the required parent constructor, and configuration of inspected reusable controller traits.
- Keep action flow readable without imposing an arbitrary line limit. Branching, loops, multiple writes, or domain terminology inside an action are signals that ownership belongs elsewhere.
- A single validated `create()` or `update()` plus Resource response formatting is acceptable controller orchestration. Do not extract it merely to make the controller shorter.
- Reuse `successResponse()`, `fetchData()`, Resources, Form Requests, and the established controller traits. `HasDeleteMethods` is the canonical delete lifecycle for deletable CRUD controllers; do not reimplement its actions.

## The House CRUD Shape

This is what a normal DataEntry resource looks like. Copy the shape; do not invent a service around it.

```php
public function index(PageRequest $request): JsonResponse
{
    $query = app(Pipeline::class)
        ->send(Sector::query()->with(['creator', 'manager']))
        ->through([SectorFilter::class, JsonNameFilter::class, TrashedFilter::class, OrderByFilter::class])
        ->thenReturn();

    return successResponse(fetchData($query, $request->pageSize, SectorResource::class));
}

public function store(SectorRequest $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $sector = Sector::create($request->safe()->except('city_ids'));
        $sector->syncCities($request->validated('city_ids') ?? []);

        return successResponse(
            new SectorResource($sector->load(['creator', 'manager', 'cities'])),
            __('api.global.created', ['item' => __('api.messages.sector.sector')])
        );
    });
}
```

- One `create()`/`update()` plus one `syncX()` is still controller work. Wrap the pair in `DB::transaction()` so a failed relation write cannot leave a half-saved record, and keep the `successResponse` inside that closure the way the surrounding controllers do.
- Exclude the relation key from the model write with `$request->safe()->except('city_ids')`, and pass the list to the model's own `syncX()`. Never build pivot rows or update children from the controller.
- A relation list that is absent must not clear the relation: pass `?? []` and let the model's empty guard decide. The base request normalizes an empty array to `null`, so "cleared everything" and "sent nothing" are the same payload — say so in the endpoint's documentation instead of inventing a second flag.
- Do not add a service, an action class, a DTO, or a repository for this shape. A service earns its place only with several coordinated writes, media handling, notifications, after-commit work, or a second entry point such as a command or job.
- A status or step transition is not by itself a reason for a service: the strategy already owns it. Keep `DB::transaction()` and the Factory/Context resolution in the controller action, as `.agents/skills/laravel-strategy-module-development/SKILL.md` describes.

## Index Queries: Scopes Before Filters

- Use model scopes for reusable domain selection, visibility, ownership, tenant, lifecycle, or invariant query constraints. Apply the relevant scope before request-controlled filters so a filter cannot widen access.
- Use Pipeline filters for client-selected search, scalar constraints, translated-name search, dates, trashed state, and ordering. Every filterable resource index must use the project Pipeline and reuse global filters before adding a domain filter.
- Keep authorization out of request filters. Align any visibility scope such as `related()` or `canView()` with the corresponding Policy so list and record access cannot disagree.
- Eager-load only relations required by the Resource or the endpoint, then paginate through `fetchData($query, $request->pageSize, Resource::class)`.
- Do not create a no-op scope merely to satisfy a pattern. If the resource has a real visibility or domain boundary, that boundary must be expressed by a named scope rather than duplicated in the controller.

## Authorization Ownership

- Use controller middleware for fixed action permissions that require no model instance or runtime domain context.
- Use `Gate::authorize()` with a Policy for ownership, visibility, protected records, current state, or any other contextual decision. Use the class for collection/create checks and the bound model for record checks.
- Do not duplicate the same rule in middleware and a Policy. When both appear in one controller, give them distinct responsibilities and keep the Policy safe for every caller that relies on it.
- Confirm Policy discovery and registration before relying on a Policy.
- Confirm lifecycle ability names, policy fallback, request IDs, callbacks, and transaction behavior before extending the delete contract.

## Delete Lifecycle: `HasDeleteMethods`

- Every CRUD controller that exposes deletion must import and use `App\Trait\Global\HasDeleteMethods` and configure its model in the constructor with `$this->model = Model::class` or the trait's `setDeleteModel()` method. Call the parent constructor when the chosen base controller requires it.
- Use the trait-provided `destroy()`, `restore()`, and `forceDelete()` actions. Do not add manual controller or service versions of those actions for the ordinary lifecycle.
- Treat controllers that both use the trait and override one of those actions as legacy exceptions, not templates. When a task changes that delete path, first determine whether guards and callbacks can preserve its behavior cleanly; do not migrate unrelated controllers opportunistically.
- The trait accepts `ids`, then `id`, then the first route parameter; it supports model-bound instances and batches. Preserve that request contract unless the user explicitly requests a breaking change.
- The trait owns its Policy lookup, permission fallback, protected-relation checks, model lookup, and standard response. Do not duplicate them in the controller. Additional middleware may enforce a distinct established action permission, but it must not contradict the trait or Policy.
- Register `setDeleteGuards()`, `beforeDelete()`, or `afterDelete()` only for real domain preconditions or side effects. Use typed model callbacks as in `Modules/Delegation/app/Http/Controllers/DelegationController.php`; do not copy Delegation's draft/creator rule into unrelated domains.
- Keep `enableDeletePolicy(true)` as the default. Disable it only when the inspected feature has an explicit alternative authorization contract and tests covering it.
- Expose dedicated `delete`, `restore`, and `force-delete` endpoints according to `.agents/skills/laravel-route-development/SKILL.md`. Do not expose the resource controller's conventional `destroy` route in parallel.
- Expose `restore` and `force-delete` only when the model and feature support soft deletion. Read-only or intentionally non-deletable controllers must not gain fake lifecycle actions merely to resemble CRUD controllers.
- `HasDeleteMethods` does not make a multi-record lifecycle atomic. If callbacks or dependent writes must commit as one unit, preserve the trait as the HTTP entry point and design an authorized shared lifecycle/service transaction rather than placing the sequence in the controller or assuming batch atomicity.

## Direct Writes, Services, and Transactions

- Decide per operation whether a service has real ownership. A service is not the default for every `store()` or `update()`, and reducing two clear controller lines to a pass-through service is over-engineering.
- Leave ordinary code exactly as it is. When the action is one validated `create()` or `update()` with no extra write, no domain branching, no workflow transition, and no reusable business rule, keep it inline in the controller with no service and no transaction.
- When that write is accompanied by only one or two additional write lines — a model `syncX()` call, a log entry, a counter, one dependent create — keep them in the controller action but wrap the whole group in `DB::transaction()` so the action commits or rolls back as one unit. A partial commit there is a correctness bug, not a style preference.
- Move `store()` or `update()` into a cohesive domain service when the operation carries substantial internal detail: several coordinated writes, non-trivial invariants or state transitions, media handling, notifications, after-commit work, or reuse from another entry point such as a command, job, or MCP tool. The service then owns `DB::transaction()` for the complete atomic change, including relation sync, logs, counters, and dependent writes.
- Established model observers, casts, activity logging, and Resource formatting do not by themselves justify a service; they already belong to their existing layers.
- Line count is a signal, not the decision rule. A short domain-sensitive operation can require a service, while a straightforward controller action can remain direct. Base the decision on responsibility, atomicity, reuse, and testability.
- Reuse an existing cohesive service when it is already the authoritative owner of the operation's invariants. Do not bypass it to save a method call, and do not create a new service that merely mirrors `Model::create()` or `$model->update()`.
- Pass validated data or explicit values into the service. A service must not authorize, validate, read the global request, or return an HTTP response.
- Return the affected model or a purpose-specific result to the controller; the controller loads Resource relations and formats the response.
- Keep transactions short. Perform external I/O after commit with `DB::afterCommit()` when it must only run after a successful commit.
- Do not add transactions to `index`, `show`, or other read-only actions. Do not split one resource into generic repositories, DTOs, separate action classes, or other layers without a demonstrated current need.
- Existing batch lifecycle traits are not automatically atomic. If callbacks or multiple records must succeed or fail together, use a service or an authorized shared-trait change that supplies one transaction boundary.

## Relation Synchronization Belongs to the Model

- Every relation write — `syncData`, `sync()`, attach/detach, replace-children, file or participant synchronization — lives in a method on the model that owns the relation. Follow the established project convention of named model methods such as `syncFiles()` and `syncParticipants()` in `app/Models/Cause.php`.
- The controller or service only calls `$model->syncX($validatedPart)` inside the transaction boundary. It must not build pivot payloads, delete children, or map request arrays into relation rows itself.
- Keep the model method self-contained: it accepts already-validated data, guards empty input, and performs the full replace or merge for that relation. It must not authorize, validate, read the global request, or return an HTTP response.
- Reuse an existing `syncX()` method instead of adding a second path for the same relation. When several models share one sync rule, put it in the established shared trait, such as `Modules/IntellectualProperty/app/Traits/SyncIntellectualPropertyFiles.php`, rather than duplicating it.
- Adding a relation sync to an action means that action now performs more than one write, so it must run inside a transaction: `DB::transaction()` in the controller for one or two lines, or in the service when the operation is larger.
- Apply `.agents/skills/laravel-model-development/SKILL.md` when adding or changing these model methods.

## Verification

- Test authentication, each permission path, Policy ownership or state boundaries, and agreement between the index visibility scope and record-level access.
- Test index filters individually and in combination, eager-loaded Resource output, pagination, trashed behavior, and stable ordering.
- Test successful mutations and validation failures. For any multi-write action, service-backed or wrapped in a controller `DB::transaction()`, also test rollback after a later write fails and after-commit side effects when present.
- Test relation synchronization through the model method: full replace, empty input, and the resulting relation state after a rolled-back transaction.
- For deletable resources, test single and batch IDs, authorization, domain guards, soft delete, restore, force delete, and protected-relation failures as applicable. Confirm `DELETE /{model}` is not exposed when the dedicated delete endpoint is canonical.
- Re-scan the controller for business logic, unnecessary pass-through services, raw request data, unscoped list queries, duplicated authorization, hidden N+1 queries, relation sync written inline instead of on the model, multi-write sequences left outside a transaction, and transactions at the wrong layer.
- Run the smallest relevant PHPUnit feature tests, route inspection when routes change, and the backend-required formatter after PHP edits.
