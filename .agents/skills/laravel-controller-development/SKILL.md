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
- For a user-requested Statement-style Strategy Pattern module, also apply `.agents/skills/laravel-strategy-module-development/SKILL.md`.
- For CRUD work, also read `.claude/skills/crud-resource.md` and its controller reference. For list filtering or authorization, read `.claude/skills/filters.md` or `.claude/skills/permissions.md`.
- For route work, apply `.agents/skills/laravel-route-development/SKILL.md`; treat `.agents/skills/laravel-route-development/SKILL.md` as secondary project evidence when it is relevant.
- Before writing or changing a service, read `Modules/Statement/app/Http/Controllers/StatementController.php` and `Modules/Statement/app/Services/StatementService.php` together. That pair is the house shape for a service-backed module; `Modules/Lawsuit` and `Modules/Delegation` follow it.
- Treat large legacy controllers as behavioral evidence, not as structural templates. Use the current simple CRUD controllers as references for direct single-model writes, and service-backed modules such as Statement only when the target operation has comparable domain complexity.

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
- Move `store()` or `update()` into a cohesive domain service when the operation carries substantial internal detail: several coordinated writes, non-trivial invariants or state transitions, media handling, notifications, after-commit work, or reuse from another entry point such as a command, job, or MCP tool.
- `DB::transaction()` stays in the controller action in both cases. It never moves into the service. `Modules/Statement`, `Modules/Lawsuit`, and `Modules/Delegation` are the three service-backed modules in this backend and not one of their services contains a single `DB::transaction()` call; the controller opens the boundary and the service supplies the steps that run inside it.
- Established model observers, casts, activity logging, and Resource formatting do not by themselves justify a service; they already belong to their existing layers.
- Line count is a signal, not the decision rule. A short domain-sensitive operation can require a service, while a straightforward controller action can remain direct. Base the decision on responsibility, atomicity, reuse, and testability.
- Reuse an existing cohesive service when it is already the authoritative owner of the operation's invariants. Do not bypass it to save a method call, and do not create a new service that merely mirrors `Model::create()` or `$model->update()`.
- Do not add transactions to `index`, `show`, or other read-only actions. Do not split one resource into generic repositories, DTOs, separate action classes, or other layers without a demonstrated current need.
- Existing batch lifecycle traits are not automatically atomic. If callbacks or multiple records must succeed or fail together, use an authorized shared-trait change that supplies one transaction boundary.

## The Service-Backed Shape: Follow `Modules/Statement`

`Modules/Statement/app/Http/Controllers/StatementController.php` with `Modules/Statement/app/Services/StatementService.php` is the canonical service-backed module. `Modules/Lawsuit` and `Modules/Delegation` repeat it line for line. Copy this shape; read those three before writing a new service.

### Controller side

```php
class StatementController extends Controller
{
    use HasDeleteMethods;

    public function __construct(protected StatementService $service)
    {
        $this->model = Statement::class;
    }

    public function store(StatementRequest $request): JsonResponse
    {
        Gate::authorize('create', $this->model);

        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $statement = $this->service->saveStatement($data);
            $this->service->syncStatementFile($data, $statement);
            $this->service->handleClosedRelated($data, $statement);

            return successResponse(new StatementResource($statement), __('api.created_success'));
        });
    }

    public function update(StatementRequest $request, Statement $statement): JsonResponse
    {
        Gate::authorize('assignOrUpdate', $statement);

        return DB::transaction(function () use ($request, $statement) {
            $data = $request->validated();
            $statement = $this->service->saveStatement($data, $statement);
            $this->service->syncStatementFile($data, $statement);

            return successResponse(new StatementResource($statement->refresh()), __('api.updated_success'));
        });
    }
}
```

- Inject the service with constructor property promotion as `protected XService $service` and call it as `$this->service`. Do not name it `$statementService`, resolve it with `app()`, or instantiate it in the action. The constructor keeps its other wiring — `$this->model`, delete callbacks, the parent constructor when the base controller requires it.
- `Gate::authorize()` runs in the controller before the transaction opens. Authorization is never a service concern.
- The action body is `return DB::transaction(function () use (...) { ... });` with `successResponse()` returned from inside the closure, exactly as the surrounding modules write it.
- `$data = $request->validated()` is taken once at the top of the closure and passed to every service call. Never hand the service the `Request` object.
- The controller lines up the steps; the service owns what each step does. Several sequential `$this->service->...()` calls in one closure is the intended shape, not a smell.
- The controller loads or refreshes relations for the response and builds the Resource. `$statement->refresh()` after an update, and `new XResource($model, 'details')` where the module uses a details variant.

### Service side

```php
class StatementService
{
    public function saveStatement(array $data, ?Statement $statement = null): Statement
    {
        // update-or-create the record, log it, sync its relations, enter the lifecycle
    }

    public function syncUsers(array $data, Statement $statement): void
    {
        if (! isset($data['user_ids'])) {
            return;
        }
        // ...
    }

    public function syncStatementFile(array $data, Model $model, bool $isUpdate = false): void { /* ... */ }

    public function manageUsers(Statement $statement, array $assignIds = [], array $removeIds = []): void { /* ... */ }

    private function logStatement(Statement $statement, StatementLogTypeEnum $type): StatementLog { /* ... */ }

    private function enterLifeCycle(Statement $statement, bool $isDraft): void { /* ... */ }
}
```

