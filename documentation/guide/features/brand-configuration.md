---
title: Brand Configuration
description: Multi-brand settings system with template-based configuration
---

# Brand Configuration

This guide documents the multi-brand configuration system that allows managing different brand settings and templates.

## Overview

The brand configuration system provides a template-based approach to managing settings for different brands. Each brand can have its own isolated settings while sharing the same database structure.

**Location:** `config/brands.php`

## Configuration Structure

### Default Brand

```php
'default_brand' => env('DEFAULT_BRAND', 'default'),
```

Sets the default brand when no specific brand is selected.

**Usage:**
```php
// Get default brand
$brand = config('brands.default_brand'); // 'default'

// Override in .env
DEFAULT_BRAND=wakeb
```

---

## Template System

The template defines all available settings for all brands. It's organized into hierarchical groups:

### 1. General Settings

#### Info Group

Contains basic company information.

```php
'general' => [
    'info' => [
        ['key' => 'name', 'type' => 'text', 'label' => [...], 'is_multi_lang' => true],
        ['key' => 'copyright_name', 'type' => 'text', 'label' => [...], 'is_multi_lang' => true],
        ['key' => 'website_address', 'type' => 'text', 'label' => [...], 'is_multi_lang' => false],
        ['key' => 'website_description', 'type' => 'textarea', 'label' => [...], 'is_multi_lang' => true],
        ['key' => 'meta_description', 'type' => 'textarea', 'label' => [...], 'is_multi_lang' => true],
    ],
]
```

**Fields:**
- `name` - Company name (translatable)
- `copyright_name` - Copyright text (translatable)
- `website_address` - Website URL
- `website_description` - Website description (translatable)
- `meta_description` - SEO meta description (translatable)

**Usage:**
```php
// Get company name
$name = setting('general.info.name');

// Get in specific language
$nameAr = setting('general.info.name', locale: 'ar');
```

---

#### Contact Group

Contains contact information.

```php
'contact' => [
    ['key' => 'contact_email', 'type' => 'text', ...],
    ['key' => 'contact_phone', 'type' => 'text', ...],
    ['key' => 'contact_address', 'type' => 'text', ...],
]
```

**Fields:**
- `contact_email` - Support email address
- `contact_phone` - Support phone number
- `contact_address` - Physical address (translatable)

**Usage:**
```php
$email = setting('general.contact.contact_email');
$phone = setting('general.contact.contact_phone');
```

---

#### Social Group

Contains social media links.

```php
'social' => [
    ['key' => 'instagram', 'type' => 'text', ...],
    ['key' => 'facebook', 'type' => 'text', ...],
    ['key' => 'linkedin', 'type' => 'text', ...],
    ['key' => 'twitter', 'type' => 'text', ...],
    ['key' => 'youtube', 'type' => 'text', ...],
]
```

**Fields:**
- `instagram` - Instagram profile URL
- `facebook` - Facebook page URL
- `linkedin` - LinkedIn company URL
- `twitter` - Twitter profile URL
- `youtube` - YouTube channel URL

**Usage:**
```php
$instagram = setting('general.social.instagram');
```

---

### 2. Properties

Contains visual assets and branding elements.

```php
'properties' => [
    ['key' => 'website_logo_large', 'type' => 'imageUploader', ...],
    ['key' => 'website_dark_logo_large', 'type' => 'imageUploader', ...],
    ['key' => 'website_logo_small', 'type' => 'imageUploader', ...],
    ['key' => 'website_dark_logo_small', 'type' => 'imageUploader', ...],
    ['key' => 'website_favorite_place_icon', 'type' => 'imageUploader', ...],
]
```

**Fields:**
- `website_logo_large` - Large logo for header
- `website_dark_logo_large` - Dark theme large logo
- `website_logo_small` - Small logo for favicon/mobile
- `website_dark_logo_small` - Dark theme small logo
- `website_favorite_place_icon` - Favicon

