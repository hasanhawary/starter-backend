<?php

namespace Modules\Showcase\app\Tools\Status\Strategies;

use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Tools\Status\ShowcaseStatus;

/**
 * Send a reviewed record back to its author for changes. The reason is
 * mandatory here — it is the only thing the author has to work from — and it is
 * kept as a decision note beside the transition entry.
 */
class DraftStatus extends ShowcaseStatus
{
    public function handle(array $params = []): void
    {
        $oldStatus = $this->model->status;

        $this->model->update(['status' => ShowcaseStatusEnum::Draft->value]);

        $this->model->log($oldStatus, ShowcaseStatusEnum::Draft, $params['notes'] ?? null);

        $this->recordReturnReason($params['notes']);

        $this->handleNotifications();
    }

    public function policy(): bool
    {
        // Only a record under review can be returned, and only by a publisher.
        if ($this->model?->status !== ShowcaseStatusEnum::InReview) {
            return false;
        }

        return $this->actorCan('publish-showcase');
    }

    public function validateRules(): array
    {
        return [
            'notes' => ['required', 'string', 'max:2000'],
        ];
    }

    public function buttons(): array
    {
        return [
            $this->button(ShowcaseStatusEnum::InReview),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    /**
     * The write that belongs to this one transition stays on the strategy.
     */
    private function recordReturnReason(string $reason): void
    {
        $this->model->addNote($reason, ShowcaseNoteTypeEnum::Decision->value);
    }
}
