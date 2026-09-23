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
- Before writing or changing a service, read the live service in this repository: `app/Services/User/UserService.php` with `app/Http/Controllers/API/User/UserController.php`, and `Modules/Showcase/app/Services/ShowcaseService.php` with its controller. Those are the house shape here.
- `Modules/Statement`, `Modules/Lawsuit` and `Modules/Delegation` are **not part of this repository**. Where guidance below still cites them it is describing a sibling project; the live files above decide this one.
- Treat large legacy controllers as behavioral evidence, not as structural templates. Use the current simple CRUD controllers as references for direct single-model writes, and service-backed modules such as Statement only when the target operation has comparable domain complexity.

## Controller Boundary

- Limit actions to HTTP concerns: authorize, accept a typed Form Request or route-bound model, compose a query, perform a simple single-model mutation or delegate a justified non-trivial operation, and format the established Resource response.
- Do not place domain decisions, state transitions, media handling, notification composition, multi-model writes, or reusable payload preparation in a controller. Relation synchronization belongs to the model that owns the relation.
- Do not hide business logic in private controller helpers. Move it to one cohesive existing or domain service; keep reusable query constraints in scopes.
- This covers query shape, not just business rules. A controller must not define a `baseQuery()`, `listQuery()`, `loadForResponse()` or any other private helper that assembles `with()` / `withCount()` / `withExists()` / `load()` sets. Name that shape as a scope on the model and call it from the action, so the listing and the record response read as one sentence: `Model::query()->visibleTo()->withListingData()`.
- Constructor work is limited to dependency injection, the required parent constructor, and configuration of inspected reusable controller traits.
- **A controller carries no inline comments.** `CountryController` has none and `UserController` has only its `@throws` blocks; that is the house style. An action that reads `Showcase::query()->visibleTo()->withListingData()` or `$showcase->lockFresh()` has already said what it does, and a comment repeating it is a second copy of the truth that rots on the next edit.
- A controller line that seems to *need* a comment is the signal: the thing it calls is badly named, or the rationale belongs one level down. Put the explanation in the docblock of the scope, model method, or collaborator that owns the behaviour — write it once, where anyone reading that mechanism from any call site will find it — and leave the action bare.
- Keep `@throws` annotations and the established section-comment banners. Those are structure and contract, not narration.
- Keep action flow readable without imposing an arbitrary line limit. Branching, loops, multiple writes, or domain terminology inside an action are signals that ownership belongs elsewhere.
- A single validated `create()` or `update()` plus Resource response formatting is acceptable controller orchestration. Do not extract it merely to make the controller shorter.
- Reuse `successResponse()`, `fetchData()`, Resources, Form Requests, and the established controller traits. `HasDeleteMethods` is the canonical delete lifecycle for deletable CRUD controllers; do not reimplement its actions.

## The House CRUD Shape

This is what a normal DataEntry resource looks like. Copy the shape; do not invent a service around it.

