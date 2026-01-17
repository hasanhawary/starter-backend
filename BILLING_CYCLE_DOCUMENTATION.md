# Billing Module - Complete Documentation

## Overview
The billing module provides a comprehensive subscription management system with plans, features, subscriptions, and usage tracking.

## Database Structure

### Tables

#### 1. `plans`
- `id` - Primary key
- `code` - Unique plan identifier
- `name` - Plan name
- `price` - Plan price (decimal)
- `currency` - Currency code (ISO 4217: USD, EGP, EUR, etc.)
- `billing_cycle` - Billing frequency (monthly, yearly, quarterly, weekly)
- `is_active` - Plan active status (boolean)
- `created_by` - Admin who created the plan
- `timestamps`

#### 2. `plan_features`
- `id` - Primary key
- `plan_id` - Foreign key to plans
- `key` - Feature identifier (e.g., 'api_calls', 'storage', 'users')
- `value` - Feature limit/value
- `created_by` - Admin who created the feature
- `timestamps`

#### 3. `subscriptions`
- `id` - Primary key
- `tenant_id` - Foreign key to tenants (UUID)
- `plan_id` - Foreign key to plans
- `starts_at` - Subscription start date
- `ends_at` - Subscription end date (nullable for lifetime)
- `status` - Subscription status (active, expired, cancelled)
- `created_by` - Admin who created the subscription
- `timestamps`

#### 4. `subscription_usage`
- `id` - Primary key
- `tenant_id` - Foreign key to tenants (UUID)
- `key` - Feature being tracked
- `used_value` - Current usage amount
- `period_start` - Usage period start
- `period_end` - Usage period end
- `created_by` - Admin who created the record
- `timestamps`

## Models

### Plan Model
**Location:** `app/Models/Central/Plan.php`

**Key Features:**
- Active/Inactive status management
- Scopes: `active()`, `inactive()`
- Helper methods: `isActive()`, `activate()`, `deactivate()`, `toggleActive()`
- Relations: `features()`, `subscriptions()`, `creator()`

### Subscription Model
**Location:** `app/Models/Central/Subscription.php`

**Key Features:**
- Status management (active, expired, cancelled)
- Scopes: `active()`, `expired()`, `cancelled()`, `forTenant()`
- Helper methods: `isActive()`, `isExpired()`, `isCancelled()`, `daysRemaining()`, `hasFeature()`, `getFeatureValue()`
- Relations: `plan()`, `tenant()`, `usages()`, `creator()`

### PlanFeature Model
**Location:** `app/Models/Central/PlanFeature.php`

**Relations:** `plan()`, `creator()`

### SubscriptionUsage Model
**Location:** `app/Models/Central/SubscriptionUsage.php`

**Key Features:**
- Scopes: `forTenant()`, `forFeature()`, `currentPeriod()`
- Helper methods: `incrementUsage()`, `decrementUsage()`, `resetUsage()`
- Relations: `tenant()`, `creator()`

## Services

### SubscriptionService
**Location:** `app/Services/Billing/SubscriptionService.php`

**Methods:**
- `changeStatus()` - Change subscription status
- `cancel()` - Cancel a subscription
- `renew()` - Renew a subscription with new dates
- `createSubscription()` - Create new subscription for tenant
- `calculateEndDate()` - Calculate end date based on billing cycle
- `checkAndUpdateExpiration()` - Check and update expired subscriptions
- `getActiveSubscription()` - Get active subscription for tenant
- `trackUsage()` - Track feature usage
- `canUseFeature()` - Check if tenant can use feature
- `getUsageStats()` - Get usage statistics for tenant
- Status helpers: `isActive()`, `isExpired()`, `isCancelled()`

### PlanService
**Location:** `app/Services/Billing/PlanService.php`

**Methods:**
- `getActivePlans()` - Get all active plans
- `getPlanWithFeatures()` - Get plan with features
- `addFeature()` - Add feature to plan
- `updateFeature()` - Update plan feature
- `removeFeature()` - Remove feature from plan
- `getPlanFeaturesArray()` - Get features as key-value array
- `hasFeature()` - Check if plan has feature
- `getFeatureValue()` - Get feature value
- `comparePlans()` - Compare two plans

## Controllers

### PlanController
**Location:** `app/Http/Controllers/API/Central/Billing/PlanController.php`

**Endpoints:**
- `GET /api/central/billing/plans` - List all plans
- `POST /api/central/billing/plans` - Create new plan
- `GET /api/central/billing/plans/{id}` - Get plan details
- `PUT /api/central/billing/plans/{id}` - Update plan
- `DELETE /api/central/billing/plans/{id}` - Soft delete plan
- `POST /api/central/billing/plans/restore` - Restore deleted plans
- `DELETE /api/central/billing/plans/force-delete` - Permanently delete plans
- `PUT /api/central/billing/plans/toggle-active` - Toggle plan active status

### SubscriptionController
**Location:** `app/Http/Controllers/API/Central/Billing/SubscriptionController.php`

