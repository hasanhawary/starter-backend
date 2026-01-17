# Plan Pricing System - Complete Documentation

## Overview

The plan pricing system allows flexible pricing for different billing cycles (monthly/yearly) with support for discounts and multiple currencies.

## Database Schema

### plan_prices Table

```sql
CREATE TABLE plan_prices (
  id BIGINT PRIMARY KEY,
  plan_id BIGINT FOREIGN KEY,
  cycle VARCHAR(255), -- 'monthly' or 'yearly'
  price DECIMAL(10,2),
  currency VARCHAR(3), -- ISO 4217 code
  discount_percent DECIMAL(5,2) NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE(plan_id, cycle, currency)
);
```

## Models

### PlanPrice Model
**Location:** `app/Models/Central/PlanPrice.php`

**Fillable Fields:**
- `plan_id` - Foreign key to plans
- `cycle` - Billing cycle (monthly/yearly)
- `price` - Price amount
- `currency` - Currency code (ISO 4217)
- `discount_percent` - Optional discount percentage

**Casts:**
- `cycle` - PlanCycleEnum
- `price` - decimal:2
- `discount_percent` - decimal:2

**Relations:**
- `plan()` - BelongsTo Plan
- `creator()` - BelongsTo Admin

**Helper Methods:**
- `getDiscountedPrice()` - Calculate price after discount
- `getFormattedPrice()` - Format price with currency (e.g., "EGP 299.00")
- `getFormattedDiscountedPrice()` - Format discounted price
- `getCycleLabel()` - Get human-readable cycle label

## Enum

### PlanCycleEnum
**Location:** `app/Enum/Billing/PlanCycleEnum.php`

```php
enum PlanCycleEnum: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
```

**Methods:**
- `resolve(string $value)` - Get display name
- `label()` - Get human-readable label
- `daysInCycle()` - Get days in cycle (30 for monthly, 365 for yearly)

## API Endpoints

### List Plan Prices
```
GET /api/central/billing/plan-prices
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "plan_id": 1,
      "cycle": "monthly",
      "cycle_label": "Monthly",
      "price": 299.00,
      "currency": "EGP",
      "formatted_price": "EGP 299.00",
      "discount_percent": null,
      "discounted_price": 299.00,
      "formatted_discounted_price": null,
      "savings": 0
    },
    {
      "id": 2,
      "plan_id": 1,
      "cycle": "yearly",
      "cycle_label": "Yearly",
      "price": 2990.00,
      "currency": "EGP",
      "formatted_price": "EGP 2990.00",
      "discount_percent": 16.67,
      "discounted_price": 2490.07,
      "formatted_discounted_price": "EGP 2490.07",
      "savings": 499.93
    }
  ]
}
```

### Create Plan Price
```
POST /api/central/billing/plan-prices
Content-Type: application/json

{
  "plan_id": 1,
  "cycle": "monthly",
  "price": 299.00,
  "currency": "EGP",
  "discount_percent": null
}
```

### Update Plan Price
```
PUT /api/central/billing/plan-prices/{id}
Content-Type: application/json

{
  "price": 349.00,
  "discount_percent": 10
}
```

### Get Plan Price
```
GET /api/central/billing/plan-prices/{id}
```

### Delete Plan Price
```
DELETE /api/central/billing/plan-prices/{id}
```

## Seeder

### PlanSeeder
**Location:** `database/seeders/Central/PlanSeeder.php`

Creates 4 plans with realistic CRM features:

#### 1. Free Plan
- **Price:** 0 EGP
- **Users:** 1
- **Leads:** 50
- **Contacts:** 100
- **Projects:** 1
- **Campaigns:** 0
- **Storage:** 1 GB
- **Features:** Basic search, limited activity logs, no integrations

#### 2. Basic Plan
- **Monthly:** 299 EGP
- **Yearly:** 2,990 EGP (16.67% discount)
- **Users:** 3
- **Leads:** 500
- **Contacts:** 1,000
- **Projects:** 5
- **Campaigns:** 2
- **Storage:** 5 GB
- **Features:** Advanced search, email integration, basic automation, team collaboration

#### 3. Business Plan
- **Monthly:** 799 EGP
- **Yearly:** 7,990 EGP (16.67% discount)
- **Users:** 15
- **Leads:** 5,000
- **Contacts:** 10,000
- **Projects:** 50
- **Campaigns:** 20
- **Storage:** 50 GB
- **Features:** SMS integration, advanced automation, advanced reporting