```php
public function index(PageRequest $request): JsonResponse
{
    $query = app(Pipeline::class)
        ->send(Sector::query()->visibleTo()->withListingData())
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

### Name the query shape, do not build it inline

- The set of relations a Resource reads is part of the model's query shape, so it belongs in the model's scope trait next to the selection scopes. Give it a scope that names the view it serves — `withListingData()`, `withDetailData()` — rather than listing relation strings in the controller.
- Provide the `load*` counterpart on the model for a route-bound record: `loadDetailData()` beside `scopeWithDetailData()`, mirroring Laravel's own `with`/`load`, `withCount`/`loadCount`, `withExists`/`loadExists` pairs. The action then reads `new XResource($model->loadDetailData())` with no helper of its own.
- Resolve per-row flags in SQL through that scope (`withExists(['pinUsers as is_pinned' => ...])`), never in the Resource. Share the constraint between the scope and its loader so both spell the flag the Resource reads identically.
- Every scope must have a named consumer — an action, a Policy, a Pipeline filter, a lookup, a command. A scope reachable only through a generic mechanism is dead code with an open door attached.

### No generic scope dispatch from request input

- Do not wire `App\Trait\Global\HasDynamicScopes` (`applyScopesFilter()`, the `scopes[]`/`values[]` parameters, a `$scopeMap` on the model) into a controller. It lets raw request input choose which model method runs, which makes the endpoint's real surface unreadable from the route, the controller, or the tests, and turns any future scope into public API by accident.
- Express each narrowing as its own named request parameter in the resource's Pipeline filter, mapped to the scope it means: `?mine=1` to `ownedBy()`, `?published=1` to `published()`, `?expiring_within=30` to `expiringWithin(30)`. The filter then documents the endpoint by being read.
- The trait stays in the repository for the lookup layer, which needs a scope name as data. That is a different contract from a resource listing; do not copy it into one.

## Authorization Ownership

**`Gate::authorize()` as the first line of the action it guards. Middleware only where the rule is genuinely flat.** A Policy decision belongs next to the code it protects, in the method that has the model in hand — not gathered into a `middleware()` block that has to re-bind the record by route-parameter name and then map abilities to actions from a distance.

```php
public static function middleware(): array
{
    // Flat permission, and `pin()` comes from a trait — there is no action body to put a line in.
    return [
        new Middleware(PermissionMiddleware::using('pin-showcase'), only: ['pin']),
    ];
}

public function index(PageRequest $request): JsonResponse
{
    Gate::authorize('viewAny', Showcase::class);
    // ...
}

public function update(ShowcaseRequest $request, Showcase $showcase): JsonResponse
{
    Gate::authorize('update', $showcase);
    // ...
}
```

- **Use `Gate::authorize()` in the action** for every Policy decision: ownership, visibility, a protected record, current state. Pass the class for collection and create checks (`viewAny`, `create`) and the route-bound model for record checks. One line, first line, no comment needed.
- **Use middleware only when the rule is flat** — a bare action permission with no model instance and no runtime context (`PermissionMiddleware::using('pin-showcase')`) — or when the action comes from a shared trait (`pin()`, and the `HasDeleteMethods` lifecycle) and therefore has no body of yours to put the call in. Middleware is the exception, not the default; a `middleware()` block listing one `can:` per action is the smell this rule exists to prevent.
- A rule that needs the request payload to resolve — a workflow transition whose eligibility depends on the target status — is neither. Put it inside the collaborator that owns the decision so every caller is gated; see `.agents/skills/laravel-strategy-module-development/SKILL.md`.
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
            $statement = $this->service->store($data);
            $statement->syncFiles($data['files'] ?? []);
            $this->service->handleClosedRelated($statement, $data);

            return successResponse(new StatementResource($statement), __('api.created_success'));
        });
    }

    public function update(StatementRequest $request, Statement $statement): JsonResponse
    {
        Gate::authorize('assignOrUpdate', $statement);

        return DB::transaction(function () use ($request, $statement) {
            $data = $request->validated();
            $statement = $this->service->update($statement, $data);
            $statement->syncFiles($data['files'] ?? []);

            return successResponse(new StatementResource($statement->refresh()), __('api.updated_success'));
        });
    }
}
```

- Inject the service with constructor property promotion as `protected XService $service` and call it as `$this->service`. Do not name it `$statementService`, resolve it with `app()`, or instantiate it in the action. The constructor keeps its other wiring — `$this->model`, delete callbacks, the parent constructor when the base controller requires it.
- `Gate::authorize()` runs in the action, before the transaction opens. Authorization is never a service concern.
- The action body is `return DB::transaction(function () use (...) { ... });` with `successResponse()` returned from inside the closure, exactly as the surrounding modules write it.
- `$data = $request->validated()` is taken once at the top of the closure and passed to every service call. Never hand the service the `Request` object.
- The controller lines up the steps; the service owns the record's writes and the model owns its relations'. A closure holding a service call followed by one or two `$model->syncX()` calls is the intended shape, not a smell.
- The controller loads or refreshes relations for the response and builds the Resource. `$statement->refresh()` after an update, and `new XResource($model, 'details')` where the module uses a details variant.

