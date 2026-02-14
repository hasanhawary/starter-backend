---
title: Settings & Configuration
description: Application configuration and user preferences management
---

# Settings & Configuration

Manage application-wide settings, feature flags, and user preferences through a unified settings system.

## Settings Types

- **Application Settings** — App name, email, timezone, maintenance mode
- **Feature Flags** — Enable/disable features per environment
- **Integration Settings** — API keys, credentials for external services
- **User Preferences** — Per-user settings (language, theme, notifications)

## API Endpoints

### Get All Settings

```bash
GET /api/admin/settings
```

Response:

```json
{
  "success": true,
  "data": {
    "general": {
      "app_name": {
        "value": "Starter Backend",
        "type": "text",
        "is_multi_lang": true,
        "placeholder": "Application Name",
        "label": "App Name"
      },
      "app_email": {
        "value": "support@example.com",
        "type": "email",
        "is_multi_lang": false
      }
    },
    "mail": {
      "smtp_host": { "value": "smtp.example.com", "type": "text" },
      "smtp_port": { "value": "587", "type": "number" }
    }
  }
}
```

### Update Settings

```bash
PUT /api/admin/settings/{id}
Content-Type: application/json

{
  "value": { "ar": "اسم التطبيق", "en": "App Name" }
}
```

## Using in Code

### Global Helper (Recommended)

The easiest way to access settings is via the global `setting()` helper:

```php
// Get a setting value by group.key path
$appName = setting('general.app_name');

// Get with language preference
$appName = setting('general.app_name', 'ar');

// Get with default fallback
$timezone = setting('general.timezone', null, 'UTC');
```

### Service Instance

For more control, use the `SettingService` instance:

```php
use App\Services\Global\SettingService;

$settingService = app(SettingService::class);

// Get all settings as nested array
$all = $settingService->all();

// Get specific setting by path
$appName = $settingService->get('general.app_name');

// Get multi-lang setting with language
$appName = $settingService->getLang('general.app_name', 'ar');

// Clear settings cache
$settingService->clearCache();
```

### SettingService Methods

| Method | Description |
|--------|-------------|
| `all()` | Get all settings as nested associative array (cached) |
| `get($path, $lang, $default)` | Get setting value by `group.key` path |
| `getLang($path, $lang, $default)` | Get multi-lang setting with language fallback |
| `clearCache()` | Clear the settings cache |

## Setting Model Structure

Settings are stored in the `settings` table with this structure:

| Column | Type | Description |
|--------|------|-------------|
| `key` | string | Setting key identifier |
| `group` | string | Dot-notation group path (e.g., `mail.smtp`) |
| `value` | json | The setting value (can be multi-lang array) |
| `type` | enum | Field type: `text`, `textarea`, `email`, `number`, `file` |
| `is_multi_lang` | boolean | Whether value supports translations |
| `placeholder` | string | Placeholder text (translatable) |
| `label` | string | Display label (translatable) |

## Caching

Settings are cached using Laravel's cache system:
- Cache key pattern: `settings_{brand}`
- Cache is stored forever until explicitly cleared
- Clear cache after updates: `$settingService->clearCache()`

## Configuration Files

Static configuration in `config/` directory:

- `config/project.php` — Project-specific settings (OTP, uploads, pagination)
- `config/lang.php` — Multi-language configuration
- `config/roles.php` — Role definitions
- `config/report.php` — Report page definitions

## See Also

- [Global Helpers](/guide/features/helpers) — The `setting()` helper function
- [Configuration Guide](/guide/configuration) — Static config file documentation
- [API Reference](/guide/api-reference) — Full endpoint documentation
