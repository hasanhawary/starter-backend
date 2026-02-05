---
title: Subscription System
description: Plans, pricing, and subscription management for tenants
---

# Subscription System

The subscription system manages plans, pricing tiers, features, and tenant subscriptions.

## Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Subscription System                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────┐  │
│  │    Plan     │───▶│ PlanPrice   │    │  PlanFeature    │  │
│  │             │    │             │    │                 │  │
│  │ • name      │    │ • price     │    │ • name          │  │
│  │ • desc      │    │ • interval  │    │ • value         │  │
│  │ • is_active │    │ • currency  │    │ • is_enabled    │  │
│  └──────┬──────┘    └─────────────┘    └─────────────────┘  │
│         │                                                    │
│         ▼                                                    │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │  Subscription   │───▶│     SubscriptionUsage           │ │
│  │                 │    │                                 │ │
│  │ • tenant_id     │    │ • feature_id                    │ │
│  │ • plan_id       │    │ • used                          │ │
│  │ • starts_at     │    │ • limit                         │ │
│  │ • ends_at       │    │                                 │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Models

### Plan

Subscription plans available to tenants.

```php
// app/Models/Central/Plan.php
class Plan extends BaseModel
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    // Relationships
    public function prices(): HasMany;
    public function features(): HasMany;
    public function subscriptions(): HasMany;
}
```

### PlanPrice

Pricing tiers for each plan (monthly, yearly, etc.).

```php
// app/Models/Central/PlanPrice.php
class PlanPrice extends BaseModel
{
    protected $fillable = [
        'plan_id',
        'price',
        'currency',
        'interval',          // monthly, yearly, etc.
        'interval_count',    // 1, 3, 6, 12
        'is_active',
    ];

    public function plan(): BelongsTo;
}
```

### PlanFeature

Features included in a plan.

```php
// app/Models/Central/PlanFeature.php
class PlanFeature extends BaseModel
{
    protected $fillable = [
        'plan_id',
        'name',
        'code',
        'value',            // Limit value or null for unlimited
        'is_enabled',
    ];

    public function plan(): BelongsTo;
}
```

### Subscription

Tenant subscription to a plan.

```php
// app/Models/Central/Subscription.php
class Subscription extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'plan_price_id',
        'starts_at',
        'ends_at',
        'canceled_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function tenant(): BelongsTo;
    public function plan(): BelongsTo;
    public function planPrice(): BelongsTo;
    public function usages(): HasMany;
}
```

### SubscriptionUsage

Track feature usage within a subscription.

```php
// app/Models/Central/SubscriptionUsage.php
class SubscriptionUsage extends BaseModel
{
    protected $fillable = [
        'subscription_id',
        'feature_id',
        'used',
        'reset_at',
    ];

    public function subscription(): BelongsTo;
    public function feature(): BelongsTo;
}
```

## API Endpoints

### Plans

```bash
# List plans
GET /api/central/plans

# Create plan
POST /api/central/plans
{
  "name": "Professional",
  "description": "For growing businesses",
  "is_active": true
}

# Update plan
PUT /api/central/plans/{plan}

# Delete plan
DELETE /api/central/plans
```

### Plan Prices

```bash
# List prices for a plan
GET /api/central/plans/{plan}/prices

# Create price
POST /api/central/plans/{plan}/prices
{
  "price": 29.99,
  "currency": "USD",
  "interval": "monthly",
  "interval_count": 1
}
```

### Plan Features

```bash
# List features for a plan
GET /api/central/plans/{plan}/features

# Create feature
POST /api/central/plans/{plan}/features
{
  "name": "API Calls",
  "code": "api_calls",
  "value": 10000,
  "is_enabled": true
}
```

### Subscriptions

```bash
# List subscriptions
GET /api/central/subscriptions

# Create subscription
POST /api/central/subscriptions
{
  "tenant_id": "uuid",
  "plan_id": 1,
  "plan_price_id": 1,
  "starts_at": "2026-02-01"
}

# Update subscription
PUT /api/central/subscriptions/{subscription}

# Cancel subscription
POST /api/central/subscriptions/{subscription}/cancel
```

## Controllers

### PlanController

```php
// app/Http/Controllers/API/Central/Subscription/PlanController.php
class PlanController extends BaseController
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Plan::class);

        $query = app(Pipeline::class)
            ->send(Plan::with(['prices', 'features']))
            ->through([PlanFilter::class, ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, PlanResource::class));
    }

    public function store(PlanRequest $request): JsonResponse
    {
        Gate::authorize('create', Plan::class);

        $plan = Plan::create($request->validated());

        return successResponse(new PlanResource($plan), __('api.created_success'));
    }
}
```

### SubscriptionController

```php
// app/Http/Controllers/API/Central/Subscription/SubscriptionController.php
class SubscriptionController extends BaseController
{
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Subscription::class);

        $query = app(Pipeline::class)
            ->send(Subscription::with(['tenant', 'plan', 'planPrice']))
            ->through([SubscriptionFilter::class, ActiveFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, SubscriptionResource::class));
    }
}
```

## Subscription Facade

```php
use App\Tools\Subscription\Facades\Subscription;

// Create subscription
Subscription::createSubscription([
    'tenant_id' => $tenant->id,
    'plan_id' => $plan->id,
    'plan_price_id' => $planPrice->id,
    'starts_at' => now(),
]);

// Get active subscription
$subscription = Subscription::getActiveSubscription($tenantId);

// Update subscription
Subscription::updateSubscription($subscription, [
    'plan_price_id' => $newPlanPriceId,
]);

// Cancel subscription
Subscription::cancelSubscription($subscription);

// Check feature usage
$canUse = Subscription::canUseFeature($subscription, 'api_calls');

// Increment usage
Subscription::incrementUsage($subscription, 'api_calls', 1);
```

## Creating Tenant with Subscription

```php
use App\Services\Tenant\TenantService;

$service = new TenantService();

$tenant = $service->createTenant(
    data: [
        'name' => 'Acme Corp',
        'domain' => 'acme.example.com',
        'database' => 'tenant_acme',
    ],
    planPriceId: 1,
    subscriptionStartsAt: '2026-02-01'
);
```

## Checking Subscription Status

```php
// Check if tenant has active subscription
$subscription = Subscription::getActiveSubscription($tenant->id);

if (!$subscription) {
    return failResponse('No active subscription', [], 402);
}

// Check specific feature
if (!Subscription::canUseFeature($subscription, 'exports')) {
    return failResponse('Feature not available in your plan', [], 403);
}
```

## See Also

- [Tenant Management](/guide/multitenancy/tenants)
- [Tenant Services](/guide/services/tenant-services)
- [API Reference](/guide/api-reference)
