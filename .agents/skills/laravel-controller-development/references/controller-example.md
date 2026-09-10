# Canonical Thin API Controller Example

This example combines the repository's current conventions without copying domain-specific behavior from a large controller. Adapt names, relations, filters, abilities, lifecycle traits, and messages to the actual feature.

## Controller

This version is intentionally a simple CRUD controller. Its writes are one validated model operation, so adding a pass-through service would not improve cohesion or testability.

```php
<?php

namespace App\Http\Controllers\API\Example;

use App\Filters\Example\ExampleFilter;
use App\Filters\Global\JsonNameFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Example\ExampleRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Example\ExampleResource;
use App\Models\Example;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ExampleController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Example::class;
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('create-example'), only: ['store']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Example::class);

        $query = app(Pipeline::class)
            ->send(Example::query()
                ->related()
                ->with(['creator']))
            ->through([
                ExampleFilter::class,
                JsonNameFilter::class,
                TrashedFilter::class,
                OrderByFilter::class,
            ])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, ExampleResource::class));
    }

    public function store(ExampleRequest $request): JsonResponse
    {
        $example = Example::query()->create($request->validated());

        return successResponse(
            new ExampleResource($example->load('creator')),
            __('api.global.created', ['item' => __('api.messages.example.example')])
        );
    }

    public function show(Example $example): JsonResponse
    {
        Gate::authorize('view', $example);

        return successResponse(new ExampleResource($example->load('creator')));
    }

    public function update(ExampleRequest $request, Example $example): JsonResponse
    {
        Gate::authorize('update', $example);

        $example->update($request->validated());

        return successResponse(
            new ExampleResource($example->load('creator')),
            __('api.global.updated', ['item' => __('api.messages.example.example')])
        );
    }
}
```

`create-example` is a fixed action permission, so middleware owns it. `view` and `update` are contextual in this example, so the Policy owns them. Do not copy that split when the target domain has different authorization semantics.

## Service Decision

Keep the direct writes above while each action is only one validated model mutation. Model observers, casts, activity logging, relation loading for the response, and the response envelope do not turn that mutation into domain orchestration. Ordinary code stays ordinary: no service, no transaction.

When the action gains only one or two extra write lines, keep it in the controller and wrap the group in `DB::transaction()` so it cannot commit halfway:

```php
public function store(ExampleRequest $request): JsonResponse
{
    $data = $request->validated();

    $example = DB::transaction(function () use ($data) {
        $example = Example::query()->create(Arr::except($data, ['member_ids']));
        $example->syncMembers($data['member_ids'] ?? []);

        return $example;
    });

    return successResponse(
        new ExampleResource($example->load('creator')),
        __('api.global.created', ['item' => __('api.messages.example.example')])
    );
}
```

Introduce and inject a service when `store()` or `update()` carries substantial internal detail: several coordinated writes, state-transition rules, media handling, notifications, after-commit work, or reuse from another entry point. Decide by ownership and atomicity rather than a numeric line threshold.

## Relation Synchronization on the Model

The relation write itself belongs to the model that owns the relation, following the project convention of `syncFiles()` and `syncParticipants()` in `app/Models/Cause.php`. The controller or service only calls it inside the transaction.

```php
public function syncMembers(array $memberIds = []): void
{
    if (empty($memberIds)) {
        return;
    }

    $this->members()->sync($memberIds);
}
```

The method takes already-validated data, guards empty input, and owns the complete replace or merge for that relation. It does not authorize, validate, read the request, or return a response. When several models share the same rule, put it in a shared trait such as `Modules/IntellectualProperty/app/Traits/SyncIntellectualPropertyFiles.php`.

## Delete Lifecycle

The example intentionally has no hand-written deletion actions. `use HasDeleteMethods` and `$this->model = Example::class` are the complete ordinary controller wiring; the trait supplies `destroy()`, `restore()`, and `forceDelete()`.

When a feature has a genuine domain precondition, register a typed callback in the constructor, following the shape used by `DelegationController`:

```php
use Illuminate\Http\Exceptions\HttpResponseException;

$this->beforeDelete('delete', function (Example $example): void {
    if ($example->is_locked) {
        throw new HttpResponseException(
            failResponse(__('api.cannot_delete_locked'), 400)
        );
    }
});
```

The predicate and translation key above are placeholders for a real, inspected domain rule; do not create them merely to match the example. Authorization belongs to the trait's Policy/permission path, while this callback is for a lifecycle-specific domain precondition. Use `setDeleteGuards()` when all registered conditions must pass, and `afterDelete()` only for required post-lifecycle behavior.

The paired route contract is documented in `.agents/skills/laravel-route-development/references/routes-example.md`. It uses explicit `delete`, `restore`, and `force-delete` endpoints and excludes `destroy` from `apiResource`.

## Service-Backed Module: The Statement Shape

When the operation grows past one or two extra lines — coordinated writes, state rules, media, notifications, or reuse from a command or job — inject a service. The house shape is `Modules/Statement`, repeated by `Modules/Lawsuit` and `Modules/Delegation`: the controller keeps `DB::transaction()` and lines up the steps, and the service owns what each step does. Read those modules before writing a new service.

### Controller

