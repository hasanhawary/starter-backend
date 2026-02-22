---
title: Helper Functions
description: Global helper functions available throughout the application
---

# Helper Functions

The Admin Dashboard Kit provides numerous helper functions in `app/Helpers/App.php` that are auto-loaded and available globally throughout the application.

## Response Helpers

### successResponse

Returns a standardized success JSON response.

```php
function successResponse($data = [], $msg = null, $code = 200): JsonResponse

// Usage
return successResponse(new UserResource($user), __('api.created_success'), 201);

// Output
{
    "status": true,
    "code": 201,
    "message": "Created successfully",
    "data": { ... }
}
```

### failResponse

Returns a standardized error JSON response.

```php
function failResponse($msg = 'fail', $data = [], $code = 400): JsonResponse

// Usage
return failResponse(__('api.record_not_found'), [], 404);

// Output
{
    "status": false,
    "code": 404,
    "message": "Record not found!",
    "data": []
}
```

### abort403

Throws a 403 Forbidden exception if condition is true.

```php
function abort403($condition = true): void

// Usage
abort403(!$user->can('edit-post'));
```

### unKnownError

Returns a generic error response, includes debug info in development.

```php
function unKnownError($message = null): JsonResponse|RedirectResponse

// Usage
return unKnownError($exception->getMessage());
```

## Data Helpers

### wrapPaginate

Paginates query results and transforms with a resource class.

```php
function wrapPaginate(Builder $query, $resource = null, $meta = [])

// Usage - Paginated (request->per_page = 10)
return successResponse(wrapPaginate($query, UserResource::class));

// Usage - All records (request->per_page  = -1 or null)
return successResponse(wrapPaginate($query, UserResource::class));

// With meta data
return successResponse(wrapPaginate($query, UserResource::class, ['total_active' => 100]));
```

### resolveEmptyToNull

Converts empty strings and arrays to null recursively.

```php
function resolveEmptyToNull($value)

// Usage
$data = resolveEmptyToNull($request->all());
// '' becomes null
// [] becomes null
// 'null' string becomes null
```

### resolveModel

Gets a model instance by name, supports modules.

```php
function resolveModel(string $name, $module = null): ?object

// Usage
$model = resolveModel('user');           // App\Models\User
$model = resolveModel('post', 'blog');   // Modules\Blog\App\Models\Post
```

### resolveArray

Ensures a value is an array.

```php
function resolveArray(string|array $array): array

// Usage
$ids = resolveArray($request->ids);  // Always returns array
$ids = resolveArray('1,2,3');        // Returns ['1', '2', '3']
```

### resolveTrans

Translates a key with fallback to original value.

```php
function resolveTrans($trans = '', $page = 'api', $lang = null, $snaked = true): ?string

// Usage
$label = resolveTrans('user_name');  // Returns translated or 'user_name'
```

### resolveBool

Converts boolean to localized yes/no string.

```php
function resolveBool($item): string

// Usage
resolveBool(1);  // Returns "Yes"
resolveBool(0);  // Returns "No"
```

## Utility Helpers

### isRoot

Checks if user has the root role.

```php
function isRoot($user = null): bool

// Usage
if (isRoot()) {
    // Current user is root
}
if (isRoot($someUser)) {
    // Specific user is root
}
```

### isBase64

Checks if a string is valid base64 encoded.

```php
function isBase64($data): bool

// Usage
if (isBase64($imageData)) {
    // Process base64 image
}
```

### getModelKey

Gets the snake_case key name for a model class.

```php
function getModelKey(?string $className = null, $trans = false): ?string

// Usage
getModelKey(User::class);           // Returns 'user'
getModelKey(UserRole::class);       // Returns 'user_role'
getModelKey(User::class, true);     // Returns translated name
```

### updateDotEnv

Updates .env file values programmatically.

```php
function updateDotEnv(array $data = []): void

// Usage
updateDotEnv([
    'APP_NAME' => 'New App Name',
    'DB_DATABASE' => 'new_database',
    'DEBUG' => true,  // Becomes 'true' string
]);
```

### when

Executes a closure if condition is truthy.

```php
function when(mixed $condition, callable $closure): void

// Usage
when($request->filled('roles'), fn() => $user->syncRoles($request->roles));
when($collection->isNotEmpty(), fn() => processItems($collection));
```

### logError

Logs an exception with file, line, and message details.

```php
function logError($exception): void

// Usage
try {
    // risky operation
} catch (Exception $e) {
    logError($e);
}
```

## Translation Helpers

### transWithParams

Translates a key with dynamic parameters.

```php
function transWithParams(?string $data, string $page = 'emails', array $params = []): ?string

// Usage - Inline params with pipe syntax
transWithParams('welcome|name=John|role=admin');
// Translates 'emails.welcome' with ['name' => 'John', 'role' => 'admin']

// Usage - Array params
transWithParams('order_confirmed', 'emails', ['order_id' => 123]);
```

### emailTrans

Shorthand for email translations with brand name.

```php
function emailTrans(?string $data, array $params = []): ?string

// Usage
emailTrans('welcome_message');  // Includes platform_name automatically
```

## Settings Helpers

### setting

Gets a setting value by path.

```php
function setting(string $path, ?string $lang = null, $default = null): mixed

// Usage
$appName = setting('general.info.name');
$appName = setting('general.info.name', 'ar');  // Arabic version
$color = setting('theme.colors.primary_color', null, '#000000');
```

### brandName

Gets the brand name from settings.

```php
function brandName(bool $display = true): string

// Usage
$name = brandName();        // Localized display name
$name = brandName(false);   // Config brand name
```

### brandSettings

Gets all brand settings as an array.

```php
function brandSettings(?string $lang = null): array

// Returns logo URLs, theme colors, contact info, social links
$brand = brandSettings('en');
$brand['logo']['lg'];           // Large logo URL
$brand['theme']['primary'];     // Primary color
$brand['contact']['email'];     // Contact email
```

## Model Helpers

### rootUsers

Gets array of user IDs with root role.

```php
function rootUsers(): array

// Usage
$rootIds = rootUsers();
User::whereNotIn('id', rootIds())->get();  // Exclude root users
```

### allModelsNames

Gets collection of all model class names.

```php
function allModelsNames(): Collection

// Returns: ['User', 'Role', 'Permission', 'Setting', ...]
```

### allAttributesFillableModels

Gets all fillable attributes across all models.

```php
function allAttributesFillableModels(): array

// Returns unique array of all fillable field names
```

### prepareModelType

Extracts lowercase model name from full class path.

```php
function prepareModelType($model): string

// Usage
prepareModelType(App\Models\User::class);  // Returns 'user'
```


## Encryption Helpers

### encryptCode

Encrypts data for frontend using AES-256-CBC.

```php
function encryptCode(array $data): array

// Usage
$encrypted = encryptCode(['token' => $token, 'user' => $userData]);
// Returns: ['payload' => '...', 'iv' => '...']
```

## Authentication Helpers

### getCurrentGuard

Gets the currently authenticated guard name.

```php
function getCurrentGuard(): int|string|null

// Usage
$guard = getCurrentGuard();  // Returns 'api', 'web', etc.
```

### shouldVerifyOtp

Checks if OTP verification is required for a model.

```php
function shouldVerifyOtp(): bool

// Usage
if (shouldVerifyOtp()) {
    // Send OTP
}
```


## See Also

- [Architecture](/guide/architecture) — How helpers fit in the application
- [Custom Rules](/guide/features/custom-rules) — Validation rules
- [Settings](/guide/features/settings) — Settings management
