# Canonical Statement-Style Strategy Module Example

`Modules/Statement` is the reference implementation for this pattern; `Modules/Delegation` and `Modules/Lawsuit` repeat it. Read those `Tools/Status` trees before writing a new one. This file is the same structure with domain names removed — copy the responsibilities and naming, never Statement's statuses, permissions, notifications, or assignment rules.

The controller keeps `DB::transaction()` and resolves the Factory and Context itself. There is no workflow service in this pattern.

## Structure

```text
Modules/Example/
├── app/
│   ├── Enum/ExampleStatusEnum.php
│   ├── Enum/ExampleLogTypeEnum.php
│   ├── Http/Controllers/ExampleController.php
│   ├── Http/Requests/ExampleActionRequest.php
│   ├── Http/Resources/ExampleResource.php
│   ├── Models/{Example,ExampleLog}.php
│   ├── Policies/ExamplePolicy.php
│   ├── Providers/{ExampleServiceProvider,RouteServiceProvider}.php
│   └── Tools/Status/
│       ├── ExampleStatus.php
│       ├── ExampleStatusContext.php
│       ├── ExampleStatusFactory.php
│       └── Strategies/
│           ├── SentPendingStatus.php
│           ├── InProgressStatus.php
│           ├── AnsweredStatus.php
│           └── RejectedStatus.php
├── config/config.php
├── database/{factories,migrations,seeders}/
├── lang/{ar,en}/
├── routes/api.php
├── composer.json
└── module.json
```

`app/Services/` is absent on purpose. Add it only when the module has a save/sync operation of its own to own — see *Service Only When the Code Needs One* below. Add filters, scopes, observers, commands, schedules, exports, reports, or registries only when the module contract needs them.

## Status Enum

```php
<?php

namespace Modules\Example\App\Enum;

enum ExampleStatusEnum: string
{
    case Draft = 'draft';
    case SentPending = 'sent_pending';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Rejected = 'rejected';
}
```

Persist the status in a string column and cast it to this enum on the model.

## Abstract Status Tool

```php
<?php

namespace Modules\Example\App\Tools\Status;

use App\Models\User;
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
        app()->setLocale('ar');
        $this->sendNotifications();
    }

    public function sendNotifications(): void {}

    abstract public function handle(?array $params = []): void;

    abstract public function policy(): bool;

    abstract public function validateRules(): array;

    abstract public function buttons(): array;
}
```

Both constructor arguments are nullable and public: the Request resolves a strategy with no model to read `validateRules()`, and system-initiated transitions pass no actor. `sendNotifications()` is a no-op that each strategy overrides only when it notifies.

`handleNotifications()` in the live modules calls `app()->setLocale('ar')` before dispatching. That is a global side effect kept for compatibility with the existing notification templates — do not treat it as a rule to spread. Prefer passing the locale explicitly, or restoring the previous locale, in new code.

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

    public function handle(?array $params = []): void
    {
        $this->status->handle($params);
    }

    public function policy(): bool
    {
        if (isRoot()) {
            return true;
        }

        return $this->status->policy();
    }

    public function validateRules(): array
    {
        return $this->status->validateRules();
    }

    public function buttons(): array
    {
        if (isRoot()) {
            return $this->status->buttons();
        }

        return collect($this->status->buttons())->filter(function ($button) {
            $tempStatus = clone $this;

            return $tempStatus->setStatus($this->createStatusFromFactory($button['key']))->policy();
        })->values()->toArray();
    }

    protected function createStatusFromFactory(string $buttonKey): ExampleStatus
    {
        return ExampleStatusFactory::guess(
            $buttonKey,
            $this->status->getModel(),
            $this->status->getUser()
        );
    }
}
```

The root bypass lives here, once, so no strategy repeats it. `buttons()` resolves each outgoing target through the Factory and keeps only the ones whose own `policy()` passes for this actor.

## Factory

```php
<?php

namespace Modules\Example\App\Tools\Status;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Tools\Status\Strategies\AnsweredStatus;
use Modules\Example\App\Tools\Status\Strategies\InProgressStatus;
use Modules\Example\App\Tools\Status\Strategies\RejectedStatus;
use Modules\Example\App\Tools\Status\Strategies\SentPendingStatus;

class ExampleStatusFactory
{
    public static function guess(int|string $status, ?Model $model = null, ?User $user = null): ExampleStatus
    {
        return match ($status) {
            ExampleStatusEnum::SentPending->value => new SentPendingStatus($model, $user),
            ExampleStatusEnum::InProgress->value => new InProgressStatus($model, $user),
            ExampleStatusEnum::Answered->value => new AnsweredStatus($model, $user),
            ExampleStatusEnum::Rejected->value => new RejectedStatus($model, $user),
            default => throw new InvalidArgumentException("Unknown example status: {$status}"),
        };
    }
}
```

One static `guess()`, one `match`, every supported value mapped explicitly, and a loud `InvalidArgumentException` for anything else. `Draft` has no strategy because nothing transitions *to* it.

## Concrete Strategy

One class per target transition. It owns that transition's write, its eligibility rule, its extra validation, its log entry, its notification, and its outgoing buttons.

```php
<?php

namespace Modules\Example\App\Tools\Status\Strategies;

use Illuminate\Support\Str;
use Modules\Example\App\Enum\ExampleLogTypeEnum;
use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Tools\Status\ExampleStatus;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Tools\Facades\Notification;

class InProgressStatus extends ExampleStatus
{
    public function handle(?array $params = []): void
    {
        $oldStatus = $this->model->status;

        $this->model->update([
            'status' => ExampleStatusEnum::InProgress->value,
        ]);

        $logType = ExampleLogTypeEnum::StartedProcessing;
        $this->model->log(
            $oldStatus,
            $logType->value,
            buildDelimiterMessage(Str::snake($logType->name), [
                'name' => auth()->user()->name,
            ]),
            $params['notes'] ?? null
        );

        $this->handleNotifications();
    }

