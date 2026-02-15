---
title: Tenant-Aware Models
description: How models automatically detect and use the correct database connection
---

# Tenant-Aware Models

Models in the Laravel Multi-Tenant Dashboard Kit automatically detect whether they're in a tenant context and use the appropriate database connection.

## Base Classes

### BaseModel

For non-authenticatable models:

```php
// app/Models/BaseModel.php
namespace App\Models;

use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->setConnection($this->detectConnection());
    }

    protected function detectConnection(): string
    {
        if (Tenant::current()) {
            return config('multitenancy.tenant_database_connection_name');
        }
        return config('multitenancy.landlord_database_connection_name');
    }
}
```

### BaseAuthenticatable

For user models that need authentication:

```php
// app/Models/BaseAuthenticatable.php
namespace App\Models;

use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Foundation\Auth\User;

abstract class BaseAuthenticatable extends User
{
    public function __construct()
    {
        parent::__construct();
        $this->setConnection($this->detectConnection());
    }

    protected function detectConnection(): string
    {
        if (Tenant::current()) {
            return config('multitenancy.tenant_database_connection_name');
        }
        return config('multitenancy.landlord_database_connection_name');
    }
}
```

## How It Works

1. **Model instantiation** → Constructor is called
2. **Detect context** → Check if `Tenant::current()` exists
3. **Set connection** → Use `tenant` or `mysql` connection
4. **All queries** → Automatically use the correct database

```php
// In tenant context (Tenant::current() is set)
$user = User::find(1);  // Queries tenant database

// In central context (no tenant)
$admin = Admin::find(1);  // Queries central database
```

## Model Organization

### Central Models (`app/Models/Central/`)

Models that always use the central database:

```php
// app/Models/Central/Admin.php
namespace App\Models\Central;

use App\Models\BaseAuthenticatable;

class Admin extends BaseAuthenticatable
{
    // Uses landlord connection (mysql)
    // Even in tenant context, admins come from central DB
}
```

**Central models include:**
- `Admin` - Platform administrators
- `Tenant` - Tenant organizations (uses `UsesLandlordConnection` trait)
- `Plan`, `PlanPrice`, `PlanFeature` - Subscription plans
- `Subscription`, `SubscriptionUsage` - Subscriptions
- `Role`, `Permission` - Platform-wide roles
- `Setting` - Global settings
- `Country` - Reference data

### Tenant Models (`app/Models/Tenant/`)

Models that use the tenant database when in tenant context:

```php
// app/Models/Tenant/User.php
namespace App\Models\Tenant;

use App\Models\BaseAuthenticatable;

class User extends BaseAuthenticatable
{
    // Uses tenant connection when Tenant::current() exists
    // Uses landlord connection otherwise
}
```

## Special Cases

### Tenant Model

The `Tenant` model always uses the landlord connection:

```php
// app/Models/Central/Tenant.php
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Tenant extends \Spatie\Multitenancy\Models\Tenant
{
    use UsesLandlordConnection;
    
    // Always queries central database
}
```

### Personal Access Tokens

Sanctum tokens are stored in the central database:

```php
// app/Models/Central/PersonalAccessToken.php
class PersonalAccessToken extends BasePersonalAccessToken
{
    // Always uses landlord connection
}
```

## Creating Models

### Central Model

```php
// app/Models/Central/NewCentralModel.php
namespace App\Models\Central;

use App\Models\BaseModel;

class NewCentralModel extends BaseModel
{
    protected $fillable = ['name', 'description'];
    
    // Will use landlord connection (mysql)
}
```

### Tenant Model

```php
// app/Models/Tenant/NewTenantModel.php
namespace App\Models\Tenant;

use App\Models\BaseModel;

class NewTenantModel extends BaseModel
{
    protected $fillable = ['name', 'tenant_data'];
    
    // Will use tenant connection when in tenant context
}
```

## Common Traits

### CreatedByObserver

Automatically sets `created_by` on model creation:

```php
use App\Trait\Global\CreatedByObserver;

class Admin extends BaseAuthenticatable
{
    use CreatedByObserver;
    
    protected $fillable = ['name', 'email', 'created_by'];
}
```

### LogsActivityOptions

Enables activity logging:

```php
use App\Trait\Global\LogsActivityOptions;

class User extends BaseAuthenticatable
{
    use LogsActivityOptions;
    
    // Automatically logs changes
}
```

### HasRoles (Spatie)

For role-based permissions:

```php
use Spatie\Permission\Traits\HasRoles;

class Admin extends BaseAuthenticatable
{
    use HasRoles;
    
    protected string $guard_name = 'admin';
}
```

## Permission Properties

Models can define their permission operations:

```php
class Admin extends BaseAuthenticatable
{
    // Include in permission system
    public bool $inPermission = true;
    
    // Basic CRUD operations
    public array $basicOperations = ['create', 'update', 'delete'];
    
    // Special operations
    public array $specialOperations = [
        'view-all', 
        'view-own', 
        'restore', 
        'force-delete', 
        'toggle-active'
    ];
}
```

These properties are used by `RoleService` to generate permissions like:
- `create-admin`
- `update-admin`
- `view-all-admin`
- `restore-admin`

## Querying Across Connections

To query central data from tenant context:

```php
// Explicitly use central connection
$plans = Plan::on('mysql')->get();

// Or use the model that's configured for central
$plans = \App\Models\Central\Plan::all();
```

## See Also

- [Multi-Tenancy Overview](/guide/multitenancy/)
- [Migrations](/guide/multitenancy/migrations)
- [Multi-Tenancy Configuration](/guide/configuration/multitenancy)
