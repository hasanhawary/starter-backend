---
name: database-patterns
description: Use when creating or modifying models, migrations, factories, seeders, scopes, or database-related traits. Covers model conventions, Eloquent casts, soft deletes, translatable fields, ownership scopes, and migration patterns.
---

# Database Patterns

## Philosophy
Models are the data layer and contain relationships, casts, attribute accessors, and permission declarations. Traits provide reusable model behavior (soft delete tracking, audit logging, creator tracking). Migrations use the inline anonymous class pattern. Factories use `$this->faker` and `fake()`. Seeders are brand-aware with separate brand files.

## Rules
- All models extend `BaseModel` (or vendor base classes like `Spatie\Permission\Models\Role`)
- Models MUST declare `$fillable` — never use `$guarded`
- Use Eloquent attribute casts (not manual casting in accessors)
- Use `Attribute` class for computed accessors/mutators
- Use `SoftDeletes` trait for soft-deletable models
- Use `HasTranslations` from spatie/laravel-translatable for JSON translatable fields
- Declare `$translatable` array on models with translatable fields
- Declare `$inPermission = true` and operation arrays for permission-managed models
- Use `protected $with = [...]` for default eager loads (only when always needed)
- Migrations use anonymous classes: `return new class extends Migration`
- Foreign keys use `->constrained()` or `->constrained('table')->nullOnDelete()`
- Always add `->timestamps()` and optionally `->softDeletes()`
- Scopes use traits in `app/Scopes/{Domain}/`
- Factories extend `Factory` with `$model` property
- Seeders use `firstOrCreate()` to avoid duplicates
- Use `CreatedByObserver` trait to auto-track `created_by`
- Use `LogsActivityOptions` trait for audit logging
- Use `HasDeletedBy` trait for tracking who soft-deleted

## Naming Conventions
- Models: PascalCase singular
- Tables: snake_case plural (automatic)
- Foreign key columns: `{relation}_id`
- Pivot tables: `model_model` (Spatie convention)
- Migrations: `{date}_{action}_{table}.php`
- Factories: `{Model}Factory.php`
- Seeders: `{Table}Seeder.php`

## Folder Structure
```
app/Models/                  # All Eloquent models
app/Scopes/{Domain}/        # Scope traits
app/Trait/Global/           # Model behavior traits
database/factories/          # Model factories
database/migrations/         # All migrations
database/seeders/            # App seeders
database/seeders/brands/     # Brand-specific seeders
```

## Best Practices
- Use `$fillable` and explicit casts
- Use PHP 8 constructor promotion where applicable
- Use scope traits for complex queries (especially ownership-based `related()` scope)
- Use `HasOrder` trait for reorderable models
- Cast enum columns to backed enum types
- Cast `password` to `hashed`
- Use `Schema::hasColumn()` checks in toggle traits
- Use `Cache::rememberForever()` for expensive settings queries

## Anti-Patterns
- Never use `$guarded = []` — always use `$fillable`
- Never query in Blade templates
- Never hardcode table names — use `(new Model)->getTable()`
- Never modify migrations that have run in production
- Never mix DDL and DML in one migration

## Real Examples
Model with permissions, casts, traits, and relationships:
```php
class User extends Authenticatable implements LdapAuthenticatable
{
    use ApplyNotification, AuthenticatesWithLdap, CreatedByObserver,
        HasApiTokens, HasFactory, HasRoles, InteractsWithSockets,
        LogsActivityOptions, Notifiable, SoftDeletes, UserScopes;

    protected string $guard_name = 'api';
    public bool $inPermission = true;
    public array $basicOperations = ['create', 'update', 'delete'];
    public array $specialOperations = ['view-all', 'view-own', 'restore', 'force-delete', 'toggle-active'];

    protected $fillable = ['name', 'email', 'phone_code_id', ...];
    protected $hidden = ['password', 'remember_token'];
    protected $with = ['phoneCode'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'gender' => UserGenderEnum::class,
        'otp_data' => 'array',
    ];

    public function avatar(): Attribute
    {
        return Attribute::make(get: static fn ($value) => Media::url($value));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'created_by');
    }
}
```

Ownership scope:
```php
// app/Scopes/User/UserScopes.php
trait UserScopes
{
    public function scopeRelated(Builder $builder): void
    {
        $user = auth()->user();
        if ($user->can('view-all-user')) {
            return;
        }
        if (! $user->can('view-own-user')) {
            $builder->whereRaw('1 = 0');
            return;
        }
        $builder->where('created_by', $user->id);
    }
}
```

## AI Instructions
When creating new models:
1. Use `php artisan make:model` and add factory/seeder
2. Define `$fillable`, `$hidden`, `$casts`, `$with`
3. Add `$inPermission`, `$basicOperations`, `$specialOperations`
4. Use traits for common behavior (CreatedByObserver, SoftDeletes, LogsActivityOptions)
5. Use Attribute class for accessors/mutators
6. Use scope traits for complex queries
7. Create a migration with proper foreign keys and indexes
