# Models And Database

Use this rule when creating or modifying models, migrations, relationships, casts, scopes, factories, seeders, media fields, and permission metadata.

## Model: Simple Data Entry Model

Use this shape for CRUD/data-entry models with translatable fields, media, soft delete, and permission metadata.

```php
<?php

namespace App\Models;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Product extends BaseModel
{
    // Keep reusable behavior as traits at the top of the class.
    use HasTranslations, SoftDeletes;

    // Translatable columns are JSON in the database.
    public array $translatable = ['name', 'description'];

    // Permission manager reads these properties when generating permissions.
    public bool $inPermission = true;
    public array $specialOperations = ['force-delete', 'restore', 'toggle-active'];

    // Only client-writable fields go here. Never use $guarded = [].
    protected $fillable = [
        'name',
        'description',
        'image',
        'code',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    public function setImageAttribute($value): void
    {
        // Use media-manager replace/upload so updates clean up old files.
        $path = Media::replace($this->image ?? null)->upload($value, 'products');
        $this->attributes['image'] = $path;
    }

    public function image(): Attribute
    {
        // Resources can expose $this->image as a ready URL.
        return Attribute::make(get: fn ($value) => Media::url($value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }
}
```

## Model: User/Auth-Aware Model

Use this shape for auth/permission-heavy models that need hidden fields, default eager loads, enum casts, activity logs, creator tracking, notifications, and relationships.

```php
<?php

namespace App\Models;

use App\Enum\User\UserGenderEnum;
use App\Scopes\User\UserScopes;
use App\Trait\Global\ApplyNotification;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use ApplyNotification, CreatedByObserver, HasApiTokens, HasRoles, LogsActivityOptions, SoftDeletes, UserScopes;

    protected string $guard_name = 'api';

    public bool $inPermission = true;
    public array $basicOperations = ['create', 'update', 'delete'];
    public array $specialOperations = ['view-all', 'view-own', 'restore', 'force-delete', 'toggle-active'];

    protected $fillable = [
        'name', 'email', 'phone_code_id', 'phone', 'avatar', 'gender', 'password',
        'is_active', 'last_login', 'created_by',
    ];

    // Hide sensitive fields from resources and accidental serialization.
    protected $hidden = ['password', 'remember_token'];

    // Use default eager loading only for relations always needed by resources.
    protected $with = ['phoneCode'];

    protected $casts = [
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'gender' => UserGenderEnum::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity logs
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly($this->fillable);
    }

    /*
     |--------------------------------------------------------------------------
     | Casts && Set Custom Attributes
     |--------------------------------------------------------------------------
     */
    public function avatar(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => Media::url($value)
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: static fn ($value) => bcrypt($value),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'created_by');
    }

    public function phoneCode(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_code_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }
}
```

## Model Rules

- Extend `BaseModel` unless a vendor/auth model base is required.
- Use `$fillable`; never use `$guarded = []`.
- Use `$hidden` for sensitive fields.
- Use `$casts` for booleans, JSON/arrays, dates/datetimes, decimals, passwords, and enums.
- Use `Attribute` objects for accessors/mutators.
- Use relation return types like `BelongsTo`, `HasMany`, `BelongsToMany`, `MorphMany`.
- Use `SoftDeletes` when the feature follows delete/restore/force-delete or audit patterns.
- Use `CreatedByObserver` when the existing feature tracks `created_by`.
- Use `LogsActivityOptions` for auditable models.
- Use `HasTranslations` and `$translatable` for JSON multi-language fields.
- Permission-managed models define `$inPermission`, `$basicOperations`, and `$specialOperations` as needed.
- Use scope traits under `app/Scopes/{Domain}/` for reusable ownership/filter scopes.

## Migration Template

```php
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->mediumText('name'); // JSON translations are stored as text/json-compatible payloads.
            $table->mediumText('description')->nullable();
            $table->string('code')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

## Migration Rules

- Use anonymous migration classes.
- Use one concern per migration.
- Add indexes for columns used in filters, sorts, searches, joins, and foreign keys.
- Use `foreignId()->constrained()` with explicit delete behavior.
- Use `nullable()->constrained()->nullOnDelete()` when records can survive deleted parents.
- Use `cascadeOnDelete()` only when deleting children is correct.
- Add `timestamps()` and `softDeletes()` when the model supports audit/restore.
- Keep `down()` reversible unless intentionally irreversible.
- Do not modify migrations that may already have run in production; create a new forward migration.
- Do not mix DDL and DML in one migration unless sibling migrations do.

## Seeder And Factory Rules

- Factories live in `database/factories/` or module factory folders.
- Seeders live in `database/seeders/`, brand seeders, or module seeder folders.
- Use `firstOrCreate()` or `updateOrCreate()` for idempotent seeders.
- Use factories for generated test/demo data.
- Mirror important DB defaults in model `$attributes` when needed.
