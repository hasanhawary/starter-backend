---
title: Troubleshooting
description: Common issues and solutions for multi-tenant application
---

# Troubleshooting

This guide provides solutions to common issues you might encounter when using the multi-tenant backend.

## Installation & Setup Issues

### Issue: Composer Install Fails

**Problem:** `composer install` command fails with dependency errors.

**Solutions:**

1. Clear composer cache:
```bash
composer clear-cache
```

2. Update composer:
```bash
composer self-update
```

3. Try installing with no-dev flag:
```bash
composer install --no-dev
```

4. Check PHP version compatibility:
```bash
php -v
# Should be PHP 8.3 or higher
```

---

### Issue: Database Connection Failed

**Problem:** `SQLSTATE[HY000]: General error: 1030 Got error...`

**Solutions:**

1. Check database credentials in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=central_db
DB_USERNAME=root
DB_PASSWORD=password
```

2. Verify MySQL is running:
```bash
# macOS
brew services list

# Linux
sudo systemctl status mysql

# Windows
services.msc
```

3. Create central database if it doesn't exist:
```bash
mysql -u root -p
CREATE DATABASE central_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

4. Run migrations:
```bash
php artisan migrate
```

---

### Issue: Tenant Database Not Created

**Problem:** Tenant creation succeeds but tenant database is not created.

**Solutions:**

1. Check tenancy configuration:
```php
// config/tenancy.php
'database_manager' => 'sqlite', // or 'mysql'
```

2. Verify tenant database strategy:
```bash
php artisan tinker
>>> config('tenancy.database_manager')
```

3. Create tenant manually:
```bash
php artisan tenant:create --name="Acme Corp" --domain="acme.app.com"
```

4. Run tenant migrations:
```bash
php artisan migrate --tenants
```

---

### Issue: Key Generation Failed

**Problem:** `RuntimeException: No application encryption key has been specified.`

**Solutions:**

```bash
# Generate application key
php artisan key:generate

# Verify key is set in .env
cat .env | grep APP_KEY
```

---

## Multi-Tenancy Issues

### Issue: Tenant Not Found

**Problem:** `TenantNotFound` exception when accessing tenant domain.

**Solutions:**

1. Verify tenant exists:
```bash
php artisan tinker
>>> App\Models\Tenant::all()
```

2. Check tenant domain:
```bash
php artisan tinker
>>> $tenant = App\Models\Tenant::where('domain', 'acme.app.com')->first()
>>> $tenant
```

3. Create tenant if missing:
```bash
php artisan tenant:create --name="Acme Corp" --domain="acme.app.com"
```

4. Verify domain DNS:
```bash
nslookup acme.app.com
```

---

### Issue: Tenant Database Not Switching

**Problem:** Queries execute on central database instead of tenant database.

**Solutions:**

1. Check tenant middleware is applied:
```php
// routes/tenant.php
Route::middleware(['tenant'])->group(function () {
    // Routes here
});
```

2. Verify tenant is initialized:
```bash
php artisan tinker
>>> tenancy()->tenant()
```

3. Check database connection:
```bash
php artisan tinker
>>> DB::connection()->getDatabaseName()
```

4. Manually switch tenant:
```bash
php artisan tinker
>>> $tenant = App\Models\Tenant::first()
>>> tenancy()->initialize($tenant)
>>> DB::connection()->getDatabaseName()
```

---

### Issue: Tenant Migrations Not Running

**Problem:** `php artisan migrate --tenants` doesn't run migrations.

**Solutions:**

1. Verify tenant migrations exist:
```bash
ls database/migrations/tenant/
```

2. Check migration status:
```bash
php artisan migrate:status --tenants
```

3. Run migrations for specific tenant:
```bash
php artisan migrate --tenants --tenant=1
```

4. Reset and re-run:
```bash
php artisan migrate:reset --tenants
php artisan migrate --tenants
```

---

### Issue: Tenant Data Isolation Not Working

**Problem:** Tenant A can see Tenant B's data.

**Solutions:**

1. Add tenant scope to models:
```php
// app/Models/User.php
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;

class User extends Model
{
    use UsesTenantModel;
}
```

2. Verify scope is applied:
```bash
php artisan tinker
>>> $users = App\Models\User::all()
>>> $users->first()->tenant_id
```

3. Check queries include tenant filter:
```bash
php artisan tinker
>>> DB::enableQueryLog()
>>> App\Models\User::all()
>>> dd(DB::getQueryLog())
```

---

## Authentication Issues

### Issue: Login Returns 401 Unauthorized

**Problem:** Login fails with "Invalid email or password" even with correct credentials.

**Solutions:**

