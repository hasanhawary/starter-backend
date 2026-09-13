# Service Layer

Use this rule when creating or modifying business services, transactions, relation syncing, side effects, notifications, or multi-step writes.

## Philosophy

Controllers stay thin. Extract non-trivial writes, notifications, exports, imports, settings logic, and multi-step operations into services — but **not** the transaction boundary (that stays in the controller action) and **not** relation writes (those belong to the model that owns the relation).

## When To Use A Service

**Use a service when** the controller has complex logic such as:
- Several coordinated writes on the record itself.
- Side effects like notifications, credential emails, or event dispatches.
- Settings, caching, or import/export orchestration.
- A second entry point — a command, job, or MCP tool — that must not bypass the same invariants.

Relation syncing is *not* on that list: `syncRoles()`, `syncTags()` and friends live on the model that owns the relation, service or no service.

**Do NOT use a service when** the controller is simple CRUD:
- `store` is just `Model::create($request->validated())`.
- `update` is just `$model->update($request->validated())`.
- No relation syncing, no notifications, no transactions needed.
- A basic data-entry resource (e.g., Country, City, Product, Category) with permission-middleware-only authorization.

For simple CRUD, the controller handles the write directly — no service class is needed. Adding a service to a simple CRUD controller adds unnecessary indirection with no benefit.

## Service Template

The controller owns the transaction; the service owns the steps inside it.

```php
// Controller
public function store(AdminRequest $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $data = $request->validated();

        $admin = $this->service->store($data);
        $admin->syncAccess($data['roles'] ?? [], $data['permissions'] ?? []);

        return successResponse(new AdminResource($admin->loadDetailData()), __('api.created_success'));
    });
}
```

```php
// Service — no DB::transaction() anywhere in it
class AdminService
{
    public function store(array $data): Admin
    {
        $admin = Admin::create($data);

        $this->sendCredentials($admin, $data);

        return $admin;
    }

    public function update(Admin $admin, array $data): Admin
    {
        $admin->update($data);

        $this->sendCredentials($admin->refresh(), $data, isCreate: false);

        return $admin;
    }

    private function sendCredentials(Admin $admin, array $data, bool $isCreate = true): void { /* ... */ }
}
```

Three things the service does *not* do: open a transaction, write a relation, or authorize.

## Service Rules

- **`store()` and `update()` stay two separate public methods.** Never merge them into a single `saveX(array $data, ?Model $model = null)`. The create path and the update path differ in their side effects — a create announces a new record and stamps the creator, an update announces a change — and a null-model branch hides that difference behind one signature. Put whatever is genuinely shared in a private helper both call.
- Signature order is the model first, then the payload: `update(User $user, UserRequest $request)`, `syncRelations(User $user, UserRequest $request)`. `store()` takes only the payload because there is no model yet.
- Beyond that pair, name methods after the domain operation, not the endpoint: `syncRelations()`, `publish()`, `sendCredentials()`, `announceActivationChange()`.
- **`DB::transaction()` never appears inside a service.** The controller action opens the boundary and the service supplies the steps that run inside it, so one action can compose several service calls plus a model sync and still commit or roll back as one unit. A service that opens its own transaction makes that impossible and nests a second one when the caller already has one open.
- **Relation writes are not service surface.** `syncRoles()`, `syncTags()`, attach/detach and replace-children live on the model that owns the relation — in a service-backed feature too. The service owns the record's own columns and the side effects of changing them.
- **Do not wrap a queued job in `DB::afterCommit()` when the job already defers itself.** `Notification::send()` dispatches `SendNotificationJob`, whose constructor calls `$this->afterCommit()`; wrapping it adds a second deferral that does nothing in production and quietly changes what the test fakes can see. Reserve `DB::afterCommit()` for external effects that are *not* self-deferring — a `Mail::send()` that is not queued, an HTTP call, a filesystem write.
- Services live in `app/Services/{Domain}/`.
- Inject services through constructor promotion in controllers.
- Accept Form Request objects or explicit typed values, following sibling services.
- Return Eloquent models, arrays, or domain results; never return HTTP responses.
- Wrap multi-step writes in `DB::transaction()` — in the controller action, never in the service.
- Dispatch a non-self-deferring side effect with `DB::afterCommit()`; a job that already calls `afterCommit()` needs no wrapper.
- Keep private/public helper methods for internal sub-operations following current service style.
- Do not call `Gate::authorize()` in services.
- Do not read from `request()` inside services.
- Do not validate in services; use Form Requests.

## Settings/Cache Service Example

```php
class SettingService
{
    protected string $cacheKeyPrefix = 'settings_';

    /**
     * Get all settings as nested associative array, cached.
     */
    public function all(): array
    {
        $brand = brandName();

        return Cache::rememberForever($this->cacheKeyPrefix.$brand, function () {
            $settings = Setting::all();
            $nested = [];

            foreach ($settings as $setting) {
                $keys = explode('.', $setting->group ?: 'general'); // group path
                $current = &$nested;

                foreach ($keys as $key) {
                    if (! isset($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }

                // Store the actual value + meta.
                $current[$setting->key] = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'is_multi_lang' => $setting->is_multi_lang,
                    'placeholder' => $setting->placeholder,
                    'label' => $setting->label,
                ];
            }

            return $nested;
        });
    }
}
```

## Helper Rules

Use existing helpers from `app/Helpers/App.php` before adding new helpers.

- Response: `successResponse()`, `failResponse()`, `abort403()`, `unKnownError()`.
- Pagination: `wrapPaginate()`.
- Translation/formatting: `resolveTrans()`, `transWithParams()`, `buildDelimiterMessage()`.
- Values: `resolveBool()`, `resolveArray()`, `resolveEmptyLang()`, `resolveEmptyToNull()`.
- Model/class helpers: `getModelKey()`, `detectModelPath()`, `getModelTranslatable()`, `resolveModel()`, `resolveClass()`.
- Auth/config helpers: `getAuthUser()`, `getAuthGuard()`, `shouldVerifyOtp()`, `brandName()`, `setting()`.

Only add a helper if it is cross-cutting and reusable. Domain-specific logic belongs in a service.
