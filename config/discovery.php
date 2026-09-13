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

        'showcases' => [
            [
                'key' => 'created_at',
                'type' => 'date_range',
                'label' => 'api.filter.global.created_at',
                'size' => ['cols' => 12, 'lg' => 12, 'md' => 12],
            ],
            [
                'key' => 'expires_at',
                'type' => 'date_range',
                'label' => 'api.filter.showcase.expires_at',
                'size' => ['cols' => 12, 'lg' => 12, 'md' => 12],
            ],
            'advanced' => [
                'type' => 'select',
                'label' => 'api.filter.global.advanced',
                'size' => ['cols' => 12, 'lg' => 12, 'md' => 12],
                'options' => [
                    [
                        'key' => 'status',
                        'type' => 'select',
                        'label' => 'api.filter.showcase.status',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-enums',
                        'name' => 'showcase_status',
                        'module' => 'showcase',
                        'actions' => [
                            'method' => 'getList',
                        ],
                    ],
                    [
                        'key' => 'priority',
                        'type' => 'select',
                        'label' => 'api.filter.showcase.priority',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-enums',
                        'name' => 'showcase_priority',
                        'module' => 'showcase',
                        'actions' => [
                            'method' => 'getList',
                        ],
                    ],
                    [
                        'key' => 'visibility',
                        'type' => 'select',
                        'label' => 'api.filter.showcase.visibility',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-enums',
                        'name' => 'showcase_visibility',
                        'module' => 'showcase',
                        'actions' => [
                            'method' => 'getList',
                        ],
                    ],
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
                        'key' => 'showcase_category_id',
                        'type' => 'select',
                        'label' => 'api.filter.showcase.category',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-models',
                        'name' => 'showcase_categories',
                        'module' => 'showcase',
                        'actions' => [
                            'scopes' => ['active'],
                        ],
                    ],
                    [
                        'key' => 'tag_id',
                        'type' => 'select',
                        'label' => 'api.filter.showcase.tag',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-models',
                        'name' => 'showcase_tags',
                        'module' => 'showcase',
                        'actions' => [
                            'scopes' => ['active'],
                        ],
                    ],
                    [
                        'key' => 'owner_id',
                        'type' => 'select',
                        'label' => 'api.filter.showcase.owner',
                        'size' => ['cols' => 12, 'lg' => 6, 'md' => 6],
                        'reference_type' => 'help-models',
                        'name' => 'users',
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

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    |
    | The columns each listing may be sorted by (the `sort_column` request
    | param, resolved by App\Filters\Global\OrderByFilter).
    |
    | The key is the module key `getModelKey()` produces for the listed model —
    | singular snake case (`showcase_category`, not `showcase_categories`) —
    | because that is what `wrapPaginate()` passes to `resourceSorting()`.
    |
    | An entry is either a plain column, or `displayed => actual` when the
    | listing names a value after what it shows rather than the column behind
    | it: `display_status` sorts by `status`, `creator` by `creator.name`,
    | `remaining_days` by the expression the model declares in
    | `sortableExpressions()`.
    |
    | Only keys the Resource actually returns are listed here; anything else
    | can never appear in the `sorting` payload, so it would be dead config.
    | Arrays (`roles`, `permissions`, `tags`, `notes`), media paths and JSON
    | blobs are deliberately absent — there is nothing stable to order by.
    |
    */

    'sorting' => [
        'user' => [
            'id',
            'name',
            'email',
            'phone',
            'gender',
            'display_gender' => 'gender',
            'is_active',
            'creator' => 'creator.name',
            'created_at',
            'updated_at',
        ],
        'role' => [
            'id',
            'name',
            'display_name',
            'translation_display_name' => 'display_name',
            'is_active',
            'creator' => 'creator.name',
            'created_at',
            'updated_at',
        ],
        'permission' => [
            'id',
            'name',
            'display_name',
            'translation_display_name' => 'display_name',
            'group',
            'display_group' => 'group',
        ],
        'country' => [
            'id',
            'name',
            'translation_name' => 'name',
            'nationality',
            'translation_nationality' => 'nationality',
            'code',
            'phone_code',
            'phone_length',
            'created_at',
        ],
        'setting' => [
            'id',
            'key',
            'value',
            'translated_value' => 'value',
            'label',
            'translated_label' => 'label',
            'placeholder',
            'translated_placeholder' => 'placeholder',
            'group',
            'display_group' => 'group',
            'type',
            'display_type' => 'type',
            'is_env',
            'is_multi_lang',
            'last_updated_at' => 'updated_at',
        ],
        'notification' => [
            'id',
            'title',
            'message' => 'body',
            'read_at',
            'open_at',
            'created_at',
        ],
        'activity' => [
            'id',
            'type' => 'subject_type',
            'subject_type_key' => 'subject_type',
            'event',
            'event_key' => 'event',
            'created_at',
        ],
        'showcase' => [
            'id',
            'reference',
            'name',
            'translation_name' => 'name',
            'description',
            'translation_description' => 'description',
            'status',
            'display_status' => 'status',
            'priority',
            'display_priority' => 'priority',
            'visibility',
            'display_visibility' => 'visibility',
            'rating',
            'views_count',
            'sort_order',
            'is_active',
            'category' => 'category.name',
            'owner' => 'owner.name',
            'creator' => 'creator.name',
            'published_at',
            'expires_at',
            'remaining_days',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'showcase_category' => [
            'id',
            'name',
            'translation_name' => 'name',
            'description',
            'translation_description' => 'description',
            'code',
            'parent_id',
            'parent' => 'parent.name',
            'sort_order',
            'is_active',
            'creator' => 'creator.name',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
        'form' => [
            'id',
            'name',
            'translation_name' => 'name',
            'description',
            'translation_description' => 'description',
            'has_steps',
            'creator' => 'creator.name',
            'created_at',
        ],
        'form_submission' => [
            'id',
            'created_at',
        ],
        'system_event' => [
            'id',
            'name',
            'translation_name' => 'name',
            'module',
            'module_name' => 'module',
            'model_type',
            'event_slug',
            'created_at',
        ],
        'notification_event' => [
            'id',
            'name',
            'translation_name' => 'name',
            'type',
            'is_reminder',
            'created_at',
        ],
        'schedule_event' => [
            'id',
            'title',
            'translated_title' => 'title',
            'body',
            'translated_body' => 'body',
            'type',
            'display_type' => 'type',
            'status',
            'date' => 'date_time',
            'date_time',
            'created_at',
        ],
    ],

];