1. Verify user exists in tenant database:
```bash
php artisan tinker
>>> tenancy()->initialize(App\Models\Tenant::first())
>>> App\Models\User::where('email', 'user@example.com')->first()
```

2. Check password is hashed:
```bash
php artisan tinker
>>> $user = App\Models\User::find(1)
>>> Hash::check('password', $user->password)
```

3. Verify user is active:
```bash
php artisan tinker
>>> $user = App\Models\User::find(1)
>>> $user->is_active
```

4. Check authentication guard:
```php
// config/auth.php
'guards' => [
    'sanctum' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
]
```

---

### Issue: Token Expired

**Problem:** API requests return 401 with "Unauthenticated" message.

**Solutions:**

1. Check token expiration in `.env`:
```env
SANCTUM_EXPIRATION=525600
```

2. Create new token:
```bash
php artisan tinker
>>> tenancy()->initialize(App\Models\Tenant::first())
>>> $user = App\Models\User::find(1)
>>> $token = $user->createToken('api-token')
>>> $token->plainTextToken
```

3. Use new token in Authorization header:
```
Authorization: Bearer {new_token}
```

---

### Issue: CORS Errors

**Problem:** `Access to XMLHttpRequest blocked by CORS policy`

**Solutions:**

1. Check CORS configuration:
```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['*'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

2. Update allowed origins:
```php
'allowed_origins' => [
    'http://localhost:3000',
    'https://yourdomain.com',
],
```

3. Clear config cache:
```bash
php artisan config:clear
```

---

## Permission & Authorization Issues

### Issue: Permission Denied (403)

**Problem:** User gets 403 Forbidden error when accessing endpoint.

**Solutions:**

1. Check user has required permission:
```bash
php artisan tinker
>>> tenancy()->initialize(App\Models\Tenant::first())
>>> $user = App\Models\User::find(1)
>>> $user->can('create-users')
```

2. Assign permission to user:
```bash
php artisan tinker
>>> $user = App\Models\User::find(1)
>>> $user->givePermissionTo('create-users')
```

3. Assign role to user:
```bash
php artisan tinker
>>> $user = App\Models\User::find(1)
>>> $user->assignRole('admin')
```

---

## Database Issues

### Issue: Migration Fails

**Problem:** `SQLSTATE[42S01]: Table or view already exists`

**Solutions:**

1. Check if table exists:
```bash
php artisan migrate:status
```

2. Rollback migrations:
```bash
php artisan migrate:rollback
```

3. Reset database:
```bash
php artisan migrate:reset
```

4. Fresh migration:
```bash
php artisan migrate:fresh
```

---

### Issue: Seeding Fails

**Problem:** `Class not found` or seeding errors.

**Solutions:**

1. Check seeder file exists:
```bash
ls database/seeders/
```

2. Run specific seeder:
```bash
php artisan db:seed --class=UserTableSeeder
```

3. Refresh database with seeding:
```bash
php artisan migrate:fresh --seed
```

4. Seed all tenants:
```bash
php artisan tenants:migrate-seed
```

---

## File Upload Issues

### Issue: File Upload Fails

**Problem:** `The file field is required` or upload returns 422.

**Solutions:**

1. Check file size limit in `.env`:
```env
UPLOAD_MAX_FILESIZE=50M
POST_MAX_SIZE=50M
```

2. Update PHP configuration:
```ini
; php.ini
upload_max_filesize = 50M
post_max_size = 50M
```

3. Check storage directory permissions:
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

4. Create storage symlink:
```bash
php artisan storage:link
```

---

### Issue: File Not Found After Upload

**Problem:** Uploaded file returns 404 when accessed.

**Solutions:**

1. Verify storage symlink exists:
```bash
ls -la public/storage
```

2. Create symlink if missing:
```bash
php artisan storage:link
```

3. Check file path in database:
```bash
php artisan tinker
>>> tenancy()->initialize(App\Models\Tenant::first())
>>> $media = App\Models\Media::find(1)
>>> $media->path
```

4. Verify file exists:
```bash
ls storage/app/public/{path}
```

---

## Performance Issues

### Issue: Slow API Responses

**Problem:** API endpoints take too long to respond.

**Solutions:**

1. Enable query logging:
```php
// In controller
DB::enableQueryLog();
// ... your code ...
dd(DB::getQueryLog());
```

2. Add database indexes:
```php
// In migration
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('created_at');
});
```

3. Use eager loading:
```php
// Bad
$users = User::all();
foreach ($users as $user) {
    echo $user->roles; // N+1 query problem
}

