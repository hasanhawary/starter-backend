<?php

namespace Modules\Showcase\app\Tools\Status;

use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Holds one selected strategy and delegates the four operations to it. The root
 * bypass, the transition guard and the button filtering live here, once, so no
 * strategy and no caller repeats them.
 */
class ShowcaseStatusContext
{
    protected ShowcaseStatus $status;

    public function setStatus(ShowcaseStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Run the transition, refusing it when this actor may not take it from the
     * record's current state.
     *
     * The guard lives here rather than at the call site so every entry point —
     * the HTTP action, a command, a job — is gated by the same check and none of
     * them can forget it.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws HttpResponseException when the actor may not take this transition
     */
    public function handle(array $params = []): void
    {
        if (! $this->policy()) {
            throw new HttpResponseException(
                failResponse(__('showcase::api.action_not_allowed'), code: 403)
            );
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

    /**
     * @return array<string, mixed>
     */
    public function validateRules(): array
    {
        return $this->status->validateRules();
    }

    /**
     * The outgoing transitions of the current state, narrowed to the ones this
     * actor is actually allowed to take.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buttons(): array
    {
        if (isRoot()) {
            return $this->status->buttons();
        }

        return collect($this->status->buttons())
            ->filter(fn (array $button) => (clone $this)
                ->setStatus($this->createStatusFromFactory($button['key']))
                ->policy())
            ->values()
            ->toArray();
    }

    protected function createStatusFromFactory(string $buttonKey): ShowcaseStatus
    {
        return ShowcaseStatusFactory::guess(
            $buttonKey,
            $this->status->getModel(),
            $this->status->getUser(),
        );
    }
}
