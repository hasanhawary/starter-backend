---
title: Global Helpers
description: Helper functions available throughout the application
---

# Global Helpers

The application provides global helper functions in `app/Helpers/App.php` that are auto-loaded via Composer.

## Response Helpers

### successResponse()

Returns a standardized success JSON response.

```php
function successResponse($data = [], $msg = null, $code = 200): JsonResponse

// Usage
return successResponse($user);
return successResponse(new UserResource($user), __('api.created_success'));
return successResponse(['token' => $token], 'Login successful', 200);
```

**Response format:**
```json
{
  "status": true,
  "code": 200,
  "message": "Success",
  "data": { ... }
}
```

### failResponse()

Returns a standardized error JSON response.

```php
function failResponse($msg = 'fail', $data = [], $code = 400): JsonResponse

// Usage
return failResponse(__('api.not_found'));
return failResponse('Validation failed', $errors, 422);
```

### abort403()

Throws a 403 Forbidden exception if condition is true.

```php
function abort403($condition = true): void

// Usage
abort403(!$user->canEdit($post));
```

### unKnownError()

Returns a generic error response.

```php
function unKnownError($message = null): JsonResponse|RedirectResponse

// Usage in catch blocks
return unKnownError($e->getMessage());
```

---

## Data Helpers

### fetchData()

Handles pagination and resource transformation in one call.

```php
function fetchData(Builder $query, string|int|null $pageSize = null, $resource = null, $meta = [])

// Usage
return successResponse(fetchData($query, $request->pageSize, UserResource::class));

// With additional metadata
return successResponse(fetchData($query, 20, UserResource::class, ['total_active' => $activeCount]));

// No pagination (pageSize = -1)
return successResponse(fetchData($query, -1, CountryResource::class));
```

### resolveEmptyToNull()

Converts empty strings and arrays to null (used in BaseFormRequest).

```php
function resolveEmptyToNull($value)

// '', 'null', [] → null
// Recursively processes arrays
```

### resolveModel()

Gets a model instance by name (optionally from a module).

```php
function resolveModel(string $name, $module = null): ?object

// Usage
$model = resolveModel('users');        // App\Models\User
$model = resolveModel('posts', 'Blog'); // Modules\Blog\App\Models\Post
```

---

## Translation Helpers

### resolveTrans()

Translates a key with optional page prefix.

```php
function resolveTrans($trans = '', $page = 'api', $lang = null, $snaked = true): ?string

// Usage
resolveTrans('user_created');           // trans('api.user_created')
resolveTrans('active', 'validation');   // trans('validation.active')
```

### resolveBool()

Converts boolean to translated yes/no.

```php
function resolveBool($item): string

// 1 → __('api.yes')
// 0 → __('api.no')
```

---

## Auth Helpers

### detectGuard()

Determines the auth guard based on the request path.

```php
function detectGuard(): string

// /api/admin/* → 'admin'
// /api/* → 'api'
// Otherwise → config default
```

### getAuthModel()

Gets the user model class for a guard.

```php
function getAuthModel(?string $guard = null): string

// 'admin' → App\Models\Admin::class
// 'api' → App\Models\User::class
```

### isRoot()

Checks if user has the root role.

```php
function isRoot($user = null): bool

// Usage
if (isRoot()) {
    // Current user is root
}
```

### shouldVerifyOtp()

Checks if OTP verification is required for a model.

```php
function shouldVerifyOtp(Model|string $model): bool

// Uses config('project.auth.otp.required_for')
```

---

## Media Helpers

### resolvePhoto()

Gets a photo URL with fallback to default avatar.

```php
function resolvePhoto($image = null, $type = 'user')

// null → asset('media/avatar.png')
// http://... → returns as-is
// path → Storage::url($path) or fallback
```

---

## Setting Helpers

### setting()

Gets a setting value by dot-notation path.

```php
function setting(string $path, ?string $lang = null, $default = null): mixed

// Usage
$appName = setting('general.info.name');
$appName = setting('general.info.name', 'ar');  // Arabic value
```

### brandSettings()

Gets all brand settings as an associative array.

```php
function brandSettings(?string $lang = null): array

// Returns: name, logo, theme, contact, social, etc.
```

### brandName()

Gets the brand name from settings or fallback.

```php
function brandName(bool $display = true): string
```

---

## Utility Helpers

### getModelKey()

Gets the snake_case key for a model class.

```php
function getModelKey(?string $className = null, $trans = false): ?string

// App\Models\Country → 'country'
// App\Models\ActivityLog → 'activity_log'
```

### encryptCode()

Encrypts data for frontend transmission.

```php
function encryptCode(array $data): array

// Returns: ['payload' => '...', 'iv' => '...']
```

### updateDotEnv()

Updates .env file with new values.

```php
function updateDotEnv(array $data = []): void

// Usage
updateDotEnv(['APP_NAME' => 'New Name', 'APP_DEBUG' => true]);
```

---

## Check Helpers

### isBase64()

Checks if a string is valid base64.

```php
function isBase64($data): bool
```

### isArrayIndex()

Checks if an array is indexed (not associative).

```php
function isArrayIndex($value): bool
```

### iSnake()

Checks if a string is snake_case.

```php
function iSnake($value): bool
```

---

## Usage in Controllers

```php
public function index(PageRequest $request): JsonResponse
{
    $query = app(Pipeline::class)
        ->send(User::query())
        ->through([UserFilter::class, OrderByFilter::class])
        ->thenReturn();

    return successResponse(fetchData($query, $request->pageSize, UserResource::class));
}

public function store(UserRequest $request): JsonResponse
{
    $user = User::create($request->validated());
    
    return successResponse(new UserResource($user), __('api.created_success'));
}
```

## See Also

- [Architecture](/guide/architecture) — Controller patterns
- [Custom Rules](/guide/features/custom-rules) — Validation
- [Settings](/guide/features/settings) — Runtime settings