**Usage:**
```php
$logo = setting('properties.website_logo_large');
$favicon = setting('properties.website_favorite_place_icon');
```

---

### 3. Notifications

Controls notification channels.

```php
'notifications' => [
    ['key' => 'mail_support', 'type' => 'switchbox', ...],
    ['key' => 'sms_support', 'type' => 'switchbox', ...],
    ['key' => 'push_support', 'type' => 'switchbox', ...],
    ['key' => 'real_time_support', 'type' => 'switchbox', ...],
]
```

**Fields:**
- `mail_support` - Enable email notifications
- `sms_support` - Enable SMS notifications
- `push_support` - Enable push notifications
- `real_time_support` - Enable real-time notifications

**Usage:**
```php
if (setting('notifications.mail_support')) {
    // Send email notification
}
```

---

### 4. Theme

Contains theme and styling settings.

#### Colors Group

```php
'theme' => [
    'colors' => [
        ['key' => 'primary_color', 'type' => 'text', ...],
        ['key' => 'secondary_color', 'type' => 'text', ...],
        ['key' => 'text_color', 'type' => 'text', ...],
        ['key' => 'muted_color', 'type' => 'text', ...],
    ],
]
```

**Fields:**
- `primary_color` - Primary brand color (hex)
- `secondary_color` - Secondary brand color (hex)
- `text_color` - Default text color (hex)
- `muted_color` - Muted/secondary text color (hex)

**Usage:**
```php
$primaryColor = setting('theme.colors.primary_color');
```

---

#### Font Group

```php
'font' => [
    ['key' => 'font_family', 'type' => 'text', ...],
    ['key' => 'font_size', 'type' => 'text', ...],
]
```

---

### 5. Config

Contains system configuration.

#### Mail Group

```php
'config' => [
    'mail' => [
        ['key' => 'mail_driver', 'type' => 'select', ...],
        ['key' => 'mail_host', 'type' => 'text', ...],
        ['key' => 'mail_port', 'type' => 'text', ...],
        ['key' => 'mail_username', 'type' => 'text', ...],
        ['key' => 'mail_password', 'type' => 'text', ...],
        ['key' => 'mail_encryption', 'type' => 'select', ...],
        ['key' => 'mail_from_address', 'type' => 'text', ...],
    ],
]
```

---

#### SMS Group

```php
'sms' => [
    ['key' => 'sms_driver', 'type' => 'select', ...],
    ['key' => 'sms_api_key', 'type' => 'text', ...],
    ['key' => 'sms_sender_id', 'type' => 'text', ...],
]
```

---

#### LDAP Group

```php
'ldap' => [
    ['key' => 'ldap_enabled', 'type' => 'switchbox', ...],
    ['key' => 'ldap_host', 'type' => 'text', ...],
    ['key' => 'ldap_port', 'type' => 'text', ...],
    ['key' => 'ldap_base_dn', 'type' => 'text', ...],
]
```

---

#### Reverb Group

```php
'reverb' => [
    ['key' => 'reverb_app_id', 'type' => 'text', ...],
    ['key' => 'reverb_app_key', 'type' => 'text', ...],
    ['key' => 'reverb_app_secret', 'type' => 'text', ...],
    ['key' => 'reverb_host', 'type' => 'text', ...],
    ['key' => 'reverb_port', 'type' => 'text', ...],
]
```

---

## Brand Seeders

Each brand has its own seeder file with brand-specific values.

**Location:** `database/seeders/brands/{brand}.php`

### Example: Wakeb Brand

