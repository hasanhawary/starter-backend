<?php

namespace App\Services\User;

use App\Enum\Global\NotificationGroupEnum;
use App\Helpers\DelimiterParamValue;
use App\Http\Requests\User\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserService
{
    /**
     * @throws Throwable
     */
    public function store(UserRequest $request): User
    {
        return DB::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $this->syncRelations($user, $request);

            DB::afterCommit(fn () => $this->sendCredentials($user, $request));

            return $user->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function update(User $user, UserRequest $request): User
    {
        return DB::transaction(function () use ($user, $request) {
            $user->update($request->validated());
            $this->syncRelations($user, $request);

            DB::afterCommit(fn () => $this->sendCredentials($user->refresh(), $request, isCreate: false));

            return $user->refresh();
        });
    }

    public function syncRelations(User $user, UserRequest $request): void
    {
        when($request->filled('roles'), static fn () => $user->syncRoles(Role::whereId($request->roles)->pluck('name')));
        when($request->filled('permissions'), static fn () => $user->syncPermissions($request->permissions));
    }

    public function sendCredentials(User $user, UserRequest $request, bool $isCreate = true): void
    {
        if (! $isCreate && ! ($user->isDirty('email') || $user->isDirty('password'))) {
            return;
        }

        $params = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $user->getFullPhone(),
            'password' => (string) $request->password,
        ];
        $params[$isCreate ? 'created_at' : 'updated_at'] = DelimiterParamValue::plain(now()->format('Y-m-d H:i'));
        $user->sendNotification([
            'title' => $isCreate ? 'create_admin_data_title' : 'update_admin_data_title',
            'msg' => buildDelimiterMessage($isCreate ? 'create_admin_data_msg' : 'update_admin_data_msg', $params),
            'target_id' => $user->id,
            'target_type' => 'users',
            'group' => NotificationGroupEnum::Global->value,
        ], ['email', 'realtime', 'notify']);
    }
}
