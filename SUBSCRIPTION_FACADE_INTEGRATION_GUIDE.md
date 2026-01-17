# Subscription Facade Integration Guide

## Overview

The Subscription controllers and Tenant controller have been updated to use the Subscription Facade for cleaner, more maintainable code. This guide shows how to use the facade in your controllers and services.

## Controllers Updated

### 1. SubscriptionController
Uses the Facade to manage subscriptions:

```php
use App\Tools\Subscription\Facades\Subscription as SubscriptionFacade;

class SubscriptionController extends BaseController
{
    public function changeStatus(ChangeSubscriptionStatusRequest $request, Subscription $subscription): JsonResponse
    {
        $statusEnum = SubscriptionStatusEnum::from($request->input('status'));
        $subscription = SubscriptionFacade::subscriptions()->changeStatus($subscription, $statusEnum)->load('plan');
        
        return successResponse(new SubscriptionResource($subscription), __('api.updated_success'));
    }

    public function cancel(Subscription $subscription): JsonResponse
    {
        $subscription = SubscriptionFacade::subscriptions()->cancel($subscription)->load('plan');
        
        return successResponse(new SubscriptionResource($subscription), __('api.updated_success'));
    }

    public function renew(RenewSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $subscription = SubscriptionFacade::subscriptions()
            ->renew($subscription, $request->input('starts_at'), $request->input('ends_at'))
            ->load('plan');
        
        return successResponse(new SubscriptionResource($subscription), __('api.updated_success'));
    }
}
```

### 2. PlanController
Uses the Facade to manage plans:

```php
use App\Tools\Subscription\Facades\Subscription;

class PlanController extends BaseController
{
    public function store(PlanRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $plan = Plan::create($request->validated());
            
            // Sync prices using facade
            Subscription::plan()->syncPrices($plan, $request->input('prices', []));

            return successResponse(new PlanResource($plan->load('features', 'prices')), __('api.created_success'));
        });
    }

    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        return DB::transaction(function () use ($plan, $request) {
            $plan->update($request->validated());
            
            // Sync prices using facade
            Subscription::plan()->syncPrices($plan, $request->input('prices', []));

            return successResponse(new PlanResource($plan->refresh()->load('features', 'prices')), __('api.updated_success'));
        });
    }
}
```

### 3. TenantController - New Feature Methods

The Tenant controller now includes subscription feature checking methods:

#### Check if Tenant Can Use Feature
```php
GET /central/tenants/{tenant}/can-use-feature/{featureKey}

Response:
{
    "success": true,
    "data": {
        "tenant_id": "uuid",
        "feature": "leads",
        "can_use": true
    }
}
```

#### Check if Tenant Can Use Feature with Limit
```php
POST /central/tenants/{tenant}/can-use-feature-with-limit/{featureKey}/{limit}

Response:
{
    "success": true,
    "data": {
        "tenant_id": "uuid",
        "feature": "leads",
        "limit": 100,
        "can_use": true
    }
}
```

#### Get Tenant Subscription Info
```php
GET /central/tenants/{tenant}/subscription-info

Response:
{
    "success": true,
    "data": {
        "tenant_id": "uuid",
        "has_subscription": true,
        "subscription": {
            "id": 1,
            "plan_id": 1,
            "plan_name": "Pro Plan",
            "status": "active",
            "starts_at": "2026-01-16T00:00:00Z",
            "ends_at": "2026-02-16T00:00:00Z",
            "days_remaining": 31,
            "is_active": true,
            "is_expired": false,
            "is_cancelled": false
        }
    }
}
```

#### Get Tenant Usage Statistics
```php
GET /central/tenants/{tenant}/usage-stats

Response:
{
    "success": true,
    "data": {
        "tenant_id": "uuid",
        "usage": {
            "leads": {
                "used": 45,
                "limit": 100,
                "percentage": 45
            },
            "users": {
                "used": 3,
                "limit": 5,
                "percentage": 60
            }
        }
    }
}
```

#### Get Tenant Plan Features
```php
GET /central/tenants/{tenant}/plan-features

Response:
{
    "success": true,
    "data": {
        "tenant_id": "uuid",
        "plan_id": 1,
        "plan_name": "Pro Plan",
        "features": {
            "leads": "100",
            "users": "5",
            "projects": "10",
            "campaigns": "5"
        }
    }
}
```

#### Track Feature Usage
```php
POST /central/tenants/{tenant}/track-usage/{featureKey}/{amount?}

Example: POST /central/tenants/uuid/track-usage/leads/5

Response:
{
    "success": true,
    "data": {
        "tenant_id": "uuid",
        "feature": "leads",
        "amount": 5,
        "message": "Usage tracked successfully"
    }
}
```

## Usage Examples

### Example 1: Create User with Feature Check

```php
// In a Tenant service or controller
use App\Tools\Subscription\Facades\Subscription;

public function createUser(Tenant $tenant, array $data)
{
    // Check if tenant can create more users
    if (!Subscription::canUseFeature($tenant->id, 'users', 5)) {
        throw new Exception('User limit reached for this plan');
    }

    // Create user
    $user = $tenant->users()->create($data);

    // Track usage
    Subscription::trackUsage($tenant->id, 'users', 1);

    return $user;
}
```

### Example 2: Create Lead with Feature Check