```php
class ExampleController extends Controller
{
    use HasDeleteMethods;

    public function __construct(protected ExampleService $service)
    {
        $this->model = Example::class;
    }

    public function store(ExampleRequest $request): JsonResponse
    {
        Gate::authorize('create', $this->model);

        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $example = $this->service->saveExample($data);
            $this->service->syncExampleFile($data, $example);

            return successResponse(new ExampleResource($example), __('api.created_success'));
        });
    }

    public function update(ExampleRequest $request, Example $example): JsonResponse
    {
        Gate::authorize('update', $example);

        return DB::transaction(function () use ($request, $example) {
            $data = $request->validated();
            $example = $this->service->saveExample($data, $example);
            $this->service->syncExampleFile($data, $example);

            return successResponse(new ExampleResource($example->refresh()), __('api.updated_success'));
        });
    }
}
```

The service is injected by constructor property promotion and called as `$this->service`. `Gate::authorize()` runs before the transaction. `$request->validated()` is taken once inside the closure and passed to each service call. `successResponse()` is returned from inside the closure, and the controller is the only place that touches Resources.

### Service

```php
<?php

namespace Modules\Example\App\Services;

use Modules\Example\App\Enum\ExampleLogTypeEnum;
use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Models\Example;
use Modules\Example\App\Models\ExampleLog;
use Modules\Example\App\Tools\Status\ExampleStatusFactory;

class ExampleService
{
    public function saveExample(array $data, ?Example $example = null): Example
    {
        $isDraft = $data['is_draft'] ?? false;

        $attributes = [
            'subject' => $data['subject'],
            'description' => $data['description'],
        ];

        if ($example) {
            $example->update($attributes);
            $this->logExample($example, ExampleLogTypeEnum::Updated);

            $wasDraft = $example->is_draft;
            $this->syncMembers($data, $example);

            if ($wasDraft && ! $isDraft) {
                $this->enterLifeCycle($example, $isDraft);
            }

            return $example;
        }

        $example = Example::create([
            ...$attributes,
            'is_draft' => $isDraft,
            'status' => $isDraft ? ExampleStatusEnum::Draft : ExampleStatusEnum::SentPending,
        ]);

        $this->logExample($example, $isDraft ? ExampleLogTypeEnum::CreatedDraft : ExampleLogTypeEnum::Created);
        $this->syncMembers($data, $example);
        $this->enterLifeCycle($example, $isDraft);

        return $example;
    }

    public function syncMembers(array $data, Example $example): void
    {
        if (! isset($data['member_ids'])) {
            return;
        }

        $now = now();
        $pivotData = collect($data['member_ids'])->mapWithKeys(fn ($id) => [
            $id => [
                'created_by' => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->toArray();

        $example->members()->sync($pivotData);
    }

    private function logExample(Example $example, ExampleLogTypeEnum $type): ExampleLog
    {
        return $example->logs()->create([
            'type' => $type->value,
            'message' => buildDelimiterMessage(Str::snake($type->name), ['name' => auth()->user()->name]),
        ]);
    }

    private function enterLifeCycle(Example $example, bool $isDraft): void
    {
        if ($isDraft) {
            return;
        }

        ExampleStatusFactory::guess(ExampleStatusEnum::SentPending->value, $example)->handle();
    }
}
```

What makes this the house shape:

- **No `DB::transaction()` anywhere in the service.** The controller owns the boundary. None of the three real services in this backend contains one.
- **`saveX(array $data, ?X $model = null)` covers create and update in one method.** Do not add `store()` and `update()` methods that mirror the controller's actions; a service built one-method-per-endpoint has no responsibility of its own.
- **Public methods are the steps the controller composes** — `saveX()`, `syncX()`, `syncXFile()`, `manageX()`, `handleX()`. Helpers only called from inside — `logX()`, `enterLifeCycle()` — are private.
- **`array $data` first, model second.** Each `syncX()` guards its own key with `isset()` and returns early, so an omitted key leaves the relation untouched.
- **`saveX()` returns the model; sync and manage methods return `void`.** The service never returns a Resource or an HTTP response, never calls `Gate`, and never reads the `Request`. `auth()->id()` for attribution and `auth()->user()->name` in log messages is established and fine.
- **A domain precondition that must abort throws from inside the service**, and the controller's open transaction rolls back with it:

```php
if (! $relatedExample) {
    throw new HttpResponseException(
        failResponse(trans('example::messages.related_example_not_found'), 400)
    );
}
```

- **Collaborators stay behind their own classes**: status transitions through `XStatusFactory`, notifications through the `Notification` facade, uploads through `UploadService`, permission grants through `AssignmentPermissionService`. External effects that must only follow a successful commit go through `DB::afterCommit()` or an after-commit job.

## Visibility Scope

The index applies the scope before Pipeline filters. Its ownership rule must match the record Policy.

```php
<?php

namespace App\Scopes\Example;

use Illuminate\Database\Eloquent\Builder;

trait ExampleScopes
{
    public function scopeRelated(Builder $builder): Builder
    {
        $user = auth()->user();

        return $builder->when(
            ! $user->can('view-all-example'),
            fn (Builder $query) => $query->where('created_by', $user->id)
        );
    }
}
```

Import and use `ExampleScopes` on the model before calling `related()`; never copy a scope call that the target model does not implement. The corresponding Policy should authorize the same population for `view`. Middleware remains appropriate for actions whose permission is fixed and does not depend on a record.
