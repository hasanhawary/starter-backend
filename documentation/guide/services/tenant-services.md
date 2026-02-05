---
title: Tenant Services
description: Tenant management with subscription integration
---

# Tenant Services

The `TenantService` handles tenant lifecycle management including creation, updates, and subscription integration.

## TenantService

```php
// app/Services/Tenant/TenantService.php
namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Models\Central\PlanPrice;
use App\Tools\Subscription\Facades\Subscription;

class TenantService
{
    /**
     * Create a tenant with optional subscription
     */
    public function createTenant(
        array $data, 
        ?int $planPriceId = null, 
        ?string $subscriptionStartsAt = null
    ): Tenant;

    /**
     * Update tenant and optionally its subscription
     */
    public function updateTenant(
        Tenant $tenant, 
        array $data, 
        ?int $planPriceId = null, 
        ?string $subscriptionStartsAt = null
    ): Tenant;
}
```

## Create Tenant

Create a new tenant with optional subscription:

```php
use App\Services\Tenant\TenantService;

$service = new TenantService();

// Create tenant without subscription
$tenant = $service->createTenant([
    'name' => 'Acme Corporation',
    'domain' => 'acme.example.com',
    'database' => 'tenant_acme',
    'is_active' => true,
    'settings' => [
        'theme' => 'light',
        'language' => 'en',
    ],
]);

// Create tenant with subscription
$tenant = $service->createTenant(
    data: [
        'name' => 'Acme Corporation',
        'domain' => 'acme.example.com',
        'database' => 'tenant_acme',
    ],
    planPriceId: 1,                        // Plan price ID
    subscriptionStartsAt: '2026-02-01'     // Subscription start date
);
```

### Implementation

```php
public function createTenant(
    array $data, 
    ?int $planPriceId = null, 
    ?string $subscriptionStartsAt = null
): Tenant
{
    return DB::transaction(function () use ($data, $planPriceId, $subscriptionStartsAt) {
        // Create the tenant
        $tenant = Tenant::create($data);

        // Create subscription if plan specified
        if ($planPriceId) {
            $this->createSubscriptionForTenant($tenant, $planPriceId, $subscriptionStartsAt);
        }

        return $tenant->refresh();
    });
}
```

## Update Tenant

Update tenant data and optionally change subscription:

```php
use App\Services\Tenant\TenantService;

$service = new TenantService();

// Update tenant data only
$tenant = $service->updateTenant($tenant, [
    'name' => 'Acme Corp Updated',
    'settings' => [
        'theme' => 'dark',
    ],
]);

// Update tenant and change subscription
$tenant = $service->updateTenant(
    tenant: $tenant,
    data: [
        'name' => 'Acme Corp',
    ],
    planPriceId: 2  // Upgrade to different plan
);
```

### Implementation

```php
public function updateTenant(
    Tenant $tenant, 
    array $data, 
    ?int $planPriceId = null, 
    ?string $subscriptionStartsAt = null
): Tenant
{
    return DB::transaction(function () use ($tenant, $data, $planPriceId, $subscriptionStartsAt) {
        // Update tenant data
        $tenant->update($data);

        // Handle subscription change
        if ($planPriceId) {
            $subscription = Subscription::getActiveSubscription($tenant->id);

            if ($subscription) {
                // Update existing subscription
                Subscription::updateSubscription($subscription, [
                    'plan_price_id' => $planPriceId,
                ]);
            } else {
                // Create new subscription
                $this->createSubscriptionForTenant($tenant, $planPriceId, $subscriptionStartsAt);
            }
        }

        return $tenant->refresh();
    });
}
```

## Subscription Integration

The service integrates with the Subscription facade:

```php
protected function createSubscriptionForTenant(
    Tenant $tenant, 
    int $planPriceId, 
    ?string $startsAt = null
): void
{
    // Get plan price to retrieve plan_id
    $planPrice = PlanPrice::findOrFail($planPriceId);

    $subscriptionData = [
        'tenant_id' => $tenant->id,
        'plan_id' => $planPrice->plan_id,
        'plan_price_id' => $planPriceId,
    ];

    if ($startsAt) {
        $subscriptionData['starts_at'] = $startsAt;
    }

    Subscription::createSubscription($subscriptionData);

    \Log::info('Subscription created for tenant', [
        'tenant_id' => $tenant->id,
        'plan_id' => $planPrice->plan_id,
    ]);
}
```

## Usage in Controllers

```php
// app/Http/Controllers/API/Central/Tenant/TenantController.php
class TenantController extends BaseController
{
    public function __construct(protected TenantService $tenantService)
    {
        parent::__construct();
    }

    public function store(TenantRequest $request): JsonResponse
    {
        Gate::authorize('create', Tenant::class);

        $tenant = $this->tenantService->createTenant(
            $request->validated(),
            $request->plan_price_id,
            $request->subscription_starts_at
        );

        return successResponse(
            new TenantResource($tenant), 
            __('api.created_success')
        );
    }

    public function update(TenantRequest $request, Tenant $tenant): JsonResponse
    {
        Gate::authorize('update', $tenant);

        $tenant = $this->tenantService->updateTenant(
            $tenant,
            $request->validated(),
            $request->plan_price_id,
            $request->subscription_starts_at
        );

        return successResponse(
            new TenantResource($tenant), 
            __('api.updated_success')
        );
    }
}
```

## Database Setup After Creation

After creating a tenant, set up their database:

```php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Create the database
DB::statement("CREATE DATABASE {$tenant->database}");

// Run migrations
Artisan::call('tenants:artisan', [
    'artisanCommand' => 'migrate --path=database/migrations/tenant --database=tenant --force',
    '--tenant' => $tenant->id,
]);

// Seed initial data
Artisan::call('tenants:artisan', [
    'artisanCommand' => 'db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder --force',
    '--tenant' => $tenant->id,
]);
```

## Error Handling

```php
public function createTenant(array $data, ?int $planPriceId = null, ?string $subscriptionStartsAt = null): Tenant
{
    return DB::transaction(function () use ($data, $planPriceId, $subscriptionStartsAt) {
        try {
            $tenant = Tenant::create($data);

            if ($planPriceId) {
                $this->createSubscriptionForTenant($tenant, $planPriceId, $subscriptionStartsAt);
            }

            return $tenant->refresh();
        } catch (\Throwable $exception) {
            \Log::error('Failed to create tenant', [
                'data' => $data,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    });
}
```

## See Also

- [Services Overview](/guide/services/)
- [Tenant Management](/guide/multitenancy/tenants)
- [Subscriptions](/guide/subscriptions/)