// Good
$users = User::with('roles')->get();
```

4. Cache frequently accessed data:
```php
$settings = Cache::remember('settings', 3600, function () {
    return Setting::all();
});
```

---

### Issue: High Memory Usage

**Problem:** `Allowed memory size exhausted`

**Solutions:**

1. Increase PHP memory limit:
```php
// In .env or php.ini
MEMORY_LIMIT=512M
```

2. Update php.ini:
```ini
memory_limit = 512M
```

3. Optimize queries:
```php
// Use pagination instead of loading all records
$users = User::paginate(15);

// Use chunking for large datasets
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

---

## Cache Issues

### Issue: Cache Not Working

**Problem:** Settings or data not being cached.

**Solutions:**

1. Check cache driver in `.env`:
```env
CACHE_DRIVER=file
```

2. Clear cache:
```bash
php artisan cache:clear
```

3. Verify cache directory permissions:
```bash
chmod -R 755 bootstrap/cache/
```

4. Test cache manually:
```bash
php artisan tinker
>>> Cache::put('test', 'value', 3600)
>>> Cache::get('test')
```

---

## Queue Issues

### Issue: Jobs Not Processing

**Problem:** Queued jobs are not being executed.

**Solutions:**

1. Check queue driver in `.env`:
```env
QUEUE_CONNECTION=database
```

2. Create queue table:
```bash
php artisan queue:table
php artisan migrate
```

3. Start queue worker:
```bash
php artisan queue:work
```

4. Check failed jobs:
```bash
php artisan queue:failed
```

5. Retry failed jobs:
```bash
php artisan queue:retry all
```

---

## Subscription Issues

### Issue: Subscription Not Found

**Problem:** Subscription queries return empty results.

**Solutions:**

1. Verify subscription exists:
```bash
php artisan tinker
>>> App\Models\Subscription::all()
```

2. Check subscription status:
```bash
php artisan tinker
>>> $subscription = App\Models\Subscription::first()
>>> $subscription->status
```

3. Check subscription expiry:
```bash
php artisan tinker
>>> $subscription = App\Models\Subscription::first()
>>> $subscription->ends_at
```

4. Run subscription check:
```bash
php artisan subscriptions:check-expired
```

---

## Real-Time Features Issues

### Issue: Reverb Not Working

**Problem:** WebSocket connections fail or real-time updates don't work.

**Solutions:**

1. Check Reverb configuration:
```env
REVERB_APP_ID=1080194
REVERB_APP_KEY=bae3160ce349d284eace
REVERB_APP_SECRET=976e5b64127df42af8b6
REVERB_HOST=127.0.0.1
REVERB_PORT=9000
```

2. Start Reverb server:
```bash
php artisan reverb:start
```

3. Check WebSocket connection:
```bash
# In browser console
const ws = new WebSocket('ws://localhost:9000');
ws.onopen = () => console.log('Connected');
ws.onerror = (e) => console.error('Error', e);
```

4. Verify firewall allows port 9000:
```bash
# macOS
lsof -i :9000

# Linux
sudo netstat -tlnp | grep 9000
```

---

## Email Issues

### Issue: Emails Not Sending

**Problem:** Notifications or emails are not being sent.

**Solutions:**

1. Check mail driver in `.env`:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

2. Test mail configuration:
```bash
php artisan tinker
>>> Mail::raw('Test email', function ($message) {
    $message->to('test@example.com');
});
```

3. Check queue is processing:
```bash
php artisan queue:work
```

4. Verify email template exists:
```bash
ls resources/views/emails/
```

---

## Debugging Tips

### Enable Debug Mode

```env
APP_DEBUG=true
APP_ENV=local
```

### Use Tinker for Testing

```bash
php artisan tinker

# Initialize tenant
>>> $tenant = App\Models\Tenant::first()
>>> tenancy()->initialize($tenant)

# Test queries
>>> App\Models\User::all()

# Test relationships
>>> $user = App\Models\User::find(1)
>>> $user->roles

# Test helpers
>>> setting('general.site_name')
```

### Check Logs

```bash
# View latest logs
tail -f storage/logs/laravel.log

# Search logs
grep "error" storage/logs/laravel.log
```

### Use Laravel Debugbar

```bash
composer require barryvdh/laravel-debugbar --dev
```

---

## Getting Help

If you can't find a solution:

1. Check the [API Reference](/guide/api-reference)
2. Review [Configuration](/guide/configuration)
3. Check [Authentication](/guide/authentication) guide
4. Check [Multi-Tenancy](/guide/multitenancy/) guide
5. Open an issue on GitHub
6. Contact support at support@example.com

---

## See Also

- [Installation](/guide/installation) — Setup guide
- [Configuration](/guide/configuration) — Configuration options
- [Error Handling](/guide/error-handling) — Error responses
- [Multi-Tenancy](/guide/multitenancy/) — Multi-tenancy guide
