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

## Getting Settings

### Get All Settings

```bash
GET /api/settings
```

Response:

```json
{
  "success": true,
  "data": {
    "app_name": "Multi-Tenant Dashboard Kit",
    "app_email": "support@example.com",
    "maintenance_mode": false,
    "theme": "dark",
    "language": "en"
  }
}
```

### Get Specific Setting

```bash
GET /api/settings/app_name
```

## Updating Settings

### Set Settings

```bash
POST /api/set-settings
Content-Type: application/json

{
  "app_name": "New Name",
  "app_email": "new@example.com",
  "maintenance_mode": false
}
```

## In Code

```php
use App\\Services\\Global\\SettingService;

// Get
$appName = SettingService::get('app_name');

// Set
SettingService::set('app_name', 'New Name');

// Check
if (SettingService::is('maintenance_mode', true)) {
    // App is in maintenance mode
}
```

## Configuration Files

Located in `config/` directory:

- `config/app.php` — Application settings
- `config/mail.php` — Email configuration
- `config/database.php` — Database settings
- `config/cache.php` — Cache drivers

## See Also

- [API Reference - Settings](/guide/api-reference)
- [Configuration Guide](/guide/configuration)
