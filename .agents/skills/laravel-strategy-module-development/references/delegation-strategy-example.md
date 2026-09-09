# Canonical Delegation-Style Strategy Module Example

Use this as the structural reference for an explicitly requested workflow module. It preserves Delegation's `Tools/Status` architecture while keeping the controller thin and moving transaction and strategy orchestration into the service.

## Structure

```text
Modules/Example/
├── app/
│   ├── Enum/ExampleStatusEnum.php
│   ├── Http/Controllers/ExampleController.php
│   ├── Http/Requests/ExampleActionRequest.php
│   ├── Http/Resources/ExampleResource.php
│   ├── Models/Example.php
│   ├── Policies/ExamplePolicy.php
│   ├── Providers/{ExampleServiceProvider,RouteServiceProvider}.php
│   ├── Services/ExampleWorkflowService.php
│   └── Tools/Status/
│       ├── ExampleStatus.php
│       ├── ExampleStatusContext.php
│       ├── ExampleStatusFactory.php
│       └── Strategies/
│           ├── PendingApprovalStatus.php
│           ├── ApprovedStatus.php
│           └── RejectedStatus.php
├── config/config.php
├── database/{factories,migrations,Seeders}/
├── lang/{ar,en}/
├── routes/api.php
├── composer.json
└── module.json
```

Add filters, scopes, observers, commands, schedules, exports, reports, or support registries only when the module contract needs them.

## Status Enum

```php
<?php

namespace Modules\Example\App\Enum;

enum ExampleStatusEnum: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

Persist the status in a string column and cast it to this enum on the model.

## Abstract Status Tool

```php
<?php

namespace Modules\Example\App\Tools\Status;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Example\App\Models\Example;

abstract class ExampleStatus
{
    public function __construct(
        public ?Example $model = null,
        public ?User $user = null
    ) {}

    public function getModel(): ?Example
    {
        return $this->model;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    protected function handleNotifications(): void
    {
        DB::afterCommit(fn () => $this->sendNotifications());
    }

    public function sendNotifications(): void {}

    abstract public function handle(array $params = []): void;

    abstract public function policy(): bool;

    abstract public function validateRules(): array;

    abstract public function buttons(): array;
}
```

## Context

```php
<?php

namespace Modules\Example\App\Tools\Status;

class ExampleStatusContext
{
    protected ExampleStatus $status;

    public function setStatus(ExampleStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function handle(array $params = []): void
    {
        $this->status->handle($params);
    }

    public function policy(): bool
    {
        return isRoot($this->status->getUser()) || $this->status->policy();
    }

    public function validateRules(): array
    {
        return $this->status->validateRules();
    }

    public function buttons(): array
    {
        if (isRoot($this->status->getUser())) {
            return $this->status->buttons();
        }

        return collect($this->status->buttons())
            ->filter(function (array $button): bool {
                $target = ExampleStatusFactory::guess(
                    $button['key'],
                    $this->status->getModel(),
                    $this->status->getUser()
                );

                return (clone $this)->setStatus($target)->policy();
            })
            ->values()
            ->all();
    }
}
```

## Factory

```php
<?php

namespace Modules\Example\App\Tools\Status;

use App\Models\User;
use InvalidArgumentException;
use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Models\Example;
use Modules\Example\App\Tools\Status\Strategies\ApprovedStatus;
use Modules\Example\App\Tools\Status\Strategies\PendingApprovalStatus;
use Modules\Example\App\Tools\Status\Strategies\RejectedStatus;

class ExampleStatusFactory
{
    public static function guess(
        ExampleStatusEnum|string $status,
        ?Example $model = null,
        ?User $user = null
    ): ExampleStatus {
        $value = $status instanceof ExampleStatusEnum ? $status->value : $status;

        return match ($value) {
            ExampleStatusEnum::PendingApproval->value => new PendingApprovalStatus($model, $user),
            ExampleStatusEnum::Approved->value => new ApprovedStatus($model, $user),
            ExampleStatusEnum::Rejected->value => new RejectedStatus($model, $user),
            default => throw new InvalidArgumentException("Unknown example status: {$value}"),
        };
    }
}
```

## Concrete Strategy

Each target status gets one strategy. The example below allows approval only from `pending_approval`.

```php
<?php

namespace Modules\Example\App\Tools\Status\Strategies;

use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Tools\Status\ExampleStatus;

class ApprovedStatus extends ExampleStatus
{
    public function handle(array $params = []): void
    {
        $oldStatus = $this->model->status;

        $this->model->update(['status' => ExampleStatusEnum::Approved]);
        $this->model->log($oldStatus, ExampleStatusEnum::Approved, $params['notes'] ?? null);
        $this->handleNotifications();
    }

