# Plan API - Unified Documentation

## Overview

Plans are now managed as a single entity with integrated pricing. When you create or update a plan, you can include prices directly in the request. Prices are automatically synced with the plan.

## API Endpoints

### List Plans
```
GET /api/central/billing/plans
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "code": "BASIC",
      "name": "Basic Plan",
      "price": 299,
      "currency": "EGP",
      "formatted_price": "EGP 299.00",
      "billing_cycle": "monthly",
      "is_active": true,
      "features": [
        {
          "id": 1,
          "key": "users",
          "value": "3"
        }
      ],
      "prices": [
        {
          "id": 1,
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
      ],
      "created_at": "2026-01-16T10:00:00Z",
      "updated_at": "2026-01-16T10:00:00Z"
    }
  ]
}
```

### Create Plan with Prices

```
POST /api/central/billing/plans
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "code": "PREMIUM",
  "name": "Premium Plan",
  "price": 799,
  "billing_cycle": "monthly",
  "currency": "EGP",
  "is_active": true,
  "prices": [
    {
      "cycle": "monthly",
      "price": 799.00,
      "currency": "EGP",
      "discount_percent": null
    },
    {
      "cycle": "yearly",
      "price": 7990.00,
      "currency": "EGP",
      "discount_percent": 16.67
    }
  ]
}
```

**Response:**
```json
{
  "data": {
    "id": 2,
    "code": "PREMIUM",
    "name": "Premium Plan",
    "price": 799,
    "currency": "EGP",
    "formatted_price": "EGP 799.00",
    "billing_cycle": "monthly",
    "is_active": true,
    "features": [],
    "prices": [
      {
        "id": 3,
        "cycle": "monthly",
        "cycle_label": "Monthly",
        "price": 799.00,
        "currency": "EGP",
        "formatted_price": "EGP 799.00",
        "discount_percent": null,
        "discounted_price": 799.00,
        "formatted_discounted_price": null,
        "savings": 0
      },
      {
        "id": 4,
        "cycle": "yearly",
        "cycle_label": "Yearly",
        "price": 7990.00,
        "currency": "EGP",
        "formatted_price": "EGP 7990.00",
        "discount_percent": 16.67,
        "discounted_price": 6657.07,
        "formatted_discounted_price": "EGP 6657.07",
        "savings": 1332.93
      }
    ],
    "created_at": "2026-01-16T10:00:00Z",
    "updated_at": "2026-01-16T10:00:00Z"
  },
  "message": "Created successfully"
}
```

### Get Plan

```
GET /api/central/billing/plans/{id}
Authorization: Bearer YOUR_TOKEN
```

**Response:** Same as create response

### Update Plan with Prices

```
PUT /api/central/billing/plans/{id}
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "name": "Premium Plan Updated",
  "price": 899,
  "currency": "EGP",
  "is_active": true,
  "prices": [
    {
      "cycle": "monthly",
      "price": 899.00,
      "currency": "EGP",
      "discount_percent": null
    },
    {
      "cycle": "yearly",
      "price": 8990.00,
      "currency": "EGP",
      "discount_percent": 16.67
    }
  ]
}
```

**Response:** Updated plan with new prices

### Delete Plan

```
DELETE /api/central/billing/plans/{id}
Authorization: Bearer YOUR_TOKEN
```

### Toggle Plan Active Status

```
PUT /api/central/billing/plans/toggle-active
Authorization: Bearer YOUR_TOKEN

{
  "plan_id": 1
}
```

## Request Validation

### Plan Fields
```php
'code' => ['required', 'string', 'unique:plans,code'],
'name' => ['required', 'string', 'max:255'],
'price' => ['required', 'numeric', 'min:0'],
'billing_cycle' => ['required', 'in:monthly,yearly'],
'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
'is_active' => ['sometimes', 'boolean'],
```

### Prices Array
```php
'prices' => ['sometimes', 'array'],
'prices.*.cycle' => ['required_with:prices', 'in:monthly,yearly'],
'prices.*.price' => ['required_with:prices', 'numeric', 'min:0'],
'prices.*.currency' => ['required_with:prices', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
'prices.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
```

## Usage Examples

### Create Plan with Monthly and Yearly Pricing

```bash
curl -X POST http://localhost:8000/api/central/billing/plans \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "code": "BUSINESS",
    "name": "Business Plan",
    "price": 799,
    "billing_cycle": "monthly",
    "currency": "EGP",
    "is_active": true,
    "prices": [
      {
        "cycle": "monthly",
        "price": 799.00,
        "currency": "EGP"
      },
      {
        "cycle": "yearly",
        "price": 7990.00,
        "currency": "EGP",
        "discount_percent": 16.67
      }
    ]
  }'
```