```php
// In a Lead service or controller
use App\Tools\Subscription\Facades\Subscription;

public function createLead(Tenant $tenant, array $data)
{
    // Check if tenant can create more leads
    if (!Subscription::canUseFeature($tenant->id, 'leads', 100)) {
        return response()->json(['error' => 'Lead limit reached'], 429);
    }

    // Create lead
    $lead = $tenant->leads()->create($data);

    // Track usage
    Subscription::trackUsage($tenant->id, 'leads', 1);

    return $lead;
}
```

### Example 3: Create Project with Feature Check

```php
// In a Project service or controller
use App\Tools\Subscription\Facades\Subscription;

public function createProject(Tenant $tenant, array $data)
{
    // Get subscription info
    $subscription = Subscription::getActiveSubscription($tenant->id);

    if (!$subscription) {
        return response()->json(['error' => 'No active subscription'], 403);
    }

    // Check if tenant can create more projects
    $projectLimit = Subscription::plan()->getFeatureValue($subscription->plan_id, 'projects');
    
    if (!$projectLimit || $tenant->projects()->count() >= (int)$projectLimit) {
        return response()->json(['error' => 'Project limit reached'], 429);
    }

    // Create project
    $project = $tenant->projects()->create($data);

    // Track usage
    Subscription::trackUsage($tenant->id, 'projects', 1);

    return $project;
}
```

### Example 4: Check Multiple Features

```php
use App\Tools\Subscription\Facades\Subscription;

public function canPerformAction(Tenant $tenant): bool
{
    // Check multiple features
    $canCreateLead = Subscription::canUseFeature($tenant->id, 'leads', 100);
    $canCreateUser = Subscription::canUseFeature($tenant->id, 'users', 5);
    $canCreateProject = Subscription::canUseFeature($tenant->id, 'projects', 10);

    return $canCreateLead && $canCreateUser && $canCreateProject;
}
```

### Example 5: Get Usage Statistics

```php
use App\Tools\Subscription\Facades\Subscription;

public function getSubscriptionDashboard(Tenant $tenant)
{
    $stats = Subscription::getUsageStats($tenant->id);
    $subscription = Subscription::getActiveSubscription($tenant->id);

    return [
        'plan' => $subscription?->plan->name,
        'status' => $subscription?->status,
        'days_remaining' => $subscription?->daysRemaining(),
        'usage' => $stats,
    ];
}
```

## Facade Methods

### Subscription Methods
- `getActiveSubscription($tenantId)` - Get active subscription
- `createSubscription($tenantId, $planId, $startsAt, $endsAt)` - Create subscription
- `trackUsage($tenantId, $featureKey, $amount)` - Track usage
- `canUseFeature($tenantId, $featureKey, $limit)` - Check feature limit
- `getUsageStats($tenantId)` - Get usage statistics

### Plan Methods
- `getActivePlans()` - Get all active plans
- `getPlanWithFeatures($planId)` - Get plan with features
- `getPlanFeaturesArray($planId)` - Get features as array
- `hasFeature($planId, $featureKey)` - Check if plan has feature
- `getFeatureValue($planId, $featureKey)` - Get feature value
- `getPrices($planId)` - Get plan prices
- `comparePlans($planId1, $planId2)` - Compare plans
- `addFeature($planId, $featureKey, $value)` - Add feature
- `updateFeature($featureId, $value)` - Update feature
- `removeFeature($featureId)` - Remove feature
- `syncPrices($plan, $prices)` - Sync prices

## Routes

### Tenant Subscription Routes

```
GET    /central/tenants/{tenant}/subscription-info
GET    /central/tenants/{tenant}/usage-stats
GET    /central/tenants/{tenant}/plan-features
GET    /central/tenants/{tenant}/can-use-feature/{featureKey}
POST   /central/tenants/{tenant}/can-use-feature-with-limit/{featureKey}/{limit}
POST   /central/tenants/{tenant}/track-usage/{featureKey}/{amount?}
```

## Best Practices

1. **Always check features before operations**
   ```php
   if (!Subscription::canUseFeature($tenantId, 'leads', 100)) {
       return error_response('Feature limit reached');
   }
   ```

2. **Track usage after successful operations**
   ```php
   $resource = create($data);
   Subscription::trackUsage($tenantId, 'leads', 1);
   ```

3. **Use transactions for atomic operations**
   ```php
   DB::transaction(function () {
       $resource = create($data);
       Subscription::trackUsage($tenantId, 'feature', 1);
   });
   ```

4. **Cache subscription info when possible**
   ```php
   $subscription = Cache::remember(
       "subscription.{$tenantId}",
       3600,
       fn() => Subscription::getActiveSubscription($tenantId)
   );
   ```

5. **Handle expired subscriptions gracefully**
   ```php
   $subscription = Subscription::getActiveSubscription($tenantId);
   if (!$subscription || $subscription->isExpired()) {
       return error_response('Subscription expired');
   }
   ```

## Testing

```php
// Test feature checking
$this->assertTrue(Subscription::canUseFeature($tenantId, 'leads', 100));

// Test usage tracking
Subscription::trackUsage($tenantId, 'leads', 5);
$stats = Subscription::getUsageStats($tenantId);
$this->assertEquals(5, $stats['leads']['used']);

// Test subscription info
$subscription = Subscription::getActiveSubscription($tenantId);
$this->assertNotNull($subscription);
$this->assertTrue($subscription->isActive());
```

## Summary

The Subscription Facade provides a clean, consistent interface for:
- ✅ Checking feature availability
- ✅ Tracking feature usage
- ✅ Managing subscriptions
- ✅ Managing plans
- ✅ Getting subscription information

Use it in your controllers, services, and models to enforce subscription limits and track usage across your application.