- One service per module, named `XService`, living in `Modules/X/app/Services/`.
- Method names describe the domain operation, never the endpoint. There is no `store()`, `update()`, or `changeStep()` on these services. A service written as one method per controller action has no responsibility of its own and is the pass-through layer this skill rejects.
- The create/update pair is a single `saveX(array $data, ?X $model = null): X`: the null model means create, a passed model means update. Both branches log, sync, and enter the lifecycle through the same private helpers. Do not split it into two public methods to mirror `store()` and `update()`.
- Signature order is `array $data` first, then the model — `syncX(array $data, X $model): void`. Methods that operate on explicit id lists instead of the payload take the model first, as `manageUsers(Statement $statement, array $assignIds = [], array $removeIds = [])` does.
- Each `syncX()` guards its own key and returns early when it is absent (`if (! isset($data['user_ids'])) { return; }`), so a payload that omits the key leaves the relation untouched instead of clearing it. Keep that guard in the service; do not re-check the key in the controller.
- Public methods are the composable steps the controller lines up. Anything only called from inside the service — `logX()`, `enterLifeCycle()`, `assignUsers()`, `removeUsers()`, `grantViewOwnPermission()` — stays private.
- `saveX()` returns the model. Sync and manage methods return `void`. Nothing in the service returns an HTTP response or a Resource.
- No `DB::transaction()`, no `Gate`, no `$request`, no validation. Reading `auth()->id()` for `created_by` attribution and `auth()->user()->name` for log messages is the established convention and is fine.
- A domain precondition that must abort the request throws `HttpResponseException(failResponse(trans('statement::messages.some_key'), 400))` from inside the service, as `handleClosedRelated()` does. The controller's open transaction rolls back with it. Use a module translation key, not a literal string.
- Cross-cutting work stays behind its own collaborator: status transitions through the `XStatusFactory`, notifications through the `Notification` facade, uploads through `UploadService`, permission grants through `AssignmentPermissionService`. The service coordinates them; it does not reimplement them.
- External I/O that must only run after a successful commit belongs in `DB::afterCommit()` or an after-commit job, not inline in the service.
- A read-only helper the controller needs for a decision — `hasConflictingActiveDelegation(int $userId): bool` in `DelegationService` — is legitimate service surface and needs no transaction around its call site.

## Relation Synchronization Belongs to the Model

- In a controller-only action, every relation write — `syncData`, `sync()`, attach/detach, replace-children, file or participant synchronization — lives in a method on the model that owns the relation. Follow the established project convention of named model methods such as `syncFiles()` and `syncParticipants()` in `app/Models/Cause.php`.
- The controller then only calls `$model->syncX($validatedPart)` inside the transaction boundary. It must not build pivot payloads, delete children, or map request arrays into relation rows itself.
- In a service-backed module the sync step lives on the service next to the save it belongs to — `syncUsers()`, `syncDepartments()`, `syncStatementFile()` in `StatementService`, `syncDefendants()` in `LawsuitService` — because the pivot rows carry domain data such as assignment type, attribution, and status. Follow the module you are in; do not move an established service sync onto the model, and do not pull a plain model `syncX()` into a service to match this shape.
- Keep the model method self-contained: it accepts already-validated data, guards empty input, and performs the full replace or merge for that relation. It must not authorize, validate, read the global request, or return an HTTP response.
- Reuse an existing `syncX()` method instead of adding a second path for the same relation. When several models share one sync rule, put it in the established shared trait, such as `Modules/IntellectualProperty/app/Traits/SyncIntellectualPropertyFiles.php`, rather than duplicating it.
- Adding a relation sync to an action means that action now performs more than one write, so it must run inside the controller's `DB::transaction()`. That is true whether the sync sits on the model or on the service — the transaction boundary does not move.
- Apply `.agents/skills/laravel-model-development/SKILL.md` when adding or changing these model methods.

## Verification

- Test authentication, each permission path, Policy ownership or state boundaries, and agreement between the index visibility scope and record-level access.
- Test index filters individually and in combination, eager-loaded Resource output, pagination, trashed behavior, and stable ordering.
- Test successful mutations and validation failures. Every multi-write action is wrapped in a controller `DB::transaction()`, service-backed or not, so also test rollback after a later write fails — including a service method that throws `HttpResponseException` mid-sequence — and after-commit side effects when present.
- Test relation synchronization through whichever method owns it, model or service: full replace, an omitted key leaving the relation untouched, and the resulting relation state after a rolled-back transaction.
- For deletable resources, test single and batch IDs, authorization, domain guards, soft delete, restore, force delete, and protected-relation failures as applicable. Confirm `DELETE /{model}` is not exposed when the dedicated delete endpoint is canonical.
- Re-scan the controller for business logic, raw request data, unscoped list queries, duplicated authorization, hidden N+1 queries, relation sync written inline in the action, and multi-write sequences left outside a transaction.
- Re-scan any service for the shapes this skill rejects: a `DB::transaction()` opened inside it, a `Gate` check or `Request` access, an HTTP response or Resource returned, or public methods named after controller actions instead of domain operations.
- Run the smallest relevant PHPUnit feature tests, route inspection when routes change, and the backend-required formatter after PHP edits.