### Service side

```php
class ShowcaseService
{
    public function store(array $data): Showcase
    {
        // create the record and fire its side effects
    }

    public function update(Showcase $showcase, array $data): Showcase
    {
        // apply the change and fire its side effects
    }

    public function publish(Showcase $showcase): Showcase { /* the transition rule and its notification */ }

    public function announceActivationChange(Showcase $showcase): void { /* ... */ }

    private function attributesFrom(array $data): array { /* ... */ }

    private function notify(SystemEventSlugEnum $slug, Showcase $showcase): void { /* ... */ }
}
```

- One service per module or domain, named `XService`, living in `Modules/X/app/Services/` or `app/Services/{Domain}/`.
- **Create and update are two public methods, `store()` and `update()`** — as `app/Services/User/UserService.php` writes them. Do not fold them into one `saveX(array $data, ?X $model = null)`: the null-model branch hides two different operations behind one signature, and the create path and the update path rarely fire the same side effects (a create announces a new record, an update announces a change; only one of them can stamp `created_by`). Two methods make each path readable and separately testable, and let each name its own notification.
- Everything that is genuinely shared between them goes in a private helper the two call — `attributesFrom()`, `notify()`, `logX()` — not in a branch inside one public method.
- Beyond that pair, method names describe the domain operation, never the endpoint: `publish()`, `manageUsers()`. A service whose every method mirrors a controller action one-for-one has no responsibility of its own and is the pass-through layer this skill rejects.
- **A public method whose whole body is one call is not a method, it is a name.** `announceActivationChange()` that only calls `$this->notify(...)`, or a wrapper around a single `Notification::send()`, adds a layer without a decision in it. Either the method makes a choice worth isolating, or the caller makes the call directly — and if the caller is a controller, that is a sign the effect belongs to whatever already sees the change (an observer for a column flip, a strategy for a transition).
- Signature order is the model first, then the validated payload — `update(X $model, array $data)` — matching `UserService::update()`. `store(array $data)` takes only the payload because there is no model yet.
- **Relation writes are not service surface.** There is no `syncX()` on the service; see *Relation Synchronization Belongs to the Model* below. The service owns the record's own columns and the side effects of changing them.
- Public methods are the composable steps the controller lines up. Anything only called from inside the service — `logX()`, `enterLifeCycle()`, `assignUsers()`, `removeUsers()`, `grantViewOwnPermission()` — stays private.
- `store()`, `update()` and other write methods return the model. Sync and manage methods return `void`. Nothing in the service returns an HTTP response or a Resource.
- No `DB::transaction()`, no `Gate`, no `$request`, no validation. Reading `auth()->id()` for `created_by` attribution and `auth()->user()->name` for log messages is the established convention and is fine.
- A domain precondition that must abort the request throws `HttpResponseException(failResponse(trans('statement::messages.some_key'), 400))` from inside the service, as `handleClosedRelated()` does. The controller's open transaction rolls back with it. Use a module translation key, not a literal string.
- Cross-cutting work stays behind its own collaborator: status transitions through the `XStatusFactory`, notifications through the `Notification` facade, uploads through `UploadService`, permission grants through `AssignmentPermissionService`. The service coordinates them; it does not reimplement them.
- External I/O that must only run after a successful commit belongs in `DB::afterCommit()` or an after-commit job, not inline in the service. Check first whether the job already defers itself — `Notification::send()` dispatches `SendNotificationJob`, whose constructor calls `$this->afterCommit()`, so wrapping it is redundant.
- A read-only helper the controller needs for a decision — `hasConflictingActiveDelegation(int $userId): bool` in `DelegationService` — is legitimate service surface and needs no transaction around its call site.

## Relation Synchronization Belongs to the Model

