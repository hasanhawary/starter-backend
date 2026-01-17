# Subscription Tool Structure

## Overview

The Subscription Tool is a lightweight, focused tool that provides essential subscription management functionality. It contains only the most important classes: Manager, Facade, Scopes, and Traits.

## Directory Structure

```
app/Tools/Subscription/
├── SubscriptionManager.php              # Main manager class
├── SubscriptionServiceProvider.php      # Service provider
├── Facades/
│   └── Subscription.php                 # Static facade access
├── Scopes/
│   ├── SubscriptionScopes.php           # Subscription query scopes
│   ├── PlanScopes.php                   # Plan query scopes
│   └── SubscriptionUsageScopes.php      # Usage query scopes
├── Traits/
│   ├── HasSubscriptionMethods.php       # Subscription methods trait
│   └── HasPlanMethods.php               # Plan methods trait
└── Services/
    ├── PlanService.php                  # Plan operations (from app/Services)
    └── SubscriptionService.php          # Subscription operations (from app/Services)
```

## Components

### 1. SubscriptionManager
Main entry point for the subscription tool. Provides unified access to services.

```php
use App\Tools\Subscription\SubscriptionManager;

$manager = app('subscription');
$manager->plan()->getActivePlans();
$manager->subscriptions()->getActiveSubscription($tenantId);
```

### 2. Facade
Static interface for convenient access.

```php
use App\Tools\Subscription\Facades\Subscription;

Subscription::plan()->getActivePlans();
Subscription::subscriptions()->getActiveSubscription($tenantId);
Subscription::trackUsage($tenantId, 'leads', 5);
```

### 3. Scopes

#### SubscriptionScopes
Query scopes for subscription queries:
- `active()` - Filter active subscriptions
- `expired()` - Filter expired subscriptions
- `cancelled()` - Filter cancelled subscriptions
- `forTenant($tenantId)` - Filter by tenant
- `forPlan($planId)` - Filter by plan
- `currentlyActive()` - Filter currently active subscriptions
- `expiringSoon($days)` - Filter subscriptions expiring soon

Usage:
```php
use App\Tools\Subscription\Scopes\SubscriptionScopes;

class Subscription extends Model
{
    use SubscriptionScopes;
}

// In queries
Subscription::active()->forTenant($tenantId)->get();
Subscription::expiringSoon(7)->get();
```

#### PlanScopes
Query scopes for plan queries:
- `active()` - Filter active plans
- `inactive()` - Filter inactive plans
- `forCurrency($currency)` - Filter by currency
- `forBillingCycle($cycle)` - Filter by billing cycle
- `withFeatures()` - Eager load features
- `withPrices()` - Eager load prices
- `byCode($code)` - Filter by code
- `search($search)` - Search by name or code

Usage:
```php
use App\Tools\Subscription\Scopes\PlanScopes;

class Plan extends Model
{
    use PlanScopes;
}

// In queries
Plan::active()->withFeatures()->withPrices()->get();
Plan::search('basic')->get();
```

#### SubscriptionUsageScopes
Query scopes for usage queries:
- `forTenant($tenantId)` - Filter by tenant
- `forFeature($featureKey)` - Filter by feature
- `currentPeriod()` - Filter current period
- `inPeriod($start, $end)` - Filter by period
- `forMonth($month, $year)` - Filter by month
- `highUsage($threshold)` - Filter high usage

Usage:
```php
use App\Tools\Subscription\Scopes\SubscriptionUsageScopes;

class SubscriptionUsage extends Model
{
    use SubscriptionUsageScopes;
}

// In queries
SubscriptionUsage::forTenant($tenantId)->forFeature('leads')->currentPeriod()->get();
SubscriptionUsage::highUsage(80)->get();
```

### 4. Traits

#### HasSubscriptionMethods
Add subscription methods to models (Tenant, User, etc.):

```php
use App\Tools\Subscription\Traits\HasSubscriptionMethods;

class Tenant extends Model
{
    use HasSubscriptionMethods;
}

// Usage
$tenant = Tenant::find($id);
$subscription = $tenant->getActiveSubscription();
$tenant->canUseFeature('leads');
$tenant->trackUsage('leads', 5);
$tenant->getUsageStats();
$tenant->isSubscriptionExpiringSoon(7);
```

