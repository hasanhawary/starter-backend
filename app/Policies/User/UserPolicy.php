<?php

namespace App\Policies\User;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param  ?User $userModel
     * @return bool
     */
    public function view(User $user, ?User $userModel = null): bool
    {
        return ($user->can('view-all-user') || $user->can('view-own-user')) &&
            (!$userModel || $this->checkUser($user, $userModel));
    }

    /**
     * @param User $user
     * @param User|null $userModel
     * @return bool
     */
    public function create(User $user, ?User $userModel = null): bool
    {
        return $user->can('create-user') && (!$userModel || $this->checkUser($user, $userModel));
    }

    /**
     * @param User $user
     * @param User $userModel
     * @return bool
     */
    public function update(User $user, User $userModel): bool
    {
        return $user->can('update-user') &&
            $this->checkUser($user, $userModel) &&
            !in_array($userModel->id, [...rootUsers(), auth()->id()], false);
    }

    /**
     * @param User $user
     * @param User|null $userModel
     * @return bool
     */
    public function delete(User $user, ?User $userModel = null): bool
    {
        if (!$user->can('delete-user')) {
            return false;
        }

        return !$userModel || ($this->checkUser($user, $userModel) && !in_array($userModel?->id, [...rootUsers(), auth()->id()], false));
    }

    /**
     * @param User $user
     * @param User|null $userModel
     * @return bool
     */
    public function restore(User $user, ?User $userModel = null): bool
    {
        if (!$user->can('restore-user')) {
            return false;
        }

        return !$userModel || $this->checkUser($user, $userModel);
    }

    /**
     * @param User $user
     * @param User $userModel
     * @return bool
     */
    public function checkUser(User $user, User $userModel): bool
    {
        return $user->can('view-all-user') || $userModel->created_by === $user->id;
    }
}