- Every relation write — `syncData`, `sync()`, attach/detach, replace-children, file or participant synchronization — lives in a method on the model that owns the relation. Follow the established convention of named model methods such as `syncFiles()` and `syncParticipants()`, or `syncTags()` and `syncOpeningNote()` in `Modules/Showcase/app/Models/Showcase.php`.
- **This holds whether or not the action uses a service.** A service-backed module does not move its sync steps onto the service: `store()` and `update()` own the record's own columns and its side effects, the model owns its relations. A pivot carrying domain data (assignment type, attribution, `is_primary`) is still that relation's data, so it is still the model's to write.
- The controller then only calls `$model->syncX($validatedPart)` inside the transaction boundary, straight after the service call. It must not build pivot payloads, delete children, or map request arrays into relation rows itself.

```php
return DB::transaction(function () use ($request) {
    $data = $request->validated();

    $showcase = $this->service->store($data);
    $showcase->syncTags($data['tag_ids'] ?? [], $data['primary_tag_id'] ?? null);
    $showcase->syncOpeningNote($data['note'] ?? null);

    return successResponse(new ShowcaseResource($showcase->loadDetailData()), __('showcase::api.created_success'));
});
```

- Pass the relation's own slice of the payload, not the whole `$data` array. A model sync method takes typed values with safe defaults — `syncTags(array $tagIds = [], int|string|null $primaryTagId = null)` — so it never has to know the request's key names.
- Keep the model method self-contained: it accepts already-validated data, guards empty input, and performs the full replace or merge for that relation. It must not authorize, validate, read the global request, or return an HTTP response.
- Reuse an existing `syncX()` method instead of adding a second path for the same relation. When several models share one sync rule, put it in the established shared trait, such as `Modules/IntellectualProperty/app/Traits/SyncIntellectualPropertyFiles.php`, rather than duplicating it.
- Adding a relation sync to an action means that action now performs more than one write, so it must run inside the controller's `DB::transaction()`. The transaction boundary stays in the controller.
- Apply `.agents/skills/laravel-model-development/SKILL.md` when adding or changing these model methods.

## Verification

- Test authentication, each permission path, Policy ownership or state boundaries, and agreement between the index visibility scope and record-level access. Assert every gate through the endpoint, so a dropped `Gate::authorize()` line fails a test.
- Test index filters individually and in combination, eager-loaded Resource output, pagination, trashed behavior, and stable ordering.
- Test successful mutations and validation failures. Every multi-write action is wrapped in a controller `DB::transaction()`, service-backed or not, so also test rollback after a later write fails — including a service method that throws `HttpResponseException` mid-sequence — and after-commit side effects when present.
- Test relation synchronization through the model method that owns it: full replace, an omitted key leaving the relation untouched, and the resulting relation state after a rolled-back transaction.
- For deletable resources, test single and batch IDs, authorization, domain guards, soft delete, restore, force delete, and protected-relation failures as applicable. Confirm `DELETE /{model}` is not exposed when the dedicated delete endpoint is canonical.
- Re-scan the controller for business logic, raw request data, unscoped list queries, duplicated authorization, hidden N+1 queries, relation sync written inline in the action, and multi-write sequences left outside a transaction.
- Re-scan any service for the shapes this skill rejects: a `DB::transaction()` opened inside it, a `Gate` check or `Request` access, an HTTP response or Resource returned, a combined `saveX(..., ?X $model = null)` in place of `store()`/`update()`, a relation write that belongs on the model, or a method per controller action beyond that pair.
- Re-scan the controller for a private helper that assembles a query or a relation-load set, and for any `applyScopesFilter()` / `scopes[]` wiring. Both mean a query shape that should be a named scope.
- Re-scan `middleware()` for `can:` entries that duplicate what a `Gate::authorize()` line in the action would say more directly, and for a payload-dependent rule that should live in its collaborator instead.
- Re-scan for inline comments in the controller. Each one is either restating the line below it — delete it — or carrying a rationale that belongs in the docblock of the thing it describes.
- Run the smallest relevant PHPUnit feature tests, route inspection when routes change, and the backend-required formatter after PHP edits.
