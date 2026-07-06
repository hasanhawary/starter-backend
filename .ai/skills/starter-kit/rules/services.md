# Service Layer

Use this rule when creating or modifying business services, transactions, relation syncing, side effects, notifications, or multi-step writes.

## Philosophy

Controllers stay thin. Extract non-trivial writes, relation syncing, notifications, exports, imports, settings logic, and multi-step operations into services.

## When To Use A Service

**Use a service when** the controller has complex logic such as:
- Relation syncing (roles, permissions, tags, categories).
- Multi-step writes requiring `DB::transaction()`.
- Side effects like notifications, credential emails, or event dispatches.
- Ownership or root-protection authorization via `Gate::authorize()`/Policies.
- Settings, caching, or import/export orchestration.

**Do NOT use a service when** the controller is simple CRUD:
- `store` is just `Model::create($request->validated())`.
- `update` is just `$model->update($request->validated())`.
- No relation syncing, no notifications, no transactions needed.
- A basic data-entry resource (e.g., Country, City, Product, Category) with permission-middleware-only authorization.

For simple CRUD, the controller handles the write directly — no service class is needed. Adding a service to a simple CRUD controller adds unnecessary indirection with no benefit.

## Service Template

```php
class AdminService
{
    /**
     * @throws Throwable
     */
    public function store(AdminRequest $request): Admin
    {
        return DB::transaction(function () use ($request) {
            $admin = Admin::create($request->validated());
            $this->syncRelations($admin, $request);

            // Send notifications only after the database commit succeeds.
            DB::afterCommit(fn () => $this->sendCredentials($admin, $request));

            return $admin->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function update(Admin $admin, AdminRequest $request): Admin
    {
        return DB::transaction(function () use ($admin, $request) {
            $admin->update($request->validated());
            $this->syncRelations($admin, $request);

            DB::afterCommit(fn () => $this->sendCredentials($admin->refresh(), $request, isCreate: false));

            return $admin->refresh();
        });
    }

    public function syncRelations(Admin $admin, AdminRequest $request): void
    {
        when($request->filled('roles'), static fn () => $admin->syncRoles(Role::whereId($request->roles)->pluck('name')));
        when($request->filled('permissions'), static fn () => $admin->syncPermissions($request->permissions));
    }
}
```

## Service Rules

- Services live in `app/Services/{Domain}/`.
- Inject services through constructor promotion in controllers.
- Accept Form Request objects or explicit typed values, following sibling services.
- Return Eloquent models, arrays, or domain results; never return HTTP responses.
- Wrap multi-step writes in `DB::transaction()`.
- Dispatch side effects with `DB::afterCommit()` inside transactions.
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
