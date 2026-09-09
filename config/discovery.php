<?php

/*
|--------------------------------------------------------------------------
| Discovery
|--------------------------------------------------------------------------
|
| `filters` describes the filter form each discovery module exposes. It is
| consumed directly by the frontend through the `help-configs` endpoint
| (GET api/help-configs?configs[0][name]=discovery&configs[0][keys][0]=filters),
| so every field is already frontend-ready: a `type`, a translated `label`, and
| a grid `size`.
|
| Labels are stored as translation keys, not translated strings: config files
| load before the translator and before the locale middleware. They are
| resolved per request by App\Services\Global\DiscoveryConfigResolver, which is
| bound in AppServiceProvider.
|
| A `date_range` field exposes one key named after the column it filters
| (`created_at`, `last_login`, `deadline`). The frontend derives the two request
| params from that key, and every date range in the application is submitted the
| same way:
|
|   {key}_from  =>  lower bound, e.g. created_at_from
|   {key}_to    =>  upper bound, e.g. created_at_to
|
| Either bound may be sent alone, which leaves that side of the range open.
|
| A `select` field additionally declares the lookup it feeds from:
|
|   reference_type  => 'help-models' | 'help-enums' | 'help-configs'
|   name            => the endpoint's target name (table / enum / config)
|   module          => optional module, or null for app-level targets
|   actions         => optional lookup params forwarded to that endpoint,
|                      e.g. ['scopes' => ['active']] for help-models,
|                      ['method' => 'getList'] for help-enums, ['keys' => [...]]
|                      for help-configs. The frontend builds the lookup
|                      request as { name, ...actions }.
|
| A scoped help-models lookup may also carry `values`, positionally matched to
| `scopes`: `values[i]` holds the arguments of `scopes[i]`, so
|
|   ['scopes' => ['hasPermissionQuery'], 'values' => [['view-all-user']]]
|
| resolves to `User::hasPermissionQuery('view-all-user')`. A scope needing a
| record id only belongs on a filter rendered inside that record, where the
| frontend knows the id.
|
| `sorting` lists the columns each module may be sorted by (the `sort_column`
| request param, resolved by App\Filters\Global\OrderByFilter). An entry may be
| a plain column, or `displayed => actual` when the listing names a column after
| what it displays (`creator` sorts by `creator.name`).
|
*/

return [

    'filters' => [
        'users' => [
            [
                'key' => 'created_at',
                'type' => 'date_range',
                'label' => 'api.filter.global.created_at',
                'size' => ['cols' => 12, 'lg' => 12, 'md' => 12],
            ],
            [
                'key' => 'last_login',
                'type' => 'date_range',
                'label' => 'api.filter.user.last_login',
                'size' => ['cols' => 12, 'lg' => 12, 'md' => 12],
            ],
            'advanced' => [
                'type' => 'select',
                'label' => 'api.filter.global.advanced',
                'size' => ['cols' => 12, 'lg' => 12, 'md' => 12],
                'options' => [
                    [
                        'key' => 'is_active',
                        'type' => 'select',
                        'label' => 'api.filter.global.is_active',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-enums',
                        'name' => 'global.active_type',
                        'module' => null,
                        'actions' => [
                            'method' => 'getList',
                        ],
                    ],
                    [
                        'key' => 'gender',
                        'type' => 'select',
                        'label' => 'api.filter.user.gender',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-enums',
                        'name' => 'user.user_gender',
                        'module' => null,
                        'actions' => [
                            'method' => 'getList',
                        ],
                    ],
                    [
                        'key' => 'role_id',
                        'type' => 'select',
                        'label' => 'api.filter.user.role',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-models',
                        'name' => 'roles',
                        'module' => null,
                    ],
                    [
                        'key' => 'created_by',
                        'type' => 'select',
                        'label' => 'api.filter.global.created_by',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-models',
                        'name' => 'users',
                        'module' => null,
                    ],
                ],
            ],
        ],
    ],

    'sorting' => [
        'users' => [
            'id',
            'name',
            'email',
            'phone',
            'gender',
            'display_gender' => 'gender',
            'is_active',
            'creator' => 'creator.name',
            'last_login',
            'created_at',
        ],
    ],

];