    public function sendNotifications(): void
    {
        Notification::send(SystemEventSlugEnum::ExampleInProgress->value, $this->model->refresh());
    }

    public function policy(): bool
    {
        if ($this->model?->status === ExampleStatusEnum::Received) {
            return $this->model?->users()->where('users.id', $this->user?->id)->exists();
        }

        return false;
    }

    public function validateRules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }

    public function buttons(): array
    {
        return [
            [
                'key' => ExampleStatusEnum::Answered->value,
                'label' => ExampleLogTypeEnum::SendReply->selfResolve(),
                'type' => 'modal',
            ],
            [
                'key' => ExampleStatusEnum::Rejected->value,
                'label' => ExampleLogTypeEnum::Rejected->selfResolve(),
                'type' => 'modal',
            ],
        ];
    }
}
```

Notes on the shape:

- `handle()` captures `$oldStatus` before the update, writes the status, records the log through the model's own `log($oldStatus, $type, $message, $notes)` method, then calls `handleNotifications()`. Optional inputs are read as `$params['x'] ?? null`; required ones are guaranteed by `validateRules()`.
- `policy()` is a pure predicate over `$this->model` and `$this->user`. It branches on the *source* status, uses null-safe access because the Request resolves strategies with no model, and returns `false` by default so an unlisted source state cannot transition.
- A strategy needing extra writes keeps them in a private helper on itself — `AnsweredStatus::syncReplayFiles()` in Statement — not in a service.
- `buttons()` lists outgoing target statuses from *this* state. A terminal status returns `[]`.
- `handleNotifications()` is called last, after the writes. `Notification::send()` dispatches `SendNotificationJob`, whose constructor calls `$this->afterCommit()`, so the notification already waits for the controller's transaction to commit.

## Dynamic Action Request

```php
<?php

namespace Modules\Example\App\Http\Requests;

use Illuminate\Validation\Rules\Enum;
use Modules\Example\App\Enum\ExampleStatusEnum;
use Modules\Example\App\Tools\Status\ExampleStatusContext;
use Modules\Example\App\Tools\Status\ExampleStatusFactory;

class ExampleActionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusRules = (new ExampleStatusContext)
            ->setStatus(ExampleStatusFactory::guess($this->request->get('status')))
            ->validateRules();

        return [
            'status' => ['required', 'string', new Enum(ExampleStatusEnum::class)],
            'notes' => ['nullable', 'string'],
            ...$statusRules,
        ];
    }

    public function attributes(): array
    {
        return __('example::validation.attributes');
    }
}
```

The Factory is resolved with the selector alone — no model, no actor — because only `validateRules()` is needed here. The base `status` enum rule stays; the strategy's rules are spread alongside it, never in place of it. Guard the unmapped-value path so a bad selector returns a validation error rather than a 500.

## Controller

```php
public function takeAction(ExampleActionRequest $request, Example $example): JsonResponse
{
    return DB::transaction(function () use ($request, $example) {
        $example = Example::query()->whereKey($example->id)->lockForUpdate()->firstOrFail();

        $statusClass = ExampleStatusFactory::guess($request->input('status'), $example, auth()->user());

        if (! isRoot() && ! $statusClass->policy()) {
            return failResponse(__('api.no_required_permissions'), 403);
        }

        $statusClass->handle([
            'notes' => $request->input('notes'),
            ...$request->validated(),
        ]);

        return successResponse(
            new ExampleResource($example->refresh(), 'details'),
            __('api.action_taken_successfully')
        );
    });
}
```

This is `StatementController::takeAction()`. The controller imports `DB` and the Factory and orchestrates the transition directly:

- `DB::transaction()` opens in the action and `successResponse()` returns from inside the closure.
- `lockForUpdate()->firstOrFail()` re-reads the row inside the transaction when the transition is race-prone.
- The Factory is called directly with the model and actor. Where the Resource or Request needs the root bypass and button filtering, they go through the Context instead.
- `! isRoot() && ! $statusClass->policy()` gates the transition as a domain invariant, returning `failResponse(..., 403)`.
- `handle()` receives the validated payload, and the controller formats the Resource from the refreshed model.

Do not wrap any of this in a service method. A `transition()` or `changeStep()` service that only re-reads the row, resolves the Factory, and opens a transaction is a pass-through layer with no responsibility of its own.

## Resource Buttons

```php
'buttons' => $this->status != ExampleStatusEnum::Draft ? $this->getStepButtons() : [],
```

```php
private function getStepButtons(): array
{
    return (new ExampleStatusContext)
        ->setStatus(ExampleStatusFactory::guess($this->status->value ?? $this->status, $this->resource, auth()->user()))
        ->buttons();
}
```

The Resource goes through the Context so the root bypass and per-target `policy()` filtering apply. It reads permitted transitions only — never `handle()`, never a write. Statuses with no strategy, such as `Draft`, are short-circuited before the Factory is called.

## Service Only When the Code Needs One

A status workflow does not by itself call for a service — the strategies already own the transitions, and the controller already owns the transaction.

`StatementService` exists because Statement has a *separate* responsibility beyond the workflow: creating and updating the record with its logs, departments, users, files, and lifecycle entry. That is `saveStatement()`, `syncUsers()`, `syncStatementFile()`, `manageUsers()` — domain operations, none of them named after a controller action, none of them opening a transaction.

Add a service to a strategy module only when it has that kind of work to own, and build it to the shape in `.agents/skills/laravel-controller-development/SKILL.md`. If the module is a workflow and nothing else, it ships without an `app/Services/` directory.
