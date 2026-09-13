<?php

namespace Modules\Showcase\app\Tools\Status\Strategies;

use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Tools\Status\ShowcaseStatus;

/**
 * Retire a record. Terminal: archiving also deactivates it, so it drops out of
 * every active listing and offers no further transition.
 */
class ArchivedStatus extends ShowcaseStatus
{
    public function handle(array $params = []): void
    {
        $oldStatus = $this->model->status;

        $this->model->update([
            'status' => ShowcaseStatusEnum::Archived->value,
            'is_active' => false,
        ]);

        $this->model->log($oldStatus, ShowcaseStatusEnum::Archived, $params['notes'] ?? null);

        $this->handleNotifications();
    }

    public function policy(): bool
    {
        // Anything that reached review or publication can be archived.
        if (! in_array($this->model?->status, [ShowcaseStatusEnum::InReview, ShowcaseStatusEnum::Published], true)) {
            return false;
        }

        return $this->actorCan('archive-showcase');
    }

    public function validateRules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Terminal state: nothing follows it.
     */
    public function buttons(): array
    {
        return [];
    }
}
