<?php

namespace Modules\Showcase\app\Tools\Status;

use App\Models\User;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Models\Showcase;

/**
 * One concrete subclass per target status. A strategy owns exactly one
 * transition: the state it writes, who may take it, what extra payload it
 * validates, the log entry it records, and the transitions available after it.
 *
 * Both constructor arguments are nullable and public: the Form Request resolves
 * a strategy with no model to read `validateRules()`, and a system-initiated
 * transition passes no actor.
 */
abstract class ShowcaseStatus
{
    public function __construct(
        public ?Showcase $model = null,
        public ?User $user = null,
    ) {}

    public function getModel(): ?Showcase
    {
        return $this->model;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Apply the transition. Implementations capture the old status first, write
     * the new one, record the log, then notify last.
     *
     * @param  array<string, mixed>  $params
     */
    abstract public function handle(array $params = []): void;

    /**
     * Whether this actor may move this record into this status right now.
     * A pure, null-safe predicate over the model and the actor — no writes.
     */
    abstract public function policy(): bool;

    /**
     * Extra validation rules this transition adds to the action request.
     *
     * @return array<string, mixed>
     */
    abstract public function validateRules(): array;

    /**
     * The transitions available *from* this state, as frontend buttons.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract public function buttons(): array;

    /**
     * Called last inside `handle()`. `Notification::send()` queues a job that
     * already waits for the controller's transaction to commit.
     */
    protected function handleNotifications(): void
    {
        $this->sendNotifications();
    }

    public function sendNotifications(): void {}

    /**
     * Whether the actor holds the given module permission.
     */
    protected function actorCan(string $permission): bool
    {
        return (bool) $this->user?->can($permission);
    }

    /**
     * Whether the actor owns or created the record.
     */
    protected function actorOwnsRecord(): bool
    {
        return $this->user !== null
            && in_array($this->user->getKey(), [$this->model?->owner_id, $this->model?->created_by], true);
    }

    /**
     * One outgoing button. The label is the target status's own translated
     * label, so a new status never needs a second translation key.
     */
    protected function button(ShowcaseStatusEnum $target, string $type = 'modal'): array
    {
        return [
            'key' => $target->value,
            'label' => $target->selfResolve(),
            'type' => $type,
        ];
    }
}
