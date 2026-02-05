---
title: Database Migrations
description: Running migrations for central and tenant databases
---

# Database Migrations

The multi-tenant architecture requires separate migrations for central and tenant databases.

## Migration Directories

```
database/migrations/
├── central/                    # Central (landlord) database
│   ├── 2023_12_04_143331_create_countries.php
│   ├── 2023_12_04_143332_create_admins.php
│   ├── 2024_03_26_182238_create_personal_access_tokens_table.php
│   ├── 2024_04_01_165954_create_permission_tables.php
│   ├── 2024_04_16_152353_create_notifications_table.php
│   ├── 2024_04_17_091026_create_settings_table.php
│   ├── 2025_12_19_203844_create_tenants_table.php
│   ├── 2025_12_20_150000_create_plans_table.php
│   └── 2025_12_20_150020_create_subscriptions_table.php
└── tenant/                     # Tenant databases
    ├── 2023_12_04_200000_create_users_table.php
    ├── 2024_03_26_182238_create_personal_access_tokens_table.php
    ├── 2024_04_01_165954_create_permission_tables.php
    └── 2024_04_16_152353_create_notifications_table.php
```

## Shell Scripts

### Central Database Setup

`migrate_seed_central.sh`:

```bash
#!/bin/bash

set -e

PHP="/opt/homebrew/opt/php@8.4/bin/php"

echo "Running central (main) migrations..."
$PHP artisan migrate:fresh --path=database/migrations/central --database=mysql --force

echo "Seeding central database..."
$PHP artisan db:seed --class=Database\\Seeders\\Central\\DatabaseSeeder --force

echo "Central setup completed successfully."
```

### Tenant Database Setup

`migrate_seed_tenant.sh`:

```bash
#!/bin/bash

set -e

PHP="/opt/homebrew/opt/php@8.4/bin/php"

echo "Running migrations for all tenants..."
$PHP artisan tenants:artisan "migrate --path=database/migrations/tenant --database=tenant --force"

echo "Seeding all tenants..."
$PHP artisan tenants:artisan "db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder --force"

echo "All migrations and seeders completed successfully."
```

## Manual Commands

### Central Database

```bash
# Fresh migration (drops all tables)
php artisan migrate:fresh --path=database/migrations/central --database=mysql

# Regular migration
php artisan migrate --path=database/migrations/central --database=mysql

# Rollback
php artisan migrate:rollback --path=database/migrations/central --database=mysql

# Seed
php artisan db:seed --class=Database\\Seeders\\Central\\DatabaseSeeder
```

### All Tenant Databases

```bash
# Migrate all tenants
php artisan tenants:artisan "migrate --path=database/migrations/tenant --database=tenant"

# Fresh migrate all tenants
php artisan tenants:artisan "migrate:fresh --path=database/migrations/tenant --database=tenant"

# Seed all tenants
php artisan tenants:artisan "db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder"
```

### Specific Tenant

```bash
# Migrate specific tenant by ID
php artisan tenants:artisan "migrate --path=database/migrations/tenant --database=tenant" --tenant=tenant-uuid-here

# Seed specific tenant
php artisan tenants:artisan "db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder" --tenant=tenant-uuid-here
```

## Creating Migrations

### Central Migration

```bash
php artisan make:migration create_new_table --path=database/migrations/central
```

Example central migration:

```php
// database/migrations/central/2026_01_01_000000_create_features_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('features');
    }
};
```

### Tenant Migration

```bash
php artisan make:migration create_tenant_table --path=database/migrations/tenant
```

Example tenant migration:

```php
// database/migrations/tenant/2026_01_01_000000_create_orders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total', 10, 2);
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('orders');
    }
};
```

## Seeder Organization

### Central Seeders

```
database/seeders/Central/
├── DatabaseSeeder.php          # Main central seeder
├── AdminTableSeeder.php        # Admin users
├── CountrySeeder.php           # Countries data
├── PlanTableSeeder.php         # Subscription plans
├── SettingTableSeeder.php      # Settings
└── TenantTableSeeder.php       # Initial tenants
```

```php
// database/seeders/Central/DatabaseSeeder.php
namespace Database\Seeders\Central;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            SettingTableSeeder::class,
            AdminTableSeeder::class,
            PlanTableSeeder::class,
            TenantTableSeeder::class,
        ]);
    }
}
```

### Tenant Seeders

```
database/seeders/Tenant/
└── DatabaseSeeder.php          # Main tenant seeder
```

```php
// database/seeders/Tenant/DatabaseSeeder.php
namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed tenant-specific data
    }
}
```

## Initial Setup Workflow

1. **Configure environment**
   ```env
   DB_DATABASE=starter_central
   ```

2. **Create central database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE starter_central;"
   ```

3. **Run central migrations**
   ```bash
   ./migrate_seed_central.sh
   # OR
   php artisan migrate --path=database/migrations/central --database=mysql
   php artisan db:seed --class=Database\\Seeders\\Central\\DatabaseSeeder
   ```

4. **Create tenant databases** (via API or manually)
   ```bash
   mysql -u root -p -e "CREATE DATABASE tenant_acme;"
   ```

5. **Run tenant migrations**
   ```bash
   ./migrate_seed_tenant.sh
   # OR
   php artisan tenants:artisan "migrate --path=database/migrations/tenant --database=tenant"
   ```

## Artisan Commands

Custom artisan commands for tenant management:

```bash
# Setup command (creates database and runs migrations)
php artisan app:setup

# Tenant migrate/seed command
php artisan tenant:migrate-seed
```

## See Also

- [Multi-Tenancy Overview](/guide/multitenancy/)
- [Tenant Models](/guide/multitenancy/models)
- [Installation Guide](/guide/installation)
