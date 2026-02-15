---
title: Brand Configuration
description: Multi-brand settings system with template-based configuration
---

# Brand Configuration

This guide documents the multi-brand configuration system that allows managing different brand settings and templates in the multi-tenant environment.

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
DEFAULT_BRAND=jervis
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

## Brand Seeders

Each brand has its own seeder file with brand-specific values.

**Location:** `database/seeders/brands/{brand}.php`

### Example: Jervis Brand

```php
<?php

return [
    'general' => [
        'info' => [
            'name' => ['en' => 'Jervis', 'ar' => 'جيرفيس'],
            'copyright_name' => ['en' => '© 2024 Jervis', 'ar' => '© 2024 جيرفيس'],
            'website_address' => 'https://jervis.com',
            'website_description' => ['en' => 'Jervis Platform', 'ar' => 'منصة جيرفيس'],
            'meta_description' => ['en' => 'Jervis - Enterprise Platform', 'ar' => 'جيرفيس - منصة المؤسسات'],
        ],
        'contact' => [
            'contact_email' => 'support@jervis.com',
            'contact_phone' => '+966 (12) 3456-789',
            'contact_address' => ['en' => 'Riyadh, Saudi Arabia', 'ar' => 'الرياض، المملكة العربية السعودية'],
        ],
        'social' => [
            'instagram' => 'https://instagram.com/jervis',
            'facebook' => 'https://facebook.com/jervis',
            'linkedin' => 'https://linkedin.com/company/jervis',
            'twitter' => 'https://twitter.com/jervis',
            'youtube' => 'https://youtube.com/jervis',
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
    // Get all settings (tenant-scoped)
    $settings = Setting::all();

    // Get settings by group
    $generalSettings = Setting::where('group', 'general')->get();

    return successResponse($settings);
}
```

---

### Using Helper Function

```php
// Get setting value (tenant-scoped)
$siteName = setting('general.info.name');

// Get with default
$logo = setting('properties.website_logo_large', '/images/default-logo.png');

// Get in specific locale
$nameAr = setting('general.info.name', locale: 'ar');
```

---

## Switching Brands

### In Environment

```env
DEFAULT_BRAND=jervis
```

---

### In Seeding

```bash
# Seed with specific brand (for current tenant)
DEFAULT_BRAND=wakeb php artisan db:seed --class=SettingTableSeeder

# Seed all brands (for current tenant)
php artisan db:seed --class=SettingTableSeeder
```

---

## Multi-Tenant Considerations

Each tenant has their own settings database with the same template structure. Settings are automatically scoped to the current tenant.

```php
// Automatically scoped to current tenant
$settings = Setting::all();

// No need to filter by tenant_id - it's automatic
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

---

## See Also

- [Settings Management](/guide/features/settings) — Settings API
- [Enums](/guide/features/enums) — Setting types
- [Multi-Tenancy](/guide/multitenancy/overview) — Multi-tenant architecture
- [API Reference](/guide/api-reference) — Settings endpoints