Methods provided:
- `getActiveSubscription()` - Get active subscription
- `hasActiveSubscription()` - Check if has active subscription
- `canUseFeature($featureKey)` - Check if can use feature
- `getFeatureValue($featureKey)` - Get feature value
- `trackUsage($featureKey, $amount)` - Track usage
- `canUseFeatureWithLimit($featureKey, $limit)` - Check with limit
- `getUsageStats()` - Get usage statistics
- `getSubscriptionStatus()` - Get status
- `getSubscriptionDaysRemaining()` - Get days remaining
- `isSubscriptionExpiringSoon($days)` - Check if expiring soon
- `getSubscriptionPlan()` - Get plan
- `getSubscriptionPlanName()` - Get plan name

#### HasPlanMethods
Add plan methods to services or models:

```php
use App\Tools\Subscription\Traits\HasPlanMethods;

class PlanService
{
    use HasPlanMethods;
}

// Usage
$service = app(PlanService::class);
$service->getActivePlans();
$service->getPlanWithFeatures($planId);
$service->planHasFeature($planId, 'leads');
```

Methods provided:
- `getActivePlans()` - Get all active plans
- `getPlanWithFeatures($planId)` - Get plan with features
- `getPlanFeatures($planId)` - Get plan features array
- `planHasFeature($planId, $featureKey)` - Check if plan has feature
- `getPlanFeatureValue($planId, $featureKey)` - Get feature value
- `getPlanPrices($planId)` - Get plan prices
- `comparePlans($planId1, $planId2)` - Compare two plans
- `addPlanFeature($planId, $featureKey, $value)` - Add feature
- `updatePlanFeature($featureId, $value)` - Update feature
- `removePlanFeature($featureId)` - Remove feature
- `syncPlanPrices($plan, $prices)` - Sync prices

## Usage Examples

### Example 1: Check Feature Access in Controller

```php
use App\Tools\Subscription\Facades\Subscription;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Check if tenant can create leads
        if (!Subscription::canUseFeature($tenantId, 'leads', 100)) {
            return response()->json(['error' => 'Lead limit reached'], 429);
        }
        
        // Create lead
        $lead = Lead::create($request->validated());
        
        // Track usage
        Subscription::trackUsage($tenantId, 'leads', 1);
        
        return response()->json($lead);
    }
}
```

### Example 2: Use Scopes in Query

```php
use App\Models\Central\Subscription;

// Get all active subscriptions for a tenant
$subscriptions = Subscription::active()
    ->forTenant($tenantId)
    ->with('plan')
    ->get();

// Get subscriptions expiring soon
$expiringSoon = Subscription::expiringSoon(7)->get();
```

### Example 3: Use Traits in Model

```php
use App\Models\Central\Tenant;
use App\Tools\Subscription\Traits\HasSubscriptionMethods;

class Tenant extends Model
{
    use HasSubscriptionMethods;
}

// In service or controller
$tenant = Tenant::find($id);

if ($tenant->hasActiveSubscription()) {
    $plan = $tenant->getSubscriptionPlan();
    $stats = $tenant->getUsageStats();
    
    if ($tenant->isSubscriptionExpiringSoon(7)) {
        // Send renewal reminder
    }
}
```

### Example 4: Compare Plans

```php
use App\Tools\Subscription\Facades\Subscription;

$comparison = Subscription::plan()->comparePlans($basicPlanId, $proPlanId);

// Display comparison
foreach ($comparison['plan1']['features'] as $key => $value) {
    echo "{$key}: {$value}";
}
```

## Registration

The tool is registered via `SubscriptionServiceProvider`. Ensure it's added to `config/app.php`:

```php
'providers' => [
    // ...
    App\Tools\Subscription\SubscriptionServiceProvider::class,
],
```

And register the Facade:

```php
'aliases' => [
    // ...
    'Subscription' => App\Tools\Subscription\Facades\Subscription::class,
],
```

## Key Features

✅ **Lightweight** - Only essential classes
✅ **Focused** - Clear separation of concerns
✅ **Reusable** - Scopes and traits for easy integration
✅ **Flexible** - Works with existing services
✅ **Type-safe** - Full type hints
✅ **Well-documented** - Comprehensive docblocks

## Related Files

- Services: `app/Services/Subscription/`
- Models: `app/Models/Central/`
- Controllers: `app/Http/Controllers/API/Central/Subscription/`
- Requests: `app/Http/Requests/Central/Subscription/`
- Resources: `app/Http/Resources/Central/Subscription/`
- Policies: `app/Policies/Central/Subscription/`
- Enums: `app/Enum/Subscription/`
- Filters: `app/Filters/Central/Subscription/`
- Traits: `app/Trait/Subscription/`
