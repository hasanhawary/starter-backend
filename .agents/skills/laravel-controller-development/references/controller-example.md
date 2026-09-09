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

## Transactional Service When Justified

When the operation grows past one or two extra lines — coordinated writes, state rules, media, notifications, or reuse from a command or job — inject `ExampleService`, replace the direct write with `$this->exampleService->store(...)` or `$this->exampleService->update(...)`, and let the service own the complete transaction. The service still delegates the relation write to the model's `syncMembers()`; it does not build the relation payload itself. Keep only operations required by the feature.

```php
<?php

namespace App\Services\Example;

use App\Models\Example;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ExampleService
{
    public function store(array $data): Example
    {
        return DB::transaction(function () use ($data) {
            $memberIds = Arr::pull($data, 'member_ids', []);
            $example = Example::query()->create($data);
            $example->syncMembers($memberIds);

            return $example->refresh();
        });
    }

    public function update(Example $example, array $data): Example
    {
        return DB::transaction(function () use ($example, $data) {
            $memberIds = Arr::pull($data, 'member_ids');
            $example->update($data);

            if ($memberIds !== null) {
                $example->syncMembers($memberIds);
            }

            return $example->refresh();
        });
    }
}
```

If notification, mail, storage, or another external effect depends on this transaction, schedule it with `DB::afterCommit()` or dispatch an after-commit job instead of holding the database transaction open.

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
