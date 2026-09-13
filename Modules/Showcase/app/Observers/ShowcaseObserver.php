<?php

namespace Modules\Showcase\app\Observers;

use Illuminate\Support\Str;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Tools\Facades\Notification;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Models\Showcase;

/**
 * Keeps the derived state of a showcase record consistent: the reference it is
 * identified by, the publication date implied by its status, and the
 * announcement that follows an activation change.
 */
class ShowcaseObserver
{
    /**
     * Handle the Showcase "creating" event.
     */
    public function creating(Showcase $showcase): void
    {
        $showcase->reference ??= $this->generateReference();

        $this->syncPublishedAt($showcase);
    }

    /**
     * Handle the Showcase "updating" event.
     */
    public function updating(Showcase $showcase): void
    {
        $this->syncPublishedAt($showcase);
    }

    /**
     * Handle the Showcase "updated" event.
     */
    public function updated(Showcase $showcase): void
    {
        $this->announceActivationChange($showcase);
    }

    /**
     * Activation is notifiable wherever it is flipped — the toggle endpoint, a
     * command, a seeder — so the announcement lives with the column change
     * rather than at one call site.
     *
     * A transition that deactivates as part of its own work (archiving) already
     * announces itself, so activation only speaks when it changed on its own.
     */
    private function announceActivationChange(Showcase $showcase): void
    {
        if (! $showcase->wasChanged('is_active') || $showcase->wasChanged('status')) {
            return;
        }

        // SendNotificationJob defers itself to after the commit.
        Notification::send(SystemEventSlugEnum::ToggleActiveShowcase->value, $showcase);
    }

    /**
     * `published_at` is derived from the status: stamped the first time a record
     * reaches `published`, cleared whenever it leaves that state again.
     */
    private function syncPublishedAt(Showcase $showcase): void
    {
        if (! $showcase->isDirty('status')) {
            return;
        }

        if ($showcase->status === ShowcaseStatusEnum::Published) {
            $showcase->published_at ??= now();

            return;
        }

        $showcase->published_at = null;
    }

    /**
     * A short, unique, human-quotable identifier: SHC-2026-XXXXXX.
     */
    private function generateReference(): string
    {
        do {
            $reference = sprintf('SHC-%s-%s', now()->format('Y'), Str::upper(Str::random(6)));
        } while (Showcase::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }
}
