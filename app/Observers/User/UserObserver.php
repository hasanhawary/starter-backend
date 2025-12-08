<?php

namespace App\Observers\User;

use App\Models\User;

class UserObserver
{
    /**
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        // $user->sendNotification([
        //     'title' => 'default_title',
        //     'msg' => 'default_message'
        // ], ['email']);
    }

    /**
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
//        $user->sendNotification([
//            'title' => 'default_title',
//            'msg' => 'default_message'
//        ], ['email', 'notify', 'realtime']);
    }
}
