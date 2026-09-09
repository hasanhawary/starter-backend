# Canonical Eloquent Model Example

Use this structure for a normal permission-managed CRUD model with translated `name` and `description`, factory support, activity logging, creator tracking, soft deletion, one enum cast, and relations. Remove a feature and its imports, properties, and section only when the target model does not need that feature. Do not leave empty sections.

```php
<?php

namespace App\Models;

use App\Enum\Example\ExampleStatusEnum;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Database\Factories\ExampleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Example extends Model
{
    /** @use HasFactory<ExampleFactory> */
    use CreatedByObserver, HasFactory, HasTranslations, LogsActivityOptions, SoftDeletes;

    public bool $inPermission = true;

    public array $basicOperations = ['create', 'read', 'update', 'delete'];

    public array $specialOperations = ['restore', 'force-delete'];

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'status',
        'parent_id',
        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts && Set Custom Attributes
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'status' => ExampleStatusEnum::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChildModel::class);
    }

    public function syncChildren(array $children = []): void
    {
        if (empty($children)) {
            return;
        }

        $this->children()->delete();
        $this->children()->createMany($children);
    }

    /**
     * @return array<string, string>
     */
    public function preventDeleteRelations(): array
    {
        return ['children' => 'not_allowed_to_delete_linked'];
    }
}
```

`syncChildren()` follows the live convention of `syncFiles()` and `syncParticipants()` in `app/Models/Cause.php`: a named method on the model that owns the relation, taking already-validated data, guarding empty input, and returning `void`. It does not open a transaction — the caller owns that boundary, wrapping the write in `DB::transaction()` in the controller for one or two lines, or in a domain service for a larger operation. Include the section only when the model actually has a relation to synchronize.

`LogsActivityOptions` is intentional and required for a normal persisted business model. It already composes Spatie's `LogsActivity` and provides the repository's default `getActivitylogOptions()` implementation, so do not repeat that method in the model.

If the inspected fields contain values that must not enter the audit log, keep the shared behavior and declare only the exclusions:

```php
protected array $logExceptAttributes = [
    'password',
    'otp_data',
    'remember_token',
];
```

Use a model-local `getActivitylogOptions()` only for a real custom logging contract that cannot be represented by `logExceptAttributes`; in that case, apply the backend activity-logging skill and preserve all required defaults explicitly.

The corresponding translated columns use the repository's storage convention:

```php
$table->mediumText('name');
$table->mediumText('description')->nullable();
```

The normal Resource representation is:

```php
'translation_name' => $this->name,
'name' => $this->getTranslations('name'),
'translation_description' => $this->description,
'description' => $this->getTranslations('description'),
```
