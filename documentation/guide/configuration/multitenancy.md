---
title: Multi-Tenancy Configuration
description: Configure Spatie Multitenancy for database-per-tenant isolation
---

# Multi-Tenancy Configuration

The application uses **Spatie Multitenancy** with separate databases per tenant. Configuration is in `config/multitenancy.php`.

## Tenant Finder

The tenant finder determines the current tenant from the incoming request:

```php
'tenant_finder' => Spatie\Multitenancy\TenantFinder\DomainTenantFinder::class,
```

**DomainTenantFinder** matches the request domain against the `domain` column in the `tenants` table.

## Switch Tenant Tasks

Tasks executed when switching between tenants:

```php
'switch_tenant_tasks' => [
    \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
],
```

The `SwitchTenantDatabaseTask` switches the database connection to the tenant's database.

## Database Connections

```php
// Connection for tenant databases
'tenant_database_connection_name' => 'tenant',

// Connection for central/landlord database
'landlord_database_connection_name' => 'mysql',
```

These correspond to connections defined in `config/database.php`:

```php
'connections' => [
    // Central database (landlord)
    'mysql' => [
        'driver' => 'mysql',
        'database' => env('DB_DATABASE', 'laravel'),
        // ... other settings
    ],
    
    // Tenant database (switched dynamically)
    'tenant' => [
        'driver' => 'mysql',
        'database' => null,  // Set at runtime by SwitchTenantDatabaseTask
        // ... other settings
    ],
],
```

## Queue Tenant Awareness

```php
'queues_are_tenant_aware_by_default' => true,
```

When `true`, queued jobs automatically maintain tenant context. The current tenant ID is stored with the job and restored when the job executes.

## Tenant Model

```php
'tenant_model' => Spatie\Multitenancy\Models\Tenant::class,
```

The project extends this with `App\Models\Central\Tenant`:

```php
class Tenant extends \Spatie\Multitenancy\Models\Tenant
{
    use HasUuids, SoftDeletes, CreatedByObserver, UsesLandlordConnection;

    protected $fillable = [
        'name', 'domain', 'database', 'status', 'is_active', 'settings', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status' => TenantStatusEnum::class,
        'settings' => 'array',
    ];
}
```

## Context Keys

```php
// Key for storing tenant in context
'current_tenant_context_key' => 'tenantId',

// Key for binding tenant in container
'current_tenant_container_key' => 'currentTenant',
```

## Tenant-Aware Jobs

Configure specific jobs to be tenant-aware or not:

```php
// Interface for tenant-aware jobs
'tenant_aware_interface' => TenantAware::class,

// Interface for non-tenant-aware jobs
'not_tenant_aware_interface' => NotTenantAware::class,

// Specific jobs that should be tenant-aware
'tenant_aware_jobs' => [],

// Specific jobs that should NOT be tenant-aware
'not_tenant_aware_jobs' => [],
```

## Actions

Customize multitenancy behavior with custom actions:

```php
'actions' => [
    'make_tenant_current_action' => MakeTenantCurrentAction::class,
    'forget_current_tenant_action' => ForgetCurrentTenantAction::class,
    'make_queue_tenant_aware_action' => MakeQueueTenantAwareAction::class,
    'migrate_tenant' => MigrateTenantAction::class,
],
```

## Environment Variables

```env
# No specific env vars - configuration is code-based
# Tenant databases are created dynamically based on tenant records
```

## How It Works

1. **Request arrives** → `DomainTenantFinder` extracts domain from request
2. **Tenant lookup** → Finds tenant by `domain` column in `tenants` table
3. **Switch database** → `SwitchTenantDatabaseTask` sets the `tenant` connection to tenant's database
4. **Models auto-detect** → `BaseModel` and `BaseAuthenticatable` use `Tenant::current()` to choose connection

```php
// In BaseModel.php
protected function detectConnection(): string
{
    if (Tenant::current()) {
        return config('multitenancy.tenant_database_connection_name');
    }
    return config('multitenancy.landlord_database_connection_name');
}
```

## See Also

- [Multi-Tenancy Overview](/guide/multitenancy/)
- [Tenant Models](/guide/multitenancy/models)
- [Database Migrations](/guide/multitenancy/migrations)
