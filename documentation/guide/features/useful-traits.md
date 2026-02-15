---
title: Useful Traits
description: Reusable traits for common functionality
---

# Useful Traits

Your project includes 6 reusable traits that provide common functionality across models and controllers.

## CreatedByObserver

Automatically sets the `created_by` field to the current authenticated user's ID when a model is created.

**Location:** `app/Trait/Global/CreatedByObserver.php`

**Features:**
- Automatically sets `created_by = auth()->id()` on creation
- Only works if user is authenticated
- Uses model boot method

**Usage:**
```php
use App\Trait\Global\CreatedByObserver;

class Post extends Model
{
    use CreatedByObserver;
    
    protected $fillable = ['title', 'content', 'created_by'];
}

// When creating:
$post = Post::create(['title' => 'My Post']);
// created_by is automatically set to auth()->id()
```

## ApplyNotification

Provides a `sendNotification()` method to send notifications through the NotificationService.

**Location:** `app/Trait/Global/ApplyNotification.php`

**Method Signature:**
```php
public function sendNotification(array $data, ?array $types = ['notify', 'realtime']): void
```

**Parameters:**
- `$data` - Notification data array (title, msg, url, urlText)
- `$types` - Notification channels: 'notify', 'realtime', 'email', 'sms'

**Usage:**
```php
use App\Trait\Global\ApplyNotification;

class User extends Model
{
    use ApplyNotification;
}

// Send notification
$user->sendNotification([
    'title' => 'Welcome',
    'msg' => 'Welcome to our platform',
    'url' => '/dashboard',
    'urlText' => 'Go to Dashboard'
], ['notify', 'realtime']);
```

## LogsActivityOptions

Provides default activity logging configuration using Spatie Activity Log. Logs all dirty (changed) attributes with the model class name as the log name.

**Location:** `app/Trait/Global/LogsActivityOptions.php`

**Features:**
- Logs all attributes
- Only logs dirty (changed) attributes
- Uses class name as log name
- Prevents empty logs
- Respects `$logExceptAttributes` property if defined

**Usage:**
```php
use App\Trait\Global\LogsActivityOptions;

class User extends Model
{
    use LogsActivityOptions;
    
    protected $fillable = ['name', 'email', 'is_active'];
    protected $logExceptAttributes = ['password'];  // Optional
}

// Retrieve logs
$user = User::find(1);
foreach ($user->activities as $activity) {
    echo $activity->description;      // 'created', 'updated', 'deleted'
    echo $activity->causer->name;     // User who made change
    echo $activity->created_at;       // When change occurred
}
```

## HasDeleteMethods

Provides `destroy()`, `restore()`, and `forceDelete()` methods with policy-based authorization, custom guards, and before/after callbacks.

**Location:** `app/Trait/Global/HasDeleteMethods.php`

**Configuration Methods:**
```php
protected function setDeleteModel(string $model): self
protected function enableDeletePolicy(bool $state = true): self
protected function setDeleteGuards(string $action, callable|array $guards): self
protected function beforeDelete(string $action, callable|array $callback): self
protected function afterDelete(string $action, callable|array $callback): self
```

**Available Actions:**
- `destroy()` - Soft delete
- `restore()` - Restore soft-deleted records
- `forceDelete()` - Permanent delete

**Usage:**
```php
use App\Trait\Global\HasDeleteMethods;

class UserController extends Controller
{
    use HasDeleteMethods;

    public function __construct()
    {
        $this->setDeleteModel(User::class)
            ->enableDeletePolicy(true);
    }
}

// Routes
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::post('/users/{id}/restore', [UserController::class, 'restore']);
Route::delete('/users/{id}/force', [UserController::class, 'forceDelete']);
```

**With Guards:**
```php
public function __construct()
{
    $this->setDeleteModel(User::class)
        ->setDeleteGuards('delete', function ($model) {
            return $model->id !== auth()->id();  // Can't delete self
        });
}
```

**With Callbacks:**
```php
public function __construct()
{
    $this->setDeleteModel(User::class)
        ->beforeDelete('delete', function ($model) {
            Log::info("Deleting user: {$model->id}");
        })
        ->afterDelete('delete', function ($model) {
            $model->tokens()->delete();
        });
}
```

## HasToggleActiveMethods

Provides `toggleActive()` method to toggle the `is_active` field with policy-based authorization and callbacks.

**Location:** `app/Trait/Global/HasToggleActiveMethods.php`

**Configuration Methods:**
```php
protected function setToggleModel(string $model): self
protected function enableTogglePolicy(bool $state = true): self
protected function setToggleGuards(callable|array $guards): self
protected function beforeToggle(callable|array $callback): self
protected function afterToggle(callable|array $callback): self
```

**Usage:**
```php
use App\Trait\Global\HasToggleActiveMethods;

class UserController extends Controller
{
    use HasToggleActiveMethods;

    public function __construct()
    {
        $this->setToggleModel(User::class);
    }
}

// Route
Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
```

**Response:**
```json
{
  "success": true,
  "message": "User activated successfully",
  "code": 200
}
```

**With Callbacks:**
```php
public function __construct()
{
    $this->setToggleModel(User::class)
        ->beforeToggle(function ($model) {
            Log::info("Toggling user: {$model->id}");
        })
        ->afterToggle(function ($model) {
            $model->sendNotification([
                'title' => 'Status Changed',
                'msg' => 'Your account status has been changed'
            ]);
        });
}
```

## HasOrder

Provides ordering functionality for models with support for reordering items.

**Location:** `app/Trait/Global/HasOrder.php`

**Method Signature:**
```php
public function changeOrder(string $orderField, string $stepField, $request): void
```

**Features:**
- Changes order of a model instance
- Automatically adjusts other items' order
- Supports step field for grouping
- Uses database transactions

**Usage:**
```php
use App\Trait\Global\HasOrder;

class Category extends Model
{
    use HasOrder;
    
    protected $fillable = ['name', 'order', 'step'];
}

// Change order
$category = Category::find(1);
$category->changeOrder('order', 'step', $request);
// $request should contain the new 'order' and 'step' values
```

**Example Request:**
```php
// Move category to position 3
$request->merge(['order' => 3, 'step' => 1]);
$category->changeOrder('order', 'step', $request);
```
