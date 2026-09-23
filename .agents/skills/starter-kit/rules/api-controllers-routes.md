# API Controllers, Routes, And Resources

Use this rule when creating or modifying API controllers, routes, API resources, response envelopes, or endpoint contracts.

## Controller And Resource Bases

Root API controllers extend `App\Http\Controllers\API\BaseController`; initialize the parent constructor and `$this->model` when using shared delete/toggle traits. Resources extend `Illuminate\Http\Resources\Json\JsonResource`. Module-specific bases follow the target module's existing convention. Request and model base contracts live in [validation](validation.md) and [models](models-database.md).

## Response Envelope

All normal API responses use the project envelope.

```php
successResponse($data, $msg, $code = 200)
// { "status": true, "code": 200, "message": "...", "data": ... }

failResponse($msg, $data = [], $code = 400)
// { "status": false, "code": 400, "message": "...", "data": ... }
```

For lists:

```php
return successResponse(wrapPaginate($query, ProductResource::class));
```

Do not return raw resources directly from controllers unless the same area already intentionally does so.

## CRUD Source Patterns

### Entry points and exact source

All snapshot links below preserve the original path after `snapshots/`; their `.txt` suffix prevents treating the references as application PHP. The [manifest](../references/crud/source-manifest.json) includes the remaining nested resources and request base class.

| Pattern | Controller | Request | Resource |
| --- | --- | --- | --- |
| User | [UserController](../references/crud/snapshots/app/Http/Controllers/API/User/UserController.php.txt) | [UserRequest](../references/crud/snapshots/app/Http/Requests/User/UserRequest.php.txt) | [UserResource](../references/crud/snapshots/app/Http/Resources/User/UserResource.php.txt) |
| Country | [CountryController](../references/crud/snapshots/app/Http/Controllers/API/DataEntry/CountryController.php.txt) | [CountryRequest](../references/crud/snapshots/app/Http/Requests/DataEntry/CountryRequest.php.txt) | [CountryResource](../references/crud/snapshots/app/Http/Resources/DataEntry/CountryResource.php.txt) |

Both index methods use [PageRequest](../references/crud/snapshots/app/Http/Requests/Global/Other/PageRequest.php.txt). Their toggle trait uses [ToggleActiveRequest](../references/crud/snapshots/app/Http/Requests/Global/Other/ToggleActiveRequest.php.txt). All four requests extend [BaseFormRequest](../references/crud/snapshots/app/Http/Requests/BaseFormRequest.php.txt), which normalizes empty values and emits the established 422 `message` / `errors` validation response.

### User: service and Gate orchestration

- Constructor injects readonly `UserService`, initializes the base controller and model, and registers avatar cleanup only for the `force` delete callback.
- Index authorizes `view` against `User::class`; its query is `User::with(['roles', 'departments', 'space'])->related()`. Pipeline order is `UserFilter`, `ActiveFilter`, `TrashedFilter`, `OrderByFilter`.
- Store authorizes `create` against the class; update authorizes `update` against the bound instance; show authorizes `view` against the bound instance. Do not substitute `viewAny` merely because it is common elsewhere.
- Store/update delegate to `UserService`; the controller then loads `departments` and `space`. Show loads `roles`, `departments`, and `space`. The live User model also declares default loading of `phoneCode`.
- The service wraps persistence and role/permission synchronization in `DB::transaction()`, schedules `sendCredentials()` using `DB::afterCommit()`, and returns refreshed models. Inspect its exact conditions before reproducing notification behavior: callback registration does not guarantee that every update sends a notification.
- `UserRequest` gets the uniqueness exclusion from route parameter `user`. It normalizes a nested phone payload into `phone` / `phone_code_id`, wraps scalar roles, and calls parent preparation first. Preserve its custom rules, enum validation, root-role exclusion, optional password and avatar semantics when maintaining this feature. These fields are domain-specific examples, not mandatory CRUD fields.
- `UserResource` projects nested phone data, enum display, roles, departments, space, creator, settings, and timestamps. Its conditional relationships deliberately have different defaults (`[]`, `null`, empty string, or an id object). No dedicated list/detail resource split exists.

### Country: inline reference-data persistence

- Implements `HasMiddleware`; static middleware applies `create-country` only to store and `update-country` only to update. The controller itself has no Gate calls in index/show. This does not mean the routes or trait methods lack authorization; inspect the route group and shared traits.
- Index starts with `Country::query()`. Pipeline order is `JsonNameFilter`, `TrashedFilter`, `ActiveFilter`, `OrderByFilter`.
- Store calls `Country::create($request->validated())`; update calls `$country->update($request->validated())`. Both refresh the model for `CountryResource`; show returns the bound model through that same resource.
- `CountryRequest` validates translated `name` / `nationality` arrays using `TranslatableRequired`; name also has `UniqueCheck`. Code uniqueness excludes trashed rows and ignores the route-bound `country`. Flag accepts an optional nullable image; phone fields follow the exact rules in the snapshot. Do not tighten these rules while merely copying a reference.
- `CountryResource` exposes localized `translation_name` / `translation_nationality` alongside full `name` / `nationality` translation maps. It also emits flag, code, phone code/length, and created timestamp. Do not replace the maps with strings or add unrequested fields.
- The live Country model owns translatable attributes and flag upload/replacement/accessor behavior. Inline validated persistence therefore does not imply that media has no lifecycle behavior.

