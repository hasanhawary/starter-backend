---
title: Useful Traits
description: Reusable traits in app/Trait/Global for controllers and models
---

# Useful Traits

This page documents the global traits located in `app/Trait/Global/` that provide reusable behavior for controllers and models.

## Controller Traits

### HasDeleteMethods

Provides standardized batch delete, restore, and force-delete operations with authorization, guards, and callbacks.

**Location:** `app/Trait/Global/HasDeleteMethods.php`

**Usage:**
```php
use App\Trait\Global\HasDeleteMethods;

class UserController extends BaseController
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = User::class;

        // Optional: Run before force delete
        $this->beforeDelete('force', fn(User $user) => Media::delete($user->avatar));

        // Optional: Run after delete
        $this->afterDelete('delete', fn(User $user) => Log::info("Deleted user: {$user->id}"));
    }
}
```

**Provided Methods:**
```php
// Soft delete (DELETE /users/delete)
public function destroy(): JsonResponse

// Restore soft-deleted (POST /users/restore)
public function restore(): JsonResponse

// Permanent delete (DELETE /users/force-delete)
public function forceDelete(): JsonResponse
```

**Configuration Methods:**
```php
// Set the model class
$this->setDeleteModel(User::class);

// Enable/disable policy checks
$this->enableDeletePolicy(true);

// Add custom guards (return false to block)
$this->setDeleteGuards('force', fn(User $model) => !$model->is_protected);

// Before/after callbacks
$this->beforeDelete('delete', fn($model) => /* cleanup */);
$this->afterDelete('restore', fn($model) => /* notify */);
```

**Request Format:**
```json
{
    "ids": [1, 2, 3]
}
// or
{
    "id": 1
}
```

---

### HasToggleActiveMethods

Provides batch toggle for `is_active` status with authorization and callbacks.

**Location:** `app/Trait/Global/HasToggleActiveMethods.php`

**Usage:**
```php
use App\Trait\Global\HasToggleActiveMethods;

class UserController extends BaseController
{
    use HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = User::class;

        // Optional callbacks
        $this->beforeToggle(fn(User $user) => Log::info("Toggling: {$user->id}"));
        $this->afterToggle(fn(User $user) => $user->sendNotification([
            'type' => 'status_changed',
            'msg' => $user->is_active ? 'activated' : 'deactivated',
        ]));
    }
}
```

**Provided Method:**
```php
// Toggle active status (PUT /users/toggle-active)
public function toggleActive(): JsonResponse
```

**Configuration Methods:**
```php
// Set the model class
$this->setToggleModel(User::class);

// Enable/disable policy checks
$this->enableTogglePolicy(true);

// Add custom guards
$this->setToggleGuards(fn(User $model) => !$model->is_protected);

// Before/after callbacks
$this->beforeToggle(fn($model) => /* check */);
$this->afterToggle(fn($model) => /* notify */);
```

---

## Model Traits

### CreatedByObserver

Automatically sets `created_by` column to the authenticated user's ID on model creation.

**Location:** `app/Trait/Global/CreatedByObserver.php`

**Usage:**
```php
use App\Trait\Global\CreatedByObserver;

class Post extends Model
{
    use CreatedByObserver;

    protected $fillable = ['title', 'content', 'created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

**How it works:**
```php
public static function bootCreatedByObserver(): void
{
    if (auth()->check()) {
        static::creating(static fn(Model $model) => $model->created_by = auth()?->id());
    }
}
```

::: tip
Ensure your migration includes `created_by` column:
```php
$table->foreignId('created_by')->nullable()->constrained('users');
```
:::

---

### LogsActivityOptions

Configures Spatie Activity Log with sensible defaults for models.

**Location:** `app/Trait/Global/LogsActivityOptions.php`

**Usage:**
```php
use App\Trait\Global\LogsActivityOptions;

class Post extends Model
{
    use LogsActivityOptions;

    // Optional: exclude specific attributes from logging
    protected array $logExceptAttributes = ['updated_at', 'remember_token'];
}
```

**Default Configuration:**
```php
public function getActivitylogOptions(): LogOptions
{
    $logOptions = LogOptions::defaults()
        ->logAll()                              // Log all attributes
        ->logOnlyDirty()                        // Only log changed values
        ->useLogName(class_basename($this))     // Use model name as log name
        ->dontSubmitEmptyLogs();                // Skip if nothing changed

    if (property_exists($this, 'logExceptAttributes')) {
        $logOptions->logExcept($this->logExceptAttributes);
    }

    return $logOptions;
}
```

---

### ApplyNotification

Provides a convenient method to send notifications from models.

**Location:** `app/Trait/Global/ApplyNotification.php`

**Usage:**
```php
use App\Trait\Global\ApplyNotification;

class User extends Authenticatable
{
    use ApplyNotification;
}

// In your code
$user->sendNotification([
    'type' => 'order_created',
    'title' => 'notifications.new_order',
    'msg' => 'notifications.order_msg|order_id=' . $order->id,
], ['notify', 'realtime', 'email']);
```

**Implementation:**
```php
public function sendNotification(array $data, ?array $types = ['notify', 'realtime']): void
{
    NotificationService::resolve($this, $data, $types);
}
```

**Channel Types:**
- `notify` — Database notification
- `realtime` — WebSocket broadcast
- `email` — Send email
- `sms` — Send SMS

---

### HasOrder

Provides methods to change the display order of items and shift other records accordingly.

**Location:** `app/Trait/Global/HasOrder.php`

**Usage:**
```php
use App\Trait\Global\HasOrder;

class MenuItem extends Model
{
    use HasOrder;

    protected $fillable = ['name', 'order', 'category_id'];
}

// In controller
public function updateOrder(Request $request, MenuItem $menuItem)
{
    $menuItem->changeOrder('order', 'category_id', $request);

    return successResponse(new MenuItemResource($menuItem->refresh()));
}
```

**Method:**
```php
/**
 * Change the order of a model instance.
 *
 * @param string $orderField   The field storing the order value
 * @param string $stepField    The grouping field (e.g., category_id)
 * @param Request $request     Request containing new order value
 */
public function changeOrder(string $orderField, string $stepField, $request): void
```

**How it works:**
- Wraps operation in a database transaction
- Shifts other items up or down to make room
- Updates the current item's order value

## Using Multiple Traits

Controllers often combine multiple traits:

```php
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;

class ProductController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Product::class;

        // Configure delete
        $this->beforeDelete('force', fn(Product $p) => Media::delete($p->image));

        // Configure toggle
        $this->afterToggle(fn(Product $p) => Cache::forget("product:{$p->id}"));
    }
}
```

Models combine traits for full functionality:

```php
class Product extends BaseModel
{
    use SoftDeletes;
    use CreatedByObserver;
    use LogsActivityOptions;
    use ApplyNotification;

    public bool $inPermission = true;
    public array $basicOperations = ['create', 'update', 'delete'];
    public array $specialOperations = ['view-all', 'view-own', 'restore', 'force-delete', 'toggle-active'];
}
```


## See Also

- [Architecture](/guide/architecture) — Controller and model patterns
- [Notifications](/guide/features/notifications) — NotificationService details
- [Activity Logging](/guide/features/activity-logging) — Spatie Activity Log
- [Permission Manager](/guide/tools/permission-manager) — Model permissions
