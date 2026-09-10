<?php

namespace App\Services\Global;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;

class AssignmentPermissionService
{
    /**
     * @param  string  $model  kebab-case model key, e.g. "task", "delegation", "statement"
     * @param  array<int, int|string|null>|int|string|null  $userIds
     */
    public static function grantViewOwn(string $model, array|int|string|null $userIds): void
    {
        $userIds = collect(is_array($userIds) ? $userIds : [$userIds])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $permission = self::permission($model);

        User::query()
            ->whereKey($userIds->all())
            ->get()
            ->each(function (User $user) use ($permission) {
                if ($user->hasPermissionTo($permission->name)) {
                    return;
                }

                $user->givePermissionTo($permission->name);
            });
    }

    private static function permission(string $model): Permission
    {
        $name = "view-own-{$model}";

        return Permission::firstOrCreate(
            [
                'name' => $name,
                'guard_name' => self::guard(),
            ],
            [
                'display_name' => 'View own '.Str::headline($model),
                'group' => Str::studly($model),
            ]
        );
    }

    private static function guard(): string
    {
        return (string) config('roles.default_guard', 'sanctum');
    }
}