    public function policy(): bool
    {
        return $this->user?->can('approve-example')
            && $this->model?->status === ExampleStatusEnum::PendingApproval;
    }

    public function validateRules(): array
    {
        return ['notes' => ['nullable', 'string']];
    }

    public function buttons(): array
    {
        return [];
    }
}
```

Use the injected actor in logs, permissions, and notifications. Do not replace it with a hidden `auth()` dependency inside the strategy.

## Transactional Workflow Service

```php
<?php

namespace Modules\Example\App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Models\Example;
use Modules\Example\App\Tools\Status\ExampleStatusContext;
use Modules\Example\App\Tools\Status\ExampleStatusFactory;

class ExampleWorkflowService
{
    /**
     * @throws AuthorizationException
     */
    public function transition(
        Example $example,
        ExampleStatusEnum $targetStatus,
        ?User $actor,
        array $data = []
    ): Example {
        return DB::transaction(function () use ($example, $targetStatus, $actor, $data) {
            $lockedExample = Example::query()
                ->lockForUpdate()
                ->findOrFail($example->getKey());

            $context = (new ExampleStatusContext)->setStatus(
                ExampleStatusFactory::guess($targetStatus, $lockedExample, $actor)
            );

            throw_unless($context->policy(), AuthorizationException::class);

            $context->handle($data);

            return $lockedExample->refresh();
        });
    }
}
```

The strategy `policy()` assertion is a transition invariant. It complements rather than replaces the endpoint's general Laravel Policy/Gate check.

## Dynamic Action Request

Validate the selector before Factory resolution so an unknown status cannot become a server error.

```php
<?php

namespace Modules\Example\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Models\Example;
use Modules\Example\App\Tools\Status\ExampleStatusContext;
use Modules\Example\App\Tools\Status\ExampleStatusFactory;

class ExampleActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValue = $this->input('status');
        $status = is_string($statusValue) ? ExampleStatusEnum::tryFrom($statusValue) : null;
        $example = $this->route('example');
        $strategyRules = [];

        if ($status !== null && $example instanceof Example) {
            $strategyRules = (new ExampleStatusContext)
                ->setStatus(ExampleStatusFactory::guess($status, $example, $this->user()))
                ->validateRules();
        }

        return [
            'status' => ['required', 'string', new Enum(ExampleStatusEnum::class)],
            ...$strategyRules,
        ];
    }

    public function targetStatus(): ExampleStatusEnum
    {
        return ExampleStatusEnum::from($this->validated('status'));
    }
}
```

## Thin Controller

```php
<?php

namespace Modules\Example\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Example\App\Http\Requests\ExampleActionRequest;
use Modules\Example\App\Http\Resources\ExampleResource;
use Modules\Example\App\Models\Example;
use Modules\Example\App\Services\ExampleWorkflowService;

class ExampleController extends Controller
{
    public function __construct(private readonly ExampleWorkflowService $workflowService) {}

    public function takeAction(ExampleActionRequest $request, Example $example): JsonResponse
    {
        Gate::authorize('view', $example);

        $example = $this->workflowService->transition(
            $example,
            $request->targetStatus(),
            $request->user(),
            $request->validated()
        );

        return successResponse(
            new ExampleResource($example, 'details'),
            __('api.action_taken_successfully')
        );
    }
}
```

The controller intentionally imports neither `DB` nor the Factory. Commands and jobs call the same workflow service rather than invoking a concrete strategy directly.

## Resource Buttons

```php
<?php

namespace Modules\Example\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Example\App\Tools\Status\ExampleStatusContext;
use Modules\Example\App\Tools\Status\ExampleStatusFactory;

class ExampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'buttons' => (new ExampleStatusContext)
                ->setStatus(ExampleStatusFactory::guess($this->status, $this->resource, $request->user()))
                ->buttons(),
        ];
    }
}
```

The Resource reads permitted transitions only. It must not call `handle()` or cause writes.
