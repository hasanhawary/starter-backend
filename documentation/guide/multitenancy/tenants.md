---
title: Tenant Management
description: Creating, updating, and managing tenants with the TenantService
---

# Tenant Management

Tenants are organizations that use the platform. Each tenant has their own isolated database.

## Tenant Model

```php
// app/Models/Central/Tenant.php
namespace App\Models\Central;

use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Tenant extends \Spatie\Multitenancy\Models\Tenant
{
    use HasUuids, SoftDeletes, CreatedByObserver, UsesLandlordConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'domain',
        'database',
        'status',
        'is_active',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status' => TenantStatusEnum::class,
        'settings' => 'array',
    ];
}
```

### Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique tenant identifier |
| `name` | string | Tenant organization name |
| `domain` | string | Domain for tenant detection |
| `database` | string | Database name for tenant |
| `status` | TenantStatusEnum | Tenant status |
| `is_active` | boolean | Whether tenant is active |
| `settings` | array (JSON) | Tenant-specific settings |
| `created_by` | UUID | Admin who created the tenant |

### Relationships

```php
// Creator (admin who created)
public function creator(): BelongsTo
{
    return $this->belongsTo(Admin::class, 'created_by');
}

// Subscriptions
public function subscriptions(): HasMany
{
    return $this->hasMany(Subscription::class, 'tenant_id');
}
```

## TenantService

The `TenantService` handles tenant CRUD operations with subscription management.

```php
// app/Services/Tenant/TenantService.php
namespace App\Services\Tenant;

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

### Create Tenant

```php
use App\Services\Tenant\TenantService;

$service = new TenantService();

// Create tenant without subscription
$tenant = $service->createTenant([
    'name' => 'Acme Corporation',
    'domain' => 'acme.example.com',
    'database' => 'tenant_acme',
    'is_active' => true,
]);

// Create tenant with subscription
$tenant = $service->createTenant(
    data: [
        'name' => 'Acme Corporation',
        'domain' => 'acme.example.com',
        'database' => 'tenant_acme',
    ],
    planPriceId: 1,  // Plan price ID
    subscriptionStartsAt: '2026-01-01'
);
```

### Update Tenant

```php
$service = new TenantService();

// Update tenant data
$tenant = $service->updateTenant($tenant, [
    'name' => 'Acme Corp Updated',
    'settings' => ['theme' => 'dark'],
]);

// Update tenant and change subscription
$tenant = $service->updateTenant(
    tenant: $tenant,
    data: ['name' => 'Acme Corp'],
    planPriceId: 2  // New plan
);
```

## TenantController

API endpoints for tenant management:

```php
// app/Http/Controllers/API/Central/Tenant/TenantController.php

// List tenants
// GET /api/central/tenants
public function index(PageRequest $request): JsonResponse;

// Create tenant
// POST /api/central/tenants
public function store(TenantRequest $request): JsonResponse;

// Show tenant
// GET /api/central/tenants/{tenant}
public function show(Tenant $tenant): JsonResponse;

// Update tenant
// PUT /api/central/tenants/{tenant}
public function update(TenantRequest $request, Tenant $tenant): JsonResponse;

// Delete tenant
// DELETE /api/central/tenants
public function destroy(): JsonResponse;

// Restore tenant
// POST /api/central/tenants/restore
public function restore(): JsonResponse;
```

## API Examples

### List Tenants

```bash
GET /api/central/tenants?pageSize=20&search=acme

Authorization: Bearer {admin_token}
```

Response:

```json
{
  "status": true,
  "data": {
    "data": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Acme Corporation",
        "domain": "acme.example.com",
        "database": "tenant_acme",
        "status": "active",
        "is_active": true,
        "settings": {},
        "created_at": "2026-01-15T10:00:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "total": 1
    }
  }
}
```

### Create Tenant

```bash
POST /api/central/tenants
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "New Company",
  "domain": "newco.example.com",
  "database": "tenant_newco",
  "plan_price_id": 1,
  "is_active": true
}
```

### Update Tenant

```bash
PUT /api/central/tenants/{tenant_id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "Updated Company Name",
  "settings": {
    "theme": "dark",
    "language": "en"
  }
}
```

## Tenant Status Enum

```php
// app/Enum/Tenant/TenantStatusEnum.php
enum TenantStatusEnum: string
{
    use EnumMethods;
    
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Trial = 'trial';
    
    public static function keyName(): string
    {
        return 'tenant_status';
    }
}
```

## Tenant Settings

Tenant-specific configuration stored as JSON:

```php
// Store settings
$tenant->update([
    'settings' => [
        'theme' => 'dark',
        'language' => 'en',
        'timezone' => 'America/New_York',
        'features' => [
            'reports' => true,
            'exports' => true,
        ],
    ],
]);

// Access settings
$theme = $tenant->settings['theme'] ?? 'light';
```

## Database Creation

When creating a tenant, you need to:

1. Create the database
2. Run migrations
3. Seed initial data

```php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Create database
DB::statement("CREATE DATABASE {$tenant->database}");

// Run migrations for this tenant
Artisan::call('tenants:artisan', [
    'artisanCommand' => 'migrate --path=database/migrations/tenant --database=tenant --force',
    '--tenant' => $tenant->id,
]);

// Seed tenant data
Artisan::call('tenants:artisan', [
    'artisanCommand' => 'db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder --force',
    '--tenant' => $tenant->id,
]);
```

## Tenant Middleware

The `tenant` middleware ensures tenant context is set:

```php
// routes/tenant.php
Route::middleware('tenant')->group(function() {
    // All routes here require valid tenant context
});
```

## See Also

- [Multi-Tenancy Overview](/guide/multitenancy/)
- [Tenant Models](/guide/multitenancy/models)
- [Subscriptions](/guide/subscriptions/)
