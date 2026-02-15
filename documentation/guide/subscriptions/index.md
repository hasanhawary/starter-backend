---
title: Subscription System
description: Complete subscription and billing management for multi-tenant applications
---

# Subscription System

This guide documents the complete subscription and billing system for managing tenant subscriptions, plans, and usage tracking.

## Overview

The subscription system provides:
- Plan management with features and pricing
- Subscription lifecycle management (active, expired, cancelled)
- Usage tracking and limits
- Billing cycle management (monthly, yearly, quarterly, weekly)
- Subscription pricing with discounts

**Key Components:**
- Plans: Define features and pricing tiers
- Subscriptions: Tenant subscriptions to plans
- Usage Tracking: Track feature usage against limits
- Billing: Manage pricing and billing cycles

---

## Core Models

### Plan

**Location:** `app/Models/Central/Plan.php`

Represents a subscription plan with features and pricing.

**Attributes:**
- `id` - Plan ID
- `code` - Unique plan code
- `name` - Translatable plan name
- `is_active` - Active status
- `created_by` - Creator user ID

**Relationships:**
- `features()` - Plan features
- `prices()` - Plan pricing options
- `subscriptions()` - Active subscriptions

**Usage:**

```php
use App\Models\Central\Plan;

// Get plan
$plan = Plan::where('code', 'professional')->first();

// Get plan features
$features = $plan->features;

// Get plan prices
$prices = $plan->prices;

// Get active subscriptions
$subscriptions = $plan->subscriptions()->where('status', 'active')->get();
```

---

### PlanFeature

**Location:** `app/Models/Central/PlanFeature.php`

Represents a feature included in a plan.

**Attributes:**
- `id` - Feature ID
- `plan_id` - Plan ID
- `name` - Translatable feature name
- `key` - Feature key (for tracking usage)
- `value` - Feature limit/value
- `is_active` - Active status

**Usage:**

```php
use App\Models\Central\PlanFeature;

// Get feature
$feature = PlanFeature::where('key', 'api_calls')->first();

// Get feature value
$limit = $feature->value;  // e.g., 10000

// Check if feature is active
if ($feature->is_active) {
    // Feature is available
}
```

---

### PlanPrice

**Location:** `app/Models/Central/PlanPrice.php`

Represents pricing for a plan with billing cycle.

**Attributes:**
- `id` - Price ID
- `plan_id` - Plan ID
- `cycle` - Billing cycle (monthly, yearly, quarterly, weekly)
- `price` - Price amount
- `currency` - Currency code
- `discount_percent` - Discount percentage

**Methods:**
- `getDiscountedPrice()` - Get price after discount
- `getFormattedPrice()` - Get formatted price string
- `getFormattedDiscountedPrice()` - Get formatted discounted price
- `getCycleLabel()` - Get cycle label

**Usage:**

```php
use App\Models\Central\PlanPrice;

// Get price
$price = PlanPrice::find(1);

// Get pricing info
$basePrice = $price->price;              // 99.99
$discounted = $price->getDiscountedPrice();  // 79.99
$formatted = $price->getFormattedPrice();    // "USD 99.99"
$cycle = $price->getCycleLabel();        // "Monthly"
```

---

### Subscription

**Location:** `app/Models/Central/Subscription.php`

Represents a tenant's subscription to a plan.

**Attributes:**
- `id` - Subscription ID
- `tenant_id` - Tenant ID
- `plan_id` - Plan ID
- `plan_price_id` - Plan price ID
- `starts_at` - Subscription start date
- `ends_at` - Subscription end date
- `status` - Status (active, expired, cancelled)

**Methods:**
- `isActive()` - Check if subscription is active
- `isExpired()` - Check if subscription is expired
- `isCancelled()` - Check if subscription is cancelled
- `daysRemaining()` - Get remaining days
- `hasFeature($key)` - Check if plan has feature
- `getFeatureValue($key)` - Get feature limit
- `getBillingCycle()` - Get billing cycle
- `getPrice()` - Get base price
- `getDiscountedPrice()` - Get discounted price

**Usage:**

```php
use App\Models\Central\Subscription;

// Get subscription
$subscription = Subscription::where('tenant_id', $tenantId)->first();

// Check status
if ($subscription->isActive()) {
    // Subscription is active
}

// Get feature limit
$apiCallLimit = $subscription->getFeatureValue('api_calls');

// Get remaining days
$daysLeft = $subscription->daysRemaining();

// Get pricing
$price = $subscription->getPrice();
$discounted = $subscription->getDiscountedPrice();
```

---

### SubscriptionUsage

**Location:** `app/Models/Central/SubscriptionUsage.php`

Tracks feature usage for a subscription.

**Attributes:**
- `id` - Usage ID
- `tenant_id` - Tenant ID
- `key` - Feature key
- `used_value` - Amount used
- `period_start` - Period start date
- `period_end` - Period end date

**Usage:**

```php
use App\Models\Central\SubscriptionUsage;

// Get usage
$usage = SubscriptionUsage::where('tenant_id', $tenantId)
    ->where('key', 'api_calls')
    ->currentPeriod()
    ->first();

// Get used amount
$used = $usage->used_value;  // e.g., 5000
```

---

## Subscription Service

**Location:** `app/Tools/Subscription/Services/SubscriptionService.php`

Manages subscription operations.

### Methods

#### createSubscription()

