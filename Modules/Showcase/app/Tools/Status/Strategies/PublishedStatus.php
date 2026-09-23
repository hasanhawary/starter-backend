<?php

namespace Modules\Showcase\app\Tools\Status\Strategies;

use Illuminate\Validation\Rules\Enum;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Tools\Facades\Notification;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Tools\Status\ShowcaseStatus;

/**
 * Approve a reviewed record and publish it. This is the only transition that
 * notifies, and the only one that can set a visibility the public can reach.
 */
class PublishedStatus extends ShowcaseStatus
{
    public function handle(array $params = []): void
    {
        $oldStatus = $this->model->status;

        $this->model->update([
            'status' => ShowcaseStatusEnum::Published->value,
            // The observer stamps `published_at` off the status change itself.
            'visibility' => $params['visibility'] ?? $this->model->visibility?->value,
            'expires_at' => $params['expires_at'] ?? $this->model->expires_at,
        ]);

        $this->model->log($oldStatus, ShowcaseStatusEnum::Published, $params['notes'] ?? null);

        $this->handleNotifications();
    }

    public function sendNotifications(): void
    {
        Notification::send(SystemEventSlugEnum::PublishShowcase->value, $this->model->refresh());
    }

    public function policy(): bool
    {
        // A record is published out of review only, and only by a publisher.
        if ($this->model?->status !== ShowcaseStatusEnum::InReview) {
            return false;
        }

        return $this->actorCan('publish-showcase');
    }

    public function validateRules(): array
    {
        return [
            // Publishing is when visibility is finally decided, so it may be set here.
            'visibility' => ['nullable', 'string', new Enum(ShowcaseVisibilityEnum::class)],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function buttons(): array
    {
        return [
            $this->button(ShowcaseStatusEnum::Archived),
        ];
    }
}
