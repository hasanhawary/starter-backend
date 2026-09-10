# Skill: Permissions (`hasanhawary/permission-manager` + `spatie/laravel-permission`)

## How Permission Registration Works

The `permission-manager` package auto-generates permissions for models that have `$inPermission = true`.

**Model flags:**

```php
class Xxx extends BaseModel
{
    // Register this model with permission-manager
    public bool $inPermission = true;

    // Standard CRUD (auto-generated: create-xxx, update-xxx, delete-xxx)
    public array $basicOperations = ['create', 'update', 'delete'];

    // Extra permissions beyond CRUD
    public array $specialOperations = ['restore', 'force-delete', 'toggle-active', 'view-all', 'view-own'];
}
```

Permission names follow: `{operation}-{snake-case-model}`, e.g. `create-xxx`, `toggle-active-xxx`.

---

## Applying Permissions in Controllers

### Route-level (middleware — preferred for CRUD actions)

```php
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class XxxController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('create-xxx'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-xxx'), only: ['update']),
        ];
    }
}
```

### Action-level (Gate — for conditional/contextual checks)

```php
use Illuminate\Support\Facades\Gate;

public function index(): JsonResponse
{
    Gate::authorize('view', Xxx::class);   // policy method
    ...
}

public function show(Xxx $xxx): JsonResponse
{
    Gate::authorize('view', $xxx);         // instance-level policy
    ...
}
```

### HasDeleteMethods / HasToggleActiveMethods (automatic)

These traits auto-call `Gate::authorize()` if a policy exists, otherwise fall back to Spatie permission check (`{action}-{model}`). No extra code needed — just register the model policy if you want fine-grained control.

Disable automatic policy check when not needed:
```php
public function __construct()
{
    parent::__construct();
    $this->model = Xxx::class;
    $this->enableDeletePolicy(false);  // skip policy on delete
    $this->enableTogglePolicy(false);  // skip policy on toggle-active
}
```

---

## Writing a Policy

`app/Policies/XxxPolicy.php`

```php
namespace App\Policies;

use App\Models\Admin;
use App\Models\Xxx;

class XxxPolicy
{
    public function view(Admin $admin, Xxx $xxx): bool
    {
        return $admin->can('view-all-xxx') || $xxx->created_by === $admin->id;
    }

    public function create(Admin $admin): bool
    {
        return $admin->hasPermissionTo('create-xxx');
    }

    public function update(Admin $admin, Xxx $xxx): bool
    {
        return $admin->hasPermissionTo('update-xxx');
    }

    public function delete(Admin $admin, Xxx $xxx): bool
    {
        return $admin->hasPermissionTo('delete-xxx');
    }

    public function restore(Admin $admin, Xxx $xxx): bool
    {
        return $admin->hasPermissionTo('restore-xxx');
    }

    public function forceDelete(Admin $admin, Xxx $xxx): bool
    {
        return $admin->hasPermissionTo('force-delete-xxx');
    }

    public function toggleActive(Admin $admin, Xxx $xxx): bool
    {
        return $admin->hasPermissionTo('toggle-active-xxx');
    }
}
```

Register in `app/Providers/AuthServiceProvider.php` (or `AppServiceProvider`):
```php
Gate::policy(Xxx::class, XxxPolicy::class);
```

---

## Common Patterns

**View-all vs view-own (scoped listing):**
```php
// In a Scope trait (app/Scopes/{Domain}/XxxScopes.php)
public function scopeRelated(Builder $builder): void
{
    $builder->when(!auth()->user()->can('view-all-xxx'), function ($q) {
        $q->where('created_by', auth()->id());
    });
}

// In controller index():
Xxx::query()->related()...
```

**Root bypass:**
```php
if (isRoot()) {
    // root admin skips all permission checks
}
```