### Update Plan Prices

```bash
curl -X PUT http://localhost:8000/api/central/billing/plans/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "price": 899,
    "prices": [
      {
        "cycle": "monthly",
        "price": 899.00,
        "currency": "EGP"
      },
      {
        "cycle": "yearly",
        "price": 8990.00,
        "currency": "EGP",
        "discount_percent": 16.67
      }
    ]
  }'
```

### Create Plan with Default Monthly Price

If you don't provide prices array, a default monthly price is created:

```bash
curl -X POST http://localhost:8000/api/central/billing/plans \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "code": "STARTER",
    "name": "Starter Plan",
    "price": 199,
    "billing_cycle": "monthly",
    "currency": "EGP",
    "is_active": true
  }'
```

This automatically creates a monthly price of 199 EGP.

## PHP Usage

### Create Plan with Prices

```php
use App\Models\Central\Plan;
use App\Services\Billing\PlanService;

$planService = app(PlanService::class);

$plan = Plan::create([
    'code' => 'BUSINESS',
    'name' => 'Business Plan',
    'price' => 799,
    'billing_cycle' => 'monthly',
    'currency' => 'EGP',
    'is_active' => true,
]);

$prices = [
    [
        'cycle' => 'monthly',
        'price' => 799,
        'currency' => 'EGP',
    ],
    [
        'cycle' => 'yearly',
        'price' => 7990,
        'currency' => 'EGP',
        'discount_percent' => 16.67,
    ],
];

$planService->syncPrices($plan, $prices);
```

### Get Plan with Prices

```php
$plan = Plan::with('prices', 'features')->find(1);

foreach ($plan->prices as $price) {
    echo $price->cycle->label(); // Monthly, Yearly
    echo $price->getFormattedPrice(); // EGP 799.00
    echo $price->getFormattedDiscountedPrice(); // EGP 6657.07
}
```

### Update Plan Prices

```php
$plan = Plan::find(1);

$newPrices = [
    [
        'cycle' => 'monthly',
        'price' => 899,
        'currency' => 'EGP',
    ],
    [
        'cycle' => 'yearly',
        'price' => 8990,
        'currency' => 'EGP',
        'discount_percent' => 16.67,
    ],
];

$planService->syncPrices($plan, $newPrices);
```

## Database Operations

### Prices are Automatically Managed

When you create or update a plan with prices:
1. Existing prices for that plan are deleted
2. New prices are created from the provided array
3. All operations are wrapped in a database transaction

### Direct Price Access

```php
// Get all prices for a plan
$prices = $plan->prices()->get();

// Get specific cycle price
$monthlyPrice = $plan->prices()
    ->where('cycle', 'monthly')
    ->first();

// Get price for specific currency
$egpPrice = $plan->prices()
    ->where('currency', 'EGP')
    ->first();
```

## Error Handling

### Invalid Cycle
```json
{
  "message": "The prices.0.cycle field must be one of: monthly, yearly.",
  "errors": {
    "prices.0.cycle": ["The prices.0.cycle field must be one of: monthly, yearly."]
  }
}
```

### Invalid Currency
```json
{
  "message": "The prices.0.currency field must match the format /^[A-Z]{3}$/.",
  "errors": {
    "prices.0.currency": ["The prices.0.currency field must match the format /^[A-Z]{3}$/."]
  }
}
```

### Invalid Discount
```json
{
  "message": "The prices.0.discount_percent field must be between 0 and 100.",
  "errors": {
    "prices.0.discount_percent": ["The prices.0.discount_percent field must be between 0 and 100."]
  }
}
```

## Best Practices

1. **Always provide prices** - Include prices array when creating/updating plans
2. **Use consistent currency** - Keep currency consistent across all prices
3. **Set yearly discounts** - Offer discounts for yearly subscriptions
4. **Validate before sending** - Check data before API calls
5. **Use transactions** - All operations are transactional
6. **Load relations** - Always load prices and features when needed

## Migration from Separate Endpoints

If you were using separate plan-prices endpoints:

**Before:**
```bash
POST /api/central/billing/plans
POST /api/central/billing/plan-prices
```

**Now:**
```bash
POST /api/central/billing/plans (with prices array)
```

## Summary

- ✅ Single endpoint for plan management
- ✅ Prices synced automatically
- ✅ Transactional operations
- ✅ Flexible pricing structure
- ✅ Support for discounts
- ✅ Multiple currencies
- ✅ Automatic default pricing
