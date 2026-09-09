<?php

namespace Modules\Form\app\Policies;

use App\Models\User;
use Modules\Form\app\Models\Form;

class FormPolicy
{
    public function create(User $user): bool
    {
        return $user->can('create-form');
    }

    public function update(User $user, Form $form): bool
    {
        return $user->can('update-form');
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->can('delete-form');
    }

    public function deleteAll(User $user): bool
    {
        return $user->can('delete-form');
    }
}
