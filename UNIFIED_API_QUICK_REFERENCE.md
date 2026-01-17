# Unified Plan API - Quick Reference

## Single Endpoint for Plans

All plan operations now include prices directly.

## Create Plan with Prices

```bash
POST /api/central/billing/plans

{
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
}
```

## Update Plan with New Prices

```bash
PUT /api/central/billing/plans/{id}

{
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
}
```

## Get Plan with Prices

```bash
GET /api/central/billing/plans/{id}
```

Returns plan with all prices and features.

## List All Plans

```bash
GET /api/central/billing/plans
```

Returns all plans with prices and features.

## Delete Plan

```bash
DELETE /api/central/billing/plans/{id}
```

Deletes plan and all associated prices.

## Toggle Plan Active

```bash
PUT /api/central/billing/plans/toggle-active

{
  "plan_id": 1
}
```

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

$planService->syncPrices($plan, [
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
]);
```

### Get Plan with Prices

```php
$plan = Plan::with('prices', 'features')->find(1);

foreach ($plan->prices as $price) {
    echo $price->cycle->label(); // Monthly, Yearly
    echo $price->getFormattedPrice(); // EGP 799.00
    echo $price->getDiscountedPrice(); // 6657.07
}
```

### Update Plan Prices

```php
$plan = Plan::find(1);

$planService->syncPrices($plan, [
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
]);
```

## Validation Rules

```php
'code' => 'required|string|unique:plans,code',
'name' => 'required|string|max:255',
'price' => 'required|numeric|min:0',
'billing_cycle' => 'required|in:monthly,yearly',
'currency' => 'required|string|size:3|regex:/^[A-Z]{3}$/',
'is_active' => 'sometimes|boolean',

// Prices array
'prices' => 'sometimes|array',
'prices.*.cycle' => 'required_with:prices|in:monthly,yearly',
'prices.*.price' => 'required_with:prices|numeric|min:0',
'prices.*.currency' => 'required_with:prices|string|size:3|regex:/^[A-Z]{3}$/',
'prices.*.discount_percent' => 'nullable|numeric|min:0|max:100',
```

## Response Structure

```json
{
  "id": 1,
  "code": "BUSINESS",
  "name": "Business Plan",
  "price": 799,
  "currency": "EGP",
  "formatted_price": "EGP 799.00",
  "billing_cycle": "monthly",
  "is_active": true,
  "features": [...],
  "prices": [
    {
      "id": 1,
      "cycle": "monthly",
      "cycle_label": "Monthly",
      "price": 799.00,
      "currency": "EGP",
      "formatted_price": "EGP 799.00",
      "discount_percent": null,
      "discounted_price": 799.00,
      "savings": 0
    },
    {
      "id": 2,
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
  ]
}
```

## Key Features

✅ **Single Endpoint** - Create/update plan with prices
✅ **Atomic Operations** - All changes in one transaction
✅ **Auto Sync** - Prices automatically synced
✅ **Default Pricing** - Auto-creates monthly price if not provided
✅ **Flexible** - Optional prices array
✅ **Discounts** - Support for yearly discounts
✅ **Multiple Currencies** - Any ISO 4217 currency code

## Removed Endpoints

❌ `GET /api/central/billing/plan-prices`
❌ `POST /api/central/billing/plan-prices`
❌ `GET /api/central/billing/plan-prices/{id}`
❌ `PUT /api/central/billing/plan-prices/{id}`
❌ `DELETE /api/central/billing/plan-prices/{id}`

## Migration

**Old:** Separate plan and price endpoints
**New:** Single unified plan endpoint with prices array

## Files Changed

- ✅ `app/Http/Controllers/API/Central/Billing/PlanController.php`
- ✅ `app/Http/Requests/Central/Billing/PlanRequest.php`
- ✅ `app/Http/Resources/Central/Billing/PlanResource.php`
- ✅ `app/Services/Billing/PlanService.php`
- ✅ `routes/central.php`

## Files Deleted

- ❌ `app/Http/Controllers/API/Central/Billing/PlanPriceController.php`
- ❌ `app/Http/Requests/Central/Billing/PlanPriceRequest.php`
- ❌ `app/Policies/Central/Billing/PlanPricePolicy.php`

## Status

✅ **Ready for Production**
- All diagnostics pass
- Fully tested
- Complete documentation
- Backward compatible with seeder

---

For detailed documentation, see `PLAN_API_UNIFIED_DOCUMENTATION.md`
