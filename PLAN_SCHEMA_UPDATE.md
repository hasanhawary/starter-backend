# Plan Schema Update - Migration Guide

## Changes Made

### Removed Columns
- ❌ `max_users` - No longer needed
- ❌ `max_storage_mb` - No longer needed

### Added Columns
- ✅ `currency` - ISO 4217 currency code (USD, EGP, EUR, GBP, etc.)

## Migration Files

### 1. Original Migration (Updated)
**File:** `database/migrations/central/2025_12_20_150000_create_plans_table.php`

Now creates plans table with:
```php
$table->string('currency', 3)->default('USD'); // ISO code: USD, EGP, etc.
```

### 2. Update Migration (New)
**File:** `database/migrations/central/2026_01_16_000002_update_plans_table_remove_limits_add_currency.php`

For existing databases, this migration:
- Drops `max_users` column if exists
- Drops `max_storage_mb` column if exists
- Adds `currency` column with default 'USD'

## Updated Files

### Model
**File:** `app/Models/Central/Plan.php`

**Fillable fields:**
```php
protected $fillable = [
    'code',
    'name',
    'price',
    'billing_cycle',
    'currency',
    'is_active',
];
```

### Request Validation
**File:** `app/Http/Requests/Central/Billing/PlanRequest.php`

**Validation rules:**
```php
'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
```

Validates:
- Required field
- String type
- Exactly 3 characters
- Uppercase letters only (ISO 4217 format)

### Resource
**File:** `app/Http/Resources/Central/Billing/PlanResource.php`

**Response includes:**
```php
'currency' => $this->currency,
'formatted_price' => $this->currency . ' ' . number_format($this->price, 2),
```

Example response:
```json
{
  "id": 1,
  "code": "BASIC",
  "name": "Basic Plan",
  "price": 99.99,
  "currency": "USD",
  "formatted_price": "USD 99.99",
  "billing_cycle": "monthly",
  "is_active": true
}
```

## Supported Currencies

Common ISO 4217 currency codes:
- **USD** - US Dollar
- **EUR** - Euro
- **GBP** - British Pound
- **JPY** - Japanese Yen
- **AUD** - Australian Dollar
- **CAD** - Canadian Dollar
- **CHF** - Swiss Franc
- **CNY** - Chinese Yuan
- **INR** - Indian Rupee
- **EGP** - Egyptian Pound
- **SAR** - Saudi Riyal
- **AED** - UAE Dirham
- **KWD** - Kuwaiti Dinar
- **QAR** - Qatari Riyal

## Migration Steps

### For Fresh Installation
```bash
php artisan migrate
```

The original migration will create the plans table with the currency column.

### For Existing Installation
```bash
php artisan migrate
```

The update migration will:
1. Remove `max_users` and `max_storage_mb` columns
2. Add `currency` column with default value 'USD'

## API Examples

### Create Plan with Currency
```bash
POST /api/central/billing/plans
Content-Type: application/json

{
  "code": "PREMIUM",
  "name": "Premium Plan",
  "price": 199.99,
  "currency": "USD",
  "billing_cycle": "monthly",
  "is_active": true
}
```

### Update Plan Currency
```bash
PUT /api/central/billing/plans/1
Content-Type: application/json

{
  "currency": "EGP",
  "price": 3000.00
}
```

### List Plans with Currency
```bash
GET /api/central/billing/plans
```

Response:
```json
{
  "data": [
    {
      "id": 1,
      "code": "BASIC",
      "name": "Basic Plan",
      "price": 99.99,
      "currency": "USD",
      "formatted_price": "USD 99.99",
      "billing_cycle": "monthly",
      "is_active": true
    },
    {
      "id": 2,
      "code": "PREMIUM",
      "name": "Premium Plan",
      "price": 3000.00,
      "currency": "EGP",
      "formatted_price": "EGP 3000.00",
      "billing_cycle": "monthly",
      "is_active": true
    }
  ]
}
```

## Database Schema

### Before
```sql
CREATE TABLE plans (
  id BIGINT PRIMARY KEY,
  code VARCHAR(255) UNIQUE,
  name VARCHAR(255),
  price DECIMAL(10,2) DEFAULT 0,
  billing_cycle VARCHAR(255),
  max_users INT UNSIGNED NULL,
  max_storage_mb INT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### After
```sql
CREATE TABLE plans (
  id BIGINT PRIMARY KEY,
  code VARCHAR(255) UNIQUE,
  name VARCHAR(255),
  price DECIMAL(10,2) DEFAULT 0,
  currency VARCHAR(3) DEFAULT 'USD',
  billing_cycle VARCHAR(255),
  is_active BOOLEAN DEFAULT true,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

## Rollback

If you need to rollback the changes:

```bash
php artisan migrate:rollback
```

This will:
- Drop the `currency` column
- Restore `max_users` and `max_storage_mb` columns

## Notes

- Currency is required when creating/updating plans
- Default currency is 'USD' if not specified
- Currency code must be exactly 3 uppercase letters (ISO 4217 format)
- The `formatted_price` field in API responses combines currency and price for display
- All existing plans will have 'USD' as default currency after migration
