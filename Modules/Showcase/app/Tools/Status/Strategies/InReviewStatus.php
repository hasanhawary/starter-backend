<?php

namespace Modules\Showcase\app\Tools\Status\Strategies;

use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Tools\Status\ShowcaseStatus;

/**
 * Submit a draft for review. The owner hands the record over, so the transition
 * records who it is now waiting on.
 */
class InReviewStatus extends ShowcaseStatus
{
    public function handle(array $params = []): void
    {
        $oldStatus = $this->model->status;

        $this->model->update([
            'status' => ShowcaseStatusEnum::InReview->value,
            // A record under review is claimed by the reviewer who will decide it.
            'owner_id' => $params['reviewer_id'] ?? $this->model->owner_id,
        ]);

        $this->model->log($oldStatus, ShowcaseStatusEnum::InReview, $params['notes'] ?? null);

        $this->handleNotifications();
    }

    public function policy(): bool
    {
        // Only a draft can be submitted, and only by someone who may edit it.
        if ($this->model?->status !== ShowcaseStatusEnum::Draft) {
            return false;
        }

        return $this->actorCan('update-showcase') && ($this->actorOwnsRecord() || $this->actorCan('view-all-showcase'));
    }

    public function validateRules(): array
    {
        return [
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function buttons(): array
    {
        return [
            $this->button(ShowcaseStatusEnum::Published),
            $this->button(ShowcaseStatusEnum::Draft),
            $this->button(ShowcaseStatusEnum::Archived),
        ];
    }
}