**Endpoints:**
- `GET /api/central/billing/subscriptions` - List all subscriptions
- `POST /api/central/billing/subscriptions` - Create new subscription
- `GET /api/central/billing/subscriptions/{id}` - Get subscription details
- `PUT /api/central/billing/subscriptions/{id}` - Update subscription
- `DELETE /api/central/billing/subscriptions/{id}` - Soft delete subscription
- `POST /api/central/billing/subscriptions/{id}/change-status` - Change subscription status
- `POST /api/central/billing/subscriptions/{id}/cancel` - Cancel subscription
- `POST /api/central/billing/subscriptions/{id}/renew` - Renew subscription
- `POST /api/central/billing/subscriptions/restore` - Restore deleted subscriptions
- `DELETE /api/central/billing/subscriptions/force-delete` - Permanently delete subscriptions

### PlanFeatureController
**Location:** `app/Http/Controllers/API/Central/Billing/PlanFeatureController.php`

**Endpoints:**
- `GET /api/central/billing/plan-features` - List all features
- `POST /api/central/billing/plan-features` - Create new feature
- `GET /api/central/billing/plan-features/{id}` - Get feature details
- `PUT /api/central/billing/plan-features/{id}` - Update feature
- `DELETE /api/central/billing/plan-features/{id}` - Soft delete feature
- `POST /api/central/billing/plan-features/restore` - Restore deleted features
- `DELETE /api/central/billing/plan-features/force-delete` - Permanently delete features

### SubscriptionUsageController
**Location:** `app/Http/Controllers/API/Central/Billing/SubscriptionUsageController.php`

**Endpoints:**
- `GET /api/central/billing/subscription-usage` - List all usage records
- `GET /api/central/billing/subscription-usage/{id}` - Get usage details

## Policies

All billing models have corresponding policies in `app/Policies/Central/Billing/`:
- `PlanPolicy.php`
- `PlanFeaturePolicy.php`
- `SubscriptionPolicy.php`
- `SubscriptionUsagePolicy.php`

**Permissions checked:**
- `view-all-{resource}` / `view-own-{resource}`
- `create-{resource}`
- `update-{resource}`
- `delete-{resource}`
- `restore-{resource}`
- `force-delete-{resource}`

## Filters

### PlanFilter
**Location:** `app/Filters/Central/Billing/PlanFilter.php`

Filters plans by:
- `search` - Search in name and code

### SubscriptionFilter
**Location:** `app/Filters/Central/Billing/SubscriptionFilter.php`

Filters subscriptions by:
- `search` - Search in tenant_id
- `status` - Filter by status
- `plan_id` - Filter by plan

## Commands

### CheckExpiredSubscriptions
**Location:** `app/Console/Commands/CheckExpiredSubscriptions.php`

**Usage:** `php artisan subscriptions:check-expired`

**Purpose:** Automatically checks and updates expired subscriptions, and warns about subscriptions expiring within 7 days.

**Schedule:** Add to `app/Console/Kernel.php`:
```php
$schedule->command('subscriptions:check-expired')->daily();
```

## Usage Examples

### Creating a Subscription
```php
use App\Services\Billing\SubscriptionService;

$service = app(SubscriptionService::class);
$subscription = $service->createSubscription(
    tenantId: 'tenant-uuid',
    planId: 1,
    startsAt: now(),
    endsAt: now()->addMonth()
);
```

### Getting Plan with Currency
```php
use App\Models\Central\Plan;

$plan = Plan::find(1);
echo $plan->currency; // USD, EGP, EUR, etc.
echo $plan->price; // 99.99
echo $plan->currency . ' ' . number_format($plan->price, 2); // USD 99.99
```

### Tracking Feature Usage
```php
use App\Services\Billing\SubscriptionService;

$service = app(SubscriptionService::class);
$service->trackUsage('tenant-uuid', 'api_calls', 1);
```

### Checking Feature Limits
```php
use App\Services\Billing\SubscriptionService;

$service = app(SubscriptionService::class);
$canUse = $service->canUseFeature('tenant-uuid', 'api_calls', 1000);
```

### Getting Usage Statistics
```php
use App\Services\Billing\SubscriptionService;

$service = app(SubscriptionService::class);
$stats = $service->getUsageStats('tenant-uuid');
// Returns: ['api_calls' => ['used' => 500, 'limit' => 1000, 'percentage' => 50.0]]
```

### Managing Plans
```php
use App\Services\Billing\PlanService;

$service = app(PlanService::class);

// Add feature to plan
$service->addFeature(1, 'api_calls', '1000');

// Get plan features
$features = $service->getPlanFeaturesArray(1);

// Compare plans
$comparison = $service->comparePlans(1, 2);
```

## Middleware Integration

The `EnsureActiveSubscription` middleware checks if a tenant has an active subscription:

**Location:** `app/Http/Middleware/EnsureActiveSubscription.php`

**Usage in routes:**
```php
Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    // Protected routes
});
```

## Migration

To add the `is_active` column to existing plans table:
```bash
php artisan migrate
```

The migration file is located at:
`database/migrations/central/2026_01_16_000001_add_is_active_to_plans_table.php`

## Best Practices

1. **Always check subscription status** before allowing tenant actions
2. **Track usage regularly** for features with limits
3. **Run the check-expired command daily** to keep subscriptions up to date
4. **Use scopes** for efficient queries (e.g., `Subscription::active()->forTenant($id)`)
5. **Leverage helper methods** instead of direct property access
6. **Use the services** for complex operations instead of direct model manipulation

## Testing

Example test cases to implement:
- Creating subscriptions with different billing cycles
- Tracking and limiting feature usage
- Expiring subscriptions automatically
- Cancelling and renewing subscriptions
- Toggling plan active status
- Checking permissions for billing operations
