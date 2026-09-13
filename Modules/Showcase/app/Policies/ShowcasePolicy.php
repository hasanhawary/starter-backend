<?php

namespace Modules\Showcase\app\Policies;

use App\Models\User;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Models\Showcase;

/**
 * Permission plus ownership. `view-all-showcase` sees every record;
 * `view-own-showcase` only the ones the user owns or created, and a private
 * record is never readable by anyone else.
 *
 * Transition eligibility is not here: each `Tools/Status` strategy owns its own
 * `policy()`, because it depends on the record's current status.
 */
class ShowcasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['view-all-showcase', 'view-own-showcase']);
    }

    public function view(User $user, Showcase $showcase): bool
    {
        if ($this->owns($user, $showcase)) {
            return $user->canAny(['view-all-showcase', 'view-own-showcase']);
        }

        if ($showcase->visibility === ShowcaseVisibilityEnum::Private) {
            return false;
        }

        return $user->can('view-all-showcase');
    }

    public function create(User $user): bool
    {
        return $user->can('create-showcase');
    }

    public function update(User $user, Showcase $showcase): bool
    {
        return $user->can('update-showcase') && ($this->owns($user, $showcase) || $user->can('view-all-showcase'));
    }

    public function delete(User $user, Showcase $showcase): bool
    {
        return $user->can('delete-showcase') && ($this->owns($user, $showcase) || $user->can('view-all-showcase'));
    }

    public function restore(User $user, Showcase $showcase): bool
    {
        return $user->can('restore-showcase');
    }

    public function forceDelete(User $user, Showcase $showcase): bool
    {
        return $user->can('force-delete-showcase');
    }

    public function toggleActive(User $user, Showcase $showcase): bool
    {
        return $user->can('toggle-active-showcase');
    }

    private function owns(User $user, Showcase $showcase): bool
    {
        return in_array($user->getKey(), [$showcase->owner_id, $showcase->created_by], true);
    }
}
