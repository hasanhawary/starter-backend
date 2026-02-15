---
title: Settings & Configuration
description: Application configuration and dynamic settings management
---

# Settings & Configuration

Manage application-wide settings dynamically through the database with caching support. Settings are organized into groups with multi-language support.

## SettingService

**Location:** `app/Services/Global/SettingService.php`

The `SettingService` provides cached access to database-stored settings with support for nested groups and multi-language values.

### Methods

| Method | Description |
|--------|-------------|
| `all()` | Get all settings as nested array (cached) |
| `get(string $path, $lang, $default)` | Get setting value by group.key path |
| `getLang(string $path, ?string $lang, $default)` | Get multi-language setting value |
| `clearCache()` | Clear the settings cache |

## Usage

### Using the Helper Function

The `setting()` helper provides quick access:

```php
// Get a setting value
$siteName = setting('general.site_name');

// Get with language
$welcomeMsg = setting('content.welcome_message', 'ar');

// Get with default fallback
$timezone = setting('general.timezone', null, 'UTC');
```

### Using the Service Directly

```php
use App\Services\Global\SettingService;

$service = app(SettingService::class);

// Get all settings (nested array structure)
$allSettings = $service->all();
// Returns:
// [
//     'general' => [
//         'site_name' => ['value' => 'My App', 'type' => 'text', ...],
//         'site_email' => ['value' => 'hello@example.com', ...],
//     ],
//     'notifications' => [
//         'email_enabled' => ['value' => true, 'type' => 'boolean', ...],
//     ],
// ]

// Get specific setting
$siteName = $service->get('general.site_name');

// Get multi-language setting in current locale
$welcomeText = $service->getLang('content.welcome_message');

// Get multi-language setting in specific locale
$welcomeTextAr = $service->getLang('content.welcome_message', 'ar');

// Clear cache after updates
$service->clearCache();
```

## Setting Structure

Settings in the database have this structure:

| Column | Type | Description |
|--------|------|-------------|
| `key` | string | Setting key (unique within group) |
| `group` | string | Dot-notation group path (e.g., `general`, `notifications.email`) |
| `value` | json | Setting value (can be any type) |
| `type` | string | Value type: `text`, `textarea`, `boolean`, `number`, `json`, `select` |
| `is_multi_lang` | boolean | Whether value is multi-language |
| `label` | string | Display label for UI |
| `placeholder` | string | Input placeholder |

### Multi-Language Values

For `is_multi_lang = true` settings, value is stored as:

```json
{
  "en": "Welcome to our platform",
  "ar": "مرحبا بكم في منصتنا"
}
```

## API Endpoints

### Get All Settings

```http
GET /api/settings
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "general": {
      "site_name": "My Application",
      "site_email": "support@example.com",
      "timezone": "UTC"
    },
    "notifications": {
      "email_enabled": true,
      "sms_enabled": false
    }
  }
}
```

### Update Settings

```http
POST /api/settings
Authorization: Bearer {token}
Content-Type: application/json

{
  "settings": [
    {
      "key": "site_name",
      "group": "general",
      "value": "New Site Name"
    },
    {
      "key": "email_enabled",
      "group": "notifications",
      "value": true
    }
  ]
}
```

## Caching

Settings are cached forever (until explicitly cleared) using the brand-specific cache key:

```php
// Cache key format
settings_{brand_name}

// Clear cache
$service->clearCache();

// Or using helper
app(SettingService::class)->clearCache();
```

The cache is automatically cleared when settings are updated through the API.

## Creating Settings (Migration)

```php
use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'group' => 'general',
                'value' => 'My Application',
                'type' => 'text',
                'is_multi_lang' => false,
                'label' => 'Site Name',
            ],
            [
                'key' => 'welcome_message',
                'group' => 'content',
                'value' => ['en' => 'Welcome!', 'ar' => 'مرحبا!'],
                'type' => 'textarea',
                'is_multi_lang' => true,
                'label' => 'Welcome Message',
            ],
            [
                'key' => 'email_enabled',
                'group' => 'notifications',
                'value' => true,
                'type' => 'boolean',
                'is_multi_lang' => false,
                'label' => 'Enable Email Notifications',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key'], 'group' => $setting['group']],
                $setting
            );
        }
    }
};
```

## Setting Filters

Filter settings by group in API requests:

```php
// In SettingController
public function index(Request $request)
{
    $query = app(Pipeline::class)
        ->send(Setting::query())
        ->through([
            SettingFilter::class,
            OrderByFilter::class,
        ])
        ->thenReturn();

    return successResponse(wrapPaginate($query, SettingResource::class));
}
```

**Location:** `app/Filters/Setting/SettingFilter.php`

## Configuration Files

For static configuration (not database settings), see:

| File | Purpose |
|------|---------|
| `config/project.php` | Project-specific settings (auth, OTP, pagination) |
| `config/roles.php` | Permission and role definitions |
| `config/brands.php` | Multi-brand configuration |
| `config/report.php` | Report page definitions |

See [Configuration Guide](/guide/configuration) for details.


## See Also

- [Configuration](/guide/configuration) — Static config files
- [Helper Functions](/guide/helpers) — The `setting()` helper
- [API Reference](/guide/api-reference) — Full API documentation
