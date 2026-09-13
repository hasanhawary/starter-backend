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

namespace Modules\Example\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum ExampleStatusEnum: string
{
    use EnumMethods;

    case Draft = 'draft';
    case SentPending = 'sent_pending';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Rejected = 'rejected';
}
```

Persist the status in a string column, cast it to this enum on the model, and mirror the column default in the model's `$attributes` — a record straight out of `create()` has not re-read the row, so otherwise its status is null on the create path only.

`EnumMethods` gives `selfResolve()`, which the strategies use for button labels, so the workflow needs no parallel log-type enum for them.

## Abstract Status Tool

```php
<?php

namespace Modules\Example\app\Tools\Status;

use App\Models\User;
use Modules\Example\app\Models\Example;

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

    abstract public function handle(array $params = []): void;

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

namespace Modules\Example\app\Tools\Status;

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
        // The guard lives here so every caller is gated, not just the controller.
        if (! $this->policy()) {
            throw new HttpResponseException(failResponse(__('example::api.action_not_allowed'), code: 403));
        }

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

The root bypass and the transition guard live here, once, so neither a strategy nor a caller repeats them. `buttons()` resolves each outgoing target through the Factory and keeps only the ones whose own `policy()` passes for this actor.

## Factory

```php
<?php

namespace Modules\Example\app\Tools\Status;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Example\app\Enum\ExampleStatusEnum;
use Modules\Example\app\Tools\Status\Strategies\AnsweredStatus;
use Modules\Example\app\Tools\Status\Strategies\InProgressStatus;
use Modules\Example\app\Tools\Status\Strategies\RejectedStatus;
use Modules\Example\app\Tools\Status\Strategies\SentPendingStatus;

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

One static `guess()`, one `match`, every supported value mapped explicitly, and a loud `InvalidArgumentException` for anything else.

`Draft` is mapped here only if a record can rest in it or a transition returns to it — `buttons()` is called on the strategy for the *current* state, so any state a record sits in needs one. Mapping every enum case (as `Modules/Showcase` does) is the simpler default and removes the short-circuit below; leave a status out only when nothing transitions to it and no record rests in it.

## Concrete Strategy

One class per target transition. It owns that transition's write, its eligibility rule, its extra validation, its log entry, its notification, and its outgoing buttons.

```php
<?php

namespace Modules\Example\app\Tools\Status\Strategies;

use Illuminate\Support\Str;
use Modules\Example\app\Enum\ExampleLogTypeEnum;
use Modules\Example\app\Enum\ExampleStatusEnum;
use Modules\Example\app\Tools\Status\ExampleStatus;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Tools\Facades\Notification;

class InProgressStatus extends ExampleStatus
{
    public function handle(array $params = []): void
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

namespace Modules\Example\app\Http\Requests;

use Illuminate\Validation\Rules\Enum;
use Modules\Example\app\Enum\ExampleStatusEnum;
use Modules\Example\app\Tools\Status\ExampleStatusContext;
use Modules\Example\app\Tools\Status\ExampleStatusFactory;

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
        $example = $example->lockFresh();

        (new ExampleStatusContext)
            ->setStatus(ExampleStatusFactory::guess($request->validated('status'), $example, auth()->user()))
            ->handle($request->validated());

        return successResponse(
            new ExampleResource($example->refresh()->loadDetailData()),
            __('example::api.action_taken_success')
        );
    });
}
```

This is `StatementController::takeAction()`. The controller imports `DB` and the Factory and orchestrates the transition directly:

- `DB::transaction()` opens in the action and `successResponse()` returns from inside the closure.
- `lockFresh()` is the model's own named method for "re-read me under a row lock inside the open transaction". Route binding resolved the record before the transaction and without a lock, so a race-prone transition needs it — and naming it is what keeps the line from reading as a pointless second fetch of an already-injected model:

```php
public function lockFresh(): static
{
    return $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
}
```
- **The action authorizes nothing.** The Factory resolves the strategy, the Context runs it, and the Context's own guard refuses a transition this actor may not take. Writing `! isRoot() && ! $statusClass->policy()` here would duplicate a rule the Context owns and leave a hole for any caller that copies the action without the check.
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

`StatementService` exists because Statement has a *separate* responsibility beyond the workflow: creating and updating the record with its logs and lifecycle entry. That is `store()`, `update()` and `manageUsers()` — the record's own writes and their side effects, none of them opening a transaction. Its relation writes (`syncUsers()`, `syncFiles()`) live on the model, as `.agents/skills/laravel-controller-development/SKILL.md` requires.

Add a service to a strategy module only when it has that kind of work to own, and build it to the shape in `.agents/skills/laravel-controller-development/SKILL.md`. If the module is a workflow and nothing else, it ships without an `app/Services/` directory.
