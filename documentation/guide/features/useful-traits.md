---
title: Useful Traits
description: Common traits in `app/Trait/Global` and how to use them
---

# Useful Traits

This page documents the global traits located in `app/Trait/Global` and shows examples of how to use them in your models or controllers.

## Location

Traits are under `app/Trait/Global/` and are intended to provide reusable behavior across models and controllers.

## HasDeleteMethods

- Path: `app/Trait/Global/HasDeleteMethods.php`
- Purpose: Provide standard controller methods for soft-deleting, restoring, and force-deleting models in bulk with authorization and validation checks.
- Key methods:
  - `setDeleteModel(string $model)` — set the model class handled by the trait
  - `destroy(ModelBatchRequest $request)` — batch soft delete with policy checks
  - `restore(ModelBatchRequest $request)` — bulk restore
  - `forceDelete(ModelBatchRequest $request)` — bulk force delete
- Usage example (in a controller):

```php
use App\Trait\Global\HasDeleteMethods;

class MyController extends Controller
{
    use HasDeleteMethods;

    public function __construct()
    {
        $this->model = MyModel::class;
        $this->setDeleteGuards('delete', [function($model) {
            // return false to block deletion
            return ! $model->is_protected;
        }]);
    }
}
```

## CreatedByObserver

- Path: `app/Trait/Global/CreatedByObserver.php`
- Purpose: Automatically sets `created_by` on models on `creating` event.
- Usage: Add trait to an Eloquent model to enable automatic population of `created_by`.

```php
use App\Trait\Global\CreatedByObserver;

class Post extends Model
{
    use CreatedByObserver;
}
```

## HasOrder

- Path: `app/Trait/Global/HasOrder.php`
- Purpose: Helper methods to change the display/order field of a model and shift other records accordingly.
- Key method: `changeOrder(string $orderField, string $stepField, $request)`
- Usage example:

```php
$post = Post::find($id);
$post->changeOrder('order', 'category_id', $request);
```

## LogsActivityOptions

- Path: `app/Trait/Global/LogsActivityOptions.php`
- Purpose: Configure Spatie activity log default options for models that should be logged.
- Behavior: logs all attributes, only dirty changes, uses class basename as log name, optionally excludes attributes using `$logExceptAttributes` property.
- Usage example:

```php
class Post extends Model
{
    use LogsActivityOptions;

    protected array $logExceptAttributes = ['updated_at'];
}
```

## ApplyNotification

- Path: `app/Trait/Global/ApplyNotification.php`
- Purpose: Convenience wrapper to trigger notification sending via `NotificationService::resolve` from any model or service.
- Example:

```php
use App\Trait\Global\ApplyNotification;

class Order extends Model
{
    use ApplyNotification;

    public function notifyOrderCreated()
    {
        $this->sendNotification(['type' => 'order_created', 'order_id' => $this->id]);
    }
}
```

## HasToggleActiveMethods

- Path: `app/Trait/Global/HasToggleActiveMethods.php`
- Purpose: Provides toggle active/inactive functionality with authorization and callbacks.
- Key methods:
  - `toggleActive()` — Toggle `is_active` on selected records
  - `setToggleGuards($guards)` — Add guard callbacks
  - `beforeToggle($callback)` — Run before toggle
  - `afterToggle($callback)` — Run after toggle
- Usage example:

```php
use App\Trait\Global\HasToggleActiveMethods;

class UserController extends Controller
{
    use HasToggleActiveMethods;

    public function __construct()
    {
        $this->model = User::class;
        
        // Prevent deactivating root users
        $this->setToggleGuards([
            fn($model) => !$model->hasRole('root')
        ]);
    }
}
```

## Notes & Best Practices

- Traits are intended for shared behaviors — keep them small and focused.
- Use `CreatedByObserver` only on models with `created_by` column present.
- When using `HasDeleteMethods`, ensure policies and validation callbacks are set to avoid accidental deletes.
- Controllers typically combine `HasDeleteMethods` and `HasToggleActiveMethods`.

## See Also

- [Activity Logging](/guide/features/activity-logging) — Audit trail
- [Architecture](/guide/architecture) — Controller patterns
- [API Reference](/guide/api-reference) — see endpoints list