```php
<?php

return [
    'general' => [
        'info' => [
            'name' => ['en' => 'Wakeb', 'ar' => 'واكب'],
            'copyright_name' => ['en' => '© 2024 Wakeb', 'ar' => '© 2024 واكب'],
            'website_address' => 'https://wakeb.tech',
            'website_description' => ['en' => 'Wakeb Platform', 'ar' => 'منصة واكب'],
            'meta_description' => ['en' => 'Wakeb - Enterprise Platform', 'ar' => 'واكب - منصة المؤسسات'],
        ],
        'contact' => [
            'contact_email' => 'support@wakeb.com',
            'contact_phone' => '+966 (12) 3456-789',
            'contact_address' => ['en' => 'Riyadh, Saudi Arabia', 'ar' => 'الرياض، المملكة العربية السعودية'],
        ],
        'social' => [
            'instagram' => 'https://instagram.com/wakeb',
            'facebook' => 'https://facebook.com/wakeb',
            'linkedin' => 'https://linkedin.com/company/wakeb',
            'twitter' => 'https://twitter.com/wakeb',
            'youtube' => 'https://youtube.com/wakeb',
        ],
    ],
    'theme' => [
        'colors' => [
            'primary_color' => '#0066CC',
            'secondary_color' => '#FFFFFF',
            'text_color' => '#333333',
            'muted_color' => '#999999',
        ],
    ],
];
```

---

## Using Brand Settings

### In Controllers

```php
use App\Models\Setting;

public function index()
{
    // Get all settings
    $settings = Setting::all();

    // Get settings by group
    $generalSettings = Setting::where('group', 'general')->get();

    // Get specific setting
    $siteName = Setting::where('key', 'name')
        ->where('group', 'general.info')
        ->first();

    return successResponse($settings);
}
```

---

### Using Helper Function

```php
// Get setting value
$siteName = setting('general.info.name');

// Get with default
$logo = setting('properties.website_logo_large', '/images/default-logo.png');

// Get in specific locale
$nameAr = setting('general.info.name', locale: 'ar');
```

---

### In API Responses

```php
public function getSettings()
{
    $settings = Setting::all();

    return successResponse(
        SettingGroupResource::collection($settings->groupBy('group'))
    );
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "general.info": [
      {
        "key": "name",
        "type": "text",
        "label": "Company name",
        "value": "Wakeb",
        "is_multi_lang": true
      }
    ],
    "theme.colors": [
      {
        "key": "primary_color",
        "type": "text",
        "label": "Primary color",
        "value": "#0066CC",
        "is_multi_lang": false
      }
    ]
  }
}
```

---

## Switching Brands

### In Environment

```env
DEFAULT_BRAND=wakeb
```

---

### Programmatically

```php
// Get current brand
$brand = brandName();

// Get brand setting
$setting = Setting::where('key', 'name')
    ->where('group', 'general.info')
    ->first();
```

---

### In Seeding

```bash
# Seed with specific brand
DEFAULT_BRAND=wakeb php artisan db:seed --class=SettingTableSeeder

# Seed all brands
php artisan db:seed --class=SettingTableSeeder
```

---

## Adding New Settings

### 1. Update Template

Add to `config/brands.php`:

```php
'general' => [
    'info' => [
        // ... existing settings
        ['key' => 'new_setting', 'type' => 'text', 'label' => [...], 'is_multi_lang' => false],
    ],
]
```

---

### 2. Update Brand Seeders

Add to `database/seeders/brands/{brand}.php`:

```php
'general' => [
    'info' => [
        // ... existing values
        'new_setting' => 'value',
    ],
]
```

---

### 3. Run Migration

```bash
php artisan migrate:fresh --seed
```

---

## Best Practices

1. **Use dot notation** - Access settings with `setting('group.subgroup.key')`
2. **Provide defaults** - Always provide default values when getting settings
3. **Cache settings** - Settings are automatically cached for performance
4. **Validate types** - Ensure setting values match their defined types
5. **Document settings** - Add clear labels and descriptions
6. **Use translations** - Make settings translatable when needed
7. **Organize logically** - Group related settings together
8. **Test brand switching** - Verify settings work across brands

---

## See Also

- [Settings Management](/guide/features/settings) — Settings API
- [Enums](/guide/features/enums) — Setting types
- [Multi-Language Support](/guide/configuration) — i18n configuration
- [API Reference](/guide/api-reference) — Settings endpoints