Creates a new subscription for a tenant.

```php
use App\Tools\Subscription\Facades\Subscription;

$subscription = Subscription::createSubscription([
    'tenant_id' => $tenantId,
    'plan_id' => $planId,
    'plan_price_id' => $planPriceId,
    'starts_at' => now(),
    'ends_at' => now()->addMonth(),
]);
```

#### updateSubscription()

Updates an existing subscription.

```php
$subscription = Subscription::updateSubscription($subscription, [
    'plan_id' => $newPlanId,
    'plan_price_id' => $newPlanPriceId,
]);
```

#### changeStatus()

Changes subscription status.

```php
use App\Enum\Subscription\SubscriptionStatusEnum;

$subscription = Subscription::changeStatus(
    $subscription,
    SubscriptionStatusEnum::Active
);
```

#### cancel()

Cancels a subscription.

```php
$subscription = Subscription::cancel($subscription);
```

#### renew()

Renews a subscription.

```php
$subscription = Subscription::renew(
    $subscription,
    startsAt: now(),
    endsAt: now()->addMonth()
);
```

#### getActiveSubscription()

Gets active subscription for a tenant.

```php
$subscription = Subscription::getActiveSubscription($tenantId);
```

#### trackUsage()

Tracks feature usage.

```php
$usage = Subscription::trackUsage(
    tenantId: $tenantId,
    featureKey: 'api_calls',
    amount: 100
);
```

#### canUseFeature()

Checks if feature can be used.

```php
$canUse = Subscription::canUseFeature(
    tenantId: $tenantId,
    featureKey: 'api_calls',
    limit: 10000
);
```

#### consumeFeature()

Consumes a feature (throws exception if limit exceeded).

```php
try {
    Subscription::consumeFeature(
        tenantId: $tenantId,
        featureKey: 'api_calls',
        amount: 100
    );
} catch (DomainException $e) {
    // Feature limit exceeded
}
```

#### getUsageStats()

Gets usage statistics for a tenant.

```php
$stats = Subscription::getUsageStats($tenantId);
// Returns:
// [
//     'api_calls' => [
//         'used' => 5000,
//         'limit' => 10000,
//         'percentage' => 50.0
//     ],
//     ...
// ]
```

---

## Subscription Enums

### SubscriptionStatusEnum

**Location:** `app/Enum/Subscription/SubscriptionStatusEnum.php`

**Values:**
```php
case Active = 'active';
case Expired = 'expired';
case Cancelled = 'cancelled';
```

### PlanCycleEnum

**Location:** `app/Enum/Subscription/PlanCycleEnum.php`

**Values:**
```php
case Weekly = 'weekly';
case Monthly = 'monthly';
case Quarterly = 'quarterly';
case Yearly = 'yearly';
```

---

## Usage Examples

### Create Tenant with Subscription

```php
use App\Services\Tenant\TenantService;

$tenantService = app(TenantService::class);

$tenant = $tenantService->createTenant(
    data: [
        'name' => 'Acme Corp',
        'domain' => 'acme.app.com',
        'database' => 'acme_db',
    ],
    planPriceId: 1,  // Professional plan, monthly
    subscriptionStartsAt: now()->toDateString()
);
```

### Check Feature Limit

```php
use App\Tools\Subscription\Facades\Subscription;

$subscription = Subscription::getActiveSubscription($tenantId);

if ($subscription) {
    $apiCallLimit = $subscription->getFeatureValue('api_calls');
    $daysRemaining = $subscription->daysRemaining();
    
    echo "API Calls Limit: $apiCallLimit";
    echo "Days Remaining: $daysRemaining";
}
```

### Track API Usage

```php
use App\Tools\Subscription\Facades\Subscription;

// In API endpoint
try {
    Subscription::consumeFeature(
        tenantId: $tenantId,
        featureKey: 'api_calls',
        amount: 1
    );
    
    // Process API request
} catch (DomainException $e) {
    return response()->json([
        'error' => 'API call limit exceeded'
    ], 429);
}
```

### Get Usage Statistics

```php
use App\Tools\Subscription\Facades\Subscription;

$stats = Subscription::getUsageStats($tenantId);

foreach ($stats as $feature => $usage) {
    echo "$feature: {$usage['used']}/{$usage['limit']} ({$usage['percentage']}%)";
}
```

---

## Middleware

### EnsureActiveSubscription

**Location:** `app/Http/Middleware/EnsureActiveSubscription.php`

Ensures tenant has active subscription before accessing routes.

**Usage:**

```php
Route::middleware('ensure.active.subscription')->group(function () {
    Route::get('/api/data', [DataController::class, 'index']);
});
```

---

## Best Practices

1. **Always Check Subscription** - Verify subscription before processing requests
2. **Track Usage** - Track feature usage for billing accuracy
3. **Handle Expiration** - Automatically expire subscriptions when end date passes
4. **Notify Users** - Notify tenants before subscription expires
5. **Graceful Degradation** - Provide limited functionality for expired subscriptions
6. **Log Changes** - Log all subscription changes for audit trail
7. **Test Scenarios** - Test subscription lifecycle thoroughly

---

## See Also

- [Multi-Tenancy](/guide/multitenancy/) — Multi-tenant architecture
- [Tenant Management](/guide/multitenancy/tenants) — Tenant operations
- [Configuration](/guide/configuration/) — Configuration options

