---
title: Global Services
description: Notifications, settings, and utility services
---

# Global Services

Global services provide application-wide functionality for notifications, settings, and utilities.

## NotificationService

Multi-channel notification delivery service.

```php
// app/Services/Global/NotificationService.php
namespace App\Services\Global;

class NotificationService
{
    public static function resolve(
        Authenticatable $user, 
        array $data, 
        ?array $types = ['notify', 'realtime']
    ): void;
}
```

### Available Channels

| Channel | Description |
|---------|-------------|
| `notify` | In-app notification (stored in database) |
| `realtime` | WebSocket broadcast via Reverb |
| `email` | Email notification |
| `sms` | SMS notification via configured provider |

### Usage

```php
use App\Services\Global\NotificationService;

// Send via multiple channels
NotificationService::resolve($user, [
    'type' => 'order_created',
    'msg' => 'Your order #123 has been placed',
    'url' => '/orders/123',
    'urlText' => 'View Order',
], ['notify', 'realtime', 'email']);

// In-app notification only
NotificationService::resolve($user, [
    'type' => 'system_update',
    'msg' => 'System maintenance scheduled',
], ['notify']);

// Email only
NotificationService::resolve($user, [
    'type' => 'password_changed',
    'msg' => 'Your password has been changed',
], ['email']);
```

### Data Structure

```php
[
    'type' => 'notification_type',     // Notification type identifier
    'msg' => 'Message content',        // Main message
    'url' => '/path/to/resource',      // Optional URL
    'urlText' => 'Click here',         // Optional URL text
    // ... any additional data
]
```

### Real-time Notifications

When `realtime` channel is used and Reverb is enabled:

```php
// Broadcasts NotificationEvent
if (config('project.realtime.enable')) {
    event(new NotificationEvent($user->id, $data));
}
```

Frontend listens via Echo:

```javascript
Echo.private(`notifications.${userId}`)
    .listen('NotificationEvent', (event) => {
        console.log('New notification:', event.data);
    });
```

### Email Notifications

Uses `BasicMail` mailable:

```php
private static function sendEmail(Authenticatable $user, array $data): void
{
    Mail::to($user->email)->send(new BasicMail($user, $data));
}
```

### SMS Notifications

Dispatches SMS job:

```php
private static function sendSMS(Authenticatable $user, array $data): void
{
    if ($user->phone) {
        dispatch(new SendSmsJob($user->phone, $message));
    }
}
```

## SettingService

Cached settings management with multi-language support.

```php
// app/Services/Global/SettingService.php
namespace App\Services\Global;

class SettingService
{
    public function all(): array;
    public function get(string $path, $lang = null, $default = null);
    public function getLang(string $path, ?string $lang = null, $default = null);
    public function clearCache(): void;
}
```

### Get All Settings

```php
use App\Services\Global\SettingService;

$service = new SettingService();
$settings = $service->all();

// Returns nested array structure
[
    'general' => [
        'app_name' => [
            'value' => 'My App',
            'type' => 'text',
            'is_multi_lang' => false,
        ],
    ],
    'mail' => [
        'host' => [
            'value' => 'smtp.example.com',
            'type' => 'text',
        ],
    ],
]
```

### Get Specific Setting

```php
$service = new SettingService();

// Get by path
$appName = $service->get('general.app_name');  // 'My App'
$mailHost = $service->get('mail.host');        // 'smtp.example.com'

// With default value
$value = $service->get('some.missing.key', null, 'default');
```

### Multi-Language Settings

```php
// Get setting in specific language
$title = $service->getLang('general.site_title', 'ar');

// Get in current locale
$title = $service->getLang('general.site_title');
```

### Caching

Settings are cached indefinitely until cleared:

```php
// Settings are cached per brand
$cacheKey = 'settings_' . config('brands.default_brand');

// Clear cache when settings change
$service->clearCache();
```

### Usage in Controllers

```php
// app/Http/Controllers/API/Central/Global/Setting/SettingController.php
class SettingController extends BaseController
{
    public function index(): JsonResponse
    {
        $service = new SettingService();
        return successResponse($service->all());
    }

    public function setConfigForUser(SetSettingRequest $request): JsonResponse
    {
        // Update settings...
        
        $service = new SettingService();
        $service->clearCache();
        
        return successResponse([], __('api.settings_updated'));
    }
}
```

## EncryptionService

Data encryption utilities for sensitive information.

```php
// app/Services/Global/EncryptionService.php
namespace App\Services\Global;

class EncryptionService
{
    public static function encrypt(string $data): string;
    public static function decrypt(string $data): string;
}
```

### Usage

```php
use App\Services\Global\EncryptionService;

// Encrypt sensitive data
$encrypted = EncryptionService::encrypt($sensitiveData);

// Decrypt data
$decrypted = EncryptionService::decrypt($encrypted);
```

### Frontend Encryption

Used with frontend encryption settings:

```php
// config/project.php
'auth' => [
    'encryption' => [
        'key' => env('FRONT_SHARED_KEY', 'secret'),
        'incoming' => [
            'password' => false,  // Decrypt passwords from frontend
        ],
        'outgoing' => [
            'token' => true,      // Encrypt tokens to frontend
        ],
    ],
],
```

## QueryHelper

Query building utilities.

```php
// app/Services/Global/QueryHelper.php
namespace App\Services\Global;

class QueryHelper
{
    public static function applyFilters(Builder $query, array $filters): Builder;
    public static function applySearch(Builder $query, string $search, array $columns): Builder;
}
```

### Usage

```php
use App\Services\Global\QueryHelper;

$query = User::query();

// Apply multiple filters
$query = QueryHelper::applyFilters($query, [
    'is_active' => true,
    'role' => 'admin',
]);

// Apply search across columns
$query = QueryHelper::applySearch($query, $searchTerm, ['name', 'email']);
```

## Using Services in Controllers

```php
class MyController extends BaseController
{
    public function __construct(
        protected SettingService $settings,
        protected NotificationService $notifications
    ) {
        parent::__construct();
    }

    public function store(Request $request): JsonResponse
    {
        $model = Model::create($request->validated());

        // Notify user
        NotificationService::resolve($request->user(), [
            'type' => 'created',
            'msg' => 'Item created successfully',
        ]);

        return successResponse($model);
    }
}
```

## See Also

- [Services Overview](/guide/services/)
- [Notifications](/guide/features/notifications)
- [Settings Management](/guide/features/settings)