#### 4. Enterprise Plan
- **Monthly:** 2,499 EGP
- **Yearly:** 24,990 EGP (16.67% discount)
- **Users:** Unlimited
- **Leads:** Unlimited
- **Contacts:** Unlimited
- **Projects:** Unlimited
- **Campaigns:** Unlimited
- **Storage:** 500 GB
- **Features:** All features included, unlimited everything

## CRM Features Included

### Core Features
- **Users** - Number of team members
- **Leads** - Lead management capacity
- **Contacts** - Contact database size
- **Projects** - Project management
- **Campaigns** - Marketing campaigns
- **Actions** - Task/action tracking
- **Comments** - Collaboration comments
- **Activity Logs** - System activity tracking

### Project Management
- **Project Stages** - Customizable workflow stages
- **Roles** - User role management
- **Team Collaboration** - Team features

### Search & Reporting
- **Search** - Basic or advanced search
- **Advanced Reporting** - Analytics and reports

### Integrations
- **Email Integration** - Email sync and automation
- **SMS Integration** - SMS capabilities
- **API Calls** - API rate limits

### Customization
- **Custom Fields** - Custom data fields
- **Bulk Operations** - Bulk actions support
- **Automation** - Workflow automation

### Storage
- **Storage GB** - Cloud storage allocation

## Usage Examples

### Get Plan with Prices
```php
use App\Models\Central\Plan;

$plan = Plan::with('prices')->find(1);

foreach ($plan->prices as $price) {
    echo $price->cycle->label(); // Monthly, Yearly
    echo $price->getFormattedPrice(); // EGP 299.00
    echo $price->getFormattedDiscountedPrice(); // EGP 2490.07
}
```

### Create Plan with Prices
```php
use App\Models\Central\Plan;
use App\Models\Central\PlanPrice;

$plan = Plan::create([
    'code' => 'CUSTOM',
    'name' => 'Custom Plan',
    'price' => 500,
    'billing_cycle' => 'monthly',
    'currency' => 'EGP',
    'is_active' => true,
]);

PlanPrice::create([
    'plan_id' => $plan->id,
    'cycle' => 'monthly',
    'price' => 500,
    'currency' => 'EGP',
]);

PlanPrice::create([
    'plan_id' => $plan->id,
    'cycle' => 'yearly',
    'price' => 5000,
    'currency' => 'EGP',
    'discount_percent' => 16.67,
]);
```

### Calculate Subscription Cost
```php
use App\Models\Central\PlanPrice;

$price = PlanPrice::where('plan_id', 1)
    ->where('cycle', 'yearly')
    ->first();

$cost = $price->getDiscountedPrice(); // 2490.07
$savings = $price->price - $cost; // 499.93
```

## Seeding

### Run Seeder
```bash
php artisan db:seed --class=Database\\Seeders\\Central\\PlanSeeder
```

### Run All Seeders
```bash
php artisan db:seed
```

## Migration

### Run Migration
```bash
php artisan migrate
```

## Validation Rules

### PlanPriceRequest
```php
'plan_id' => ['required', 'exists:plans,id'],
'cycle' => ['required', new Enum(PlanCycleEnum::class)],
'price' => ['required', 'numeric', 'min:0'],
'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
```

## Authorization

### Permissions
- `view-all-plan-price` / `view-own-plan-price`
- `create-plan-price`
- `update-plan-price`
- `delete-plan-price`
- `restore-plan-price`
- `force-delete-plan-price`

### Policy
**Location:** `app/Policies/Central/Billing/PlanPricePolicy.php`

## Best Practices

1. **Always use PlanPrice for pricing** - Don't store prices directly on Plan
2. **Support multiple currencies** - Create prices for each currency needed
3. **Use discounts for promotions** - Yearly plans typically have discounts
4. **Validate cycle uniqueness** - One price per plan per cycle per currency
5. **Use helper methods** - Use `getDiscountedPrice()` instead of manual calculations
6. **Format for display** - Use `getFormattedPrice()` for user-facing prices

## Testing

```php
// Test creating plan price
$price = PlanPrice::create([
    'plan_id' => 1,
    'cycle' => 'monthly',
    'price' => 299,
    'currency' => 'EGP',
]);

assert($price->cycle === PlanCycleEnum::Monthly);
assert($price->getFormattedPrice() === 'EGP 299.00');

// Test discount calculation
$price->discount_percent = 10;
$discounted = $price->getDiscountedPrice();
assert($discounted === 269.1);
```

## Notes

- Currency is required for all prices
- Discount percent is optional (0-100)
- Cycle must be one of: monthly, yearly
- Unique constraint prevents duplicate prices
- All prices are stored in decimal format for accuracy