### Shared route and trait integration

Inspect live `routes/api.php`, `app/Http/Controllers/API/BaseController.php`, `app/Trait/Global/HasDeleteMethods.php`, and `app/Trait/Global/HasToggleActiveMethods.php` when applying either pattern.

The source uses plural `users` / `countries` prefixes and singular binding parameters `user` / `country`. Follow the Routes example below; parameter names must match Form Request route lookups and controller binding.

The delete trait supplies the methods and its own policy/permission authorization, guards, and callbacks. The toggle trait validates ids, locks selected models within a transaction, authorizes, and updates their active state. Do not infer their behavior from the controller middleware alone or replace bulk action routes with a conventional single-record destroy signature.

Inspect live `app/Helpers/App.php` for `successResponse()` and `wrapPaginate()`. Preserve their existing pagination and response behavior rather than claiming that a generic Laravel resource collection is equivalent.


## Routes

Protected resources use `auth:sanctum`; trait endpoints are explicit and stay outside `apiResource` destroy.

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('products')->group(function () {
        Route::delete('force-delete', [ProductController::class, 'forceDelete']);
        Route::delete('delete', [ProductController::class, 'destroy']);
        Route::post('restore', [ProductController::class, 'restore']);
        Route::put('toggle-active', [ProductController::class, 'toggleActive']);

        Route::apiResource('/', ProductController::class)
            ->parameters(['' => 'product'])
            ->except(['destroy']);
    });
});
```

Define custom routes before resource routes if they could conflict.

## Resource: Translatable Data Entry

```php
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Current translated value for display.
            'translation_name' => $this->name,

            // Full translation object for edit forms.
            'name' => $this->getTranslations('name'),

            'translation_description' => $this->description,
            'description' => $this->getTranslations('description'),
            'image' => $this->image,
            'code' => $this->code,
            'created_at' => $this->created_at,
        ];
    }
}
```

## Resource: Relations And Enums

```php
class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => [
                'phone' => $this->phone,
                'phone_code' => $this->whenLoaded('phoneCode', fn () => $this->phoneCode?->phone_code, ''),
                'phone_code_id' => $this->phone_code_id,
            ],
            'gender' => $this->gender,
            'display_gender' => UserGenderEnum::resolve($this->gender),
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,

            // Always guard relations with whenLoaded to avoid lazy-loading.
            'roles' => $this->whenLoaded('roles', fn () => BasicResource::collection($this->roles), []),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'settings' => $this->whenLoaded('settings', fn () => new UserSettingResource($this->settings), ['id' => null]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## Resource Rules

- Include only API-safe fields.
- Use `whenLoaded()` for relations.
- For translatable fields, return `translation_{field}` for current-locale value and `{field}` for the full translation object.
- For enums/resolved labels, return `display_{field}` beside the raw field when sibling resources do so.
- Do not query inside resources.
- Keep validation field names, request body keys, and resource output keys aligned with the API contract.

## Generating Or Extending CRUD

1. Trace the closest controller through its request, resource, model, routes, filters, authorization, shared traits, service, translations, and tests. Search consumers before changing signatures or output.
2. Choose Country's inline validated writes for simple reference data or User's service boundary for non-trivial related writes and side effects. Gate authorization alone does not require a service. Use the target feature's fields, route binding, permissions, and relationships.
3. Preserve the existing bases and constructor/model initialization, filter order, query scope, response helpers, and translation keys. Inspect traits before creating duplicate delete/restore/toggle methods.
4. Both source controllers use one resource for list, show, and write responses; add summary/detail variants only if the target contract requires them. Preserve relation fallback values and eager-load the needed projections.
5. When adding a field, update migration, fillable/casts, request rules, resource, searchable filters, translations, and focused tests together. Verify auth, binding, validation, filters, pagination, and trait-backed actions using [verification guidance](review-debug-refactor.md).

## API Contract Alignment

Keep route prefixes, incoming field keys, output keys, and nested relationship shapes compatible with consumers. Search and sort parameters must match the Pipeline's keys (`sort_column` / `sort_direction` for sorting). Preserve the paginated response envelope and 422 field-key validation errors. Backend scope still includes API contract alignment.

## Frozen Source References

The [CRUD manifest](../references/crud/source-manifest.json) retains provenance and SHA-256 hashes for 16 complete controller/request/resource snapshots. They are reference evidence, not a runnable module. Supporting services/models/traits/helpers were traced but must be inspected in the current project before reuse. To refresh, copy source bytes and update manifest hashes; never silently edit the frozen snapshots. Current validation conventions are in [validation.md](validation.md), including distinctions from historical request examples.
