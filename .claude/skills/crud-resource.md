# Skill: Creating a New API Resource (Full CRUD Slice)

Use this skill whenever adding a new domain resource. Follow the order below exactly.

---

## Checklist

- [ ] Enum(s) if column has known value set
- [ ] Migration
- [ ] Model
- [ ] Factory
- [ ] FormRequest (single file handles store + update via `$this->isMethod`)
- [ ] Filters
- [ ] Resource
- [ ] Service (if multi-step logic)
- [ ] Controller
- [ ] Routes (admin.php and/or landing.php)

---

## 1. Enum

`app/Enum/{Domain}/XxxStatusEnum.php`

```php
namespace App\Enum\{Domain};

use HasanHawary\LookupManager\Trait\EnumMethods;

enum XxxStatusEnum: string
{
    use EnumMethods;

    case Active   = 'active';
    case Archived = 'archived';

    public static function keyName(): string
    {
        return 'xxx_status'; // used by lookup-manager for frontend
    }
}
```

> Migration column is always `string`, never `enum`.

---

## 2. Migration

`database/migrations/YYYY_MM_DD_HHMMSS_create_xxxs_table.php`

```php
Schema::create('xxxs', function (Blueprint $table) {
    $table->id();
    $table->foreignIdFor(RelatedModel::class)->constrained()->cascadeOnDelete(); // FK
    $table->mediumText('name');            // translatable — always mediumText
    $table->string('status')->default(XxxStatusEnum::Active->value); // enum as string
    $table->boolean('is_active')->default(true);
    $table->softDeletes();
    $table->timestamps();

    $table->index('status');              // index every filtered/sorted column
    $table->index('is_active');
    $table->index('created_at');
});
```

**Rules:**
- `foreignIdFor()` for every FK — also add `->index()` manually when no constrained()
- `softDeletes()` for resources that have restore/force-delete routes
- Enum columns → `string`, not `enum`
- Always reversible (`down()` → `Schema::dropIfExists`)

---

## 3. Model

`app/Models/Xxx.php`

```php
namespace App\Models;

use App\Enum\{Domain}\XxxStatusEnum;
use App\Trait\Global\LogsActivityOptions;
use App\Trait\Global\CreatedByObserver;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Xxx extends BaseModel
{
    use HasFactory, SoftDeletes, HasTranslations, LogsActivityOptions, CreatedByObserver;

    // Permission-manager flags
    public bool $inPermission = true;
    public array $specialOperations = ['restore', 'force-delete', 'toggle-active'];

    // Translatable columns (stored as JSON)
    public array $translatable = ['name'];

    protected $fillable = [
        'related_model_id',
        'name',
        'image',
        'status',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status'    => XxxStatusEnum::class,
    ];

    // Media getter
    public function image(): Attribute
    {
        return Attribute::make(get: fn($v) => Media::url($v));
    }

    // Media setter (handles replace on update)
    public function setImageAttribute($value): void
    {
        $this->attributes['image'] = Media::replace($this->image ?? null)->upload($value, 'xxxs');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relations
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }
}
```

---

## 4. Factory

`database/factories/XxxFactory.php`

```php
namespace Database\Factories;

use App\Enum\{Domain}\XxxStatusEnum;
use App\Models\Xxx;
use Illuminate\Database\Eloquent\Factories\Factory;

class XxxFactory extends Factory
{
    protected $model = Xxx::class;

    public function definition(): array
    {
        return [
            'name'       => ['en' => $this->faker->words(3, true), 'ar' => $this->faker->words(3, true)],
            'status'     => $this->faker->randomElement(XxxStatusEnum::cases())->value,
            'is_active'  => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function archived(): static
    {
        return $this->state(['status' => XxxStatusEnum::Archived->value]);
    }
}
```

---

## 5. FormRequest

`app/Http/Requests/Admin/{Domain}/XxxRequest.php`

One request class handles both store and update. No `isMethod()` check — ever.

The route model binding does the work: `$this->route('xxx')` returns `null` on store and the model instance on update. Pass it directly to `UniqueCheck`, `->ignore()`, and `TranslatableRequired`'s route param — they all handle null gracefully.

```php
namespace App\Http\Requests\Admin\{Domain};

use App\Enum\{Domain}\XxxStatusEnum;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Admin\{Domain}\XxxResource;
use App\Models\Xxx;
use App\Rules\TranslatableRequired;
use App\Rules\TranslatableNullable;
use App\Rules\UniqueCheck;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;

class XxxRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $xxx = $this->route('xxx'); // null → store, model → update

        return [
            // Required translatable field with unique check
            'name' => [
                'required',
                'array',
                new UniqueCheck(Xxx::class, XxxResource::class, $xxx?->id),
                new TranslatableRequired('xxxs', ['string', 'max:191'], 'xxx'),
            ],

            // Nullable translatable field
            'description' => [
                'sometimes',
                'nullable',
                'array',
                new TranslatableNullable('xxxs', ['string', 'max:500'], 'xxx'),
            ],

            // Enum field
            'status' => ['sometimes', 'nullable', new Enum(XxxStatusEnum::class)],

            // Non-translatable unique field
            'code' => [
                'required',
                Rule::unique('xxxs', 'code')
                    ->withoutTrashed()
                    ->ignore($xxx),
            ],

            // Media field
            'image' => ['sometimes', 'nullable', File::image()->max(5120)],

            // Boolean
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
```

**Rule reference:**

| Scenario | Rule |
|---|---|
| Translatable, required, unique | `UniqueCheck` + `TranslatableRequired` |
| Translatable, required, not unique | `TranslatableRequired` only |
| Translatable, optional | `TranslatableNullable` |
| Non-translatable unique | `Rule::unique()->withoutTrashed()->ignore($xxx)` |
| Enum column | `new Enum(XxxEnum::class)` |
| Media upload | `File::image()->max(N)` or `File::types([...])->max(N)` |

---

## 6. Filters

`app/Filters/Admin/{Domain}/XxxFilter.php`

```php
namespace App\Filters\Admin\{Domain};

use Closure;

class XxxFilter
{
    public function handle($query, Closure $next)
    {
        $query = $next($query);

        $query->when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->whereJsonContains('name->en', request('search'))
                  ->orWhereJsonContains('name->ar', request('search'));
            });
        });

        $query->when(request('status'), fn($q) => $q->where('status', request('status')));

        return $query;
    }
}
```

Reuse global filters — don't duplicate: `ActiveFilter`, `OrderByFilter`, `TrashedFilter`, `DateFilter`.

---

## 7. Resource

`app/Http/Resources/Admin/{Domain}/XxxResource.php`

```php
namespace App\Http\Resources\Admin\{Domain};

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class XxxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'translation_name'=> $this->name,            // current locale
            'name'            => $this->getTranslations('name'), // all locales
            'image'           => $this->image,
            'status'          => $this->status,
            'is_active'       => $this->is_active,
            'created_at'      => $this->created_at,
        ];
    }
}
```

---

## 8. Service (when needed)

`app/Services/{Domain}/XxxService.php`

Only create a Service when there's multi-step logic, side-effects, or cross-model writes. Simple CRUD directly in the controller is fine.

```php
namespace App\Services\{Domain};

use App\Models\Xxx;
use Illuminate\Support\Facades\DB;

class XxxService
{
    public function create(array $data): Xxx
    {
        return DB::transaction(function () use ($data) {
            $xxx = Xxx::create($data);

            // side-effects after commit
            DB::afterCommit(fn() => $xxx->sendNotification([
                'title' => 'xxx_created_title',
                'msg'   => "xxx_created_msg|name={$xxx->name}",
            ], ['notify']));

            return $xxx;
        });
    }
}
```

---

## 9. Controller

`app/Http/Controllers/API/Admin/{Domain}/XxxController.php`

```php
namespace App\Http\Controllers\API\Admin\{Domain};

use App\Filters\Admin\{Domain}\XxxFilter;
use App\Filters\Global\ActiveFilter;
use App\Filters\Global\OrderByFilter;
use App\Filters\Global\TrashedFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Admin\{Domain}\XxxRequest;
use App\Http\Requests\Global\Other\PageRequest;
use App\Http\Resources\Admin\{Domain}\XxxResource;
use App\Models\Xxx;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\HasToggleActiveMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class XxxController extends BaseController implements HasMiddleware
{
    use HasDeleteMethods, HasToggleActiveMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Xxx::class;

        // Register media cleanup before force-delete
        $this->beforeDelete('force', fn(Xxx $xxx) => Media::delete($xxx->getRawOriginal('image')));
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('create-xxx'), only: ['store']),
            new Middleware(PermissionMiddleware::using('update-xxx'), only: ['update']),
        ];
    }

    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Xxx::query())
            ->through([XxxFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(wrapPaginate($query, XxxResource::class));
    }

    public function store(XxxRequest $request): JsonResponse
    {
        $xxx = Xxx::create($request->validated());

        return successResponse(new XxxResource($xxx), __('api.created_success'));
    }

    public function show(Xxx $xxx): JsonResponse
    {
        return successResponse(new XxxResource($xxx));
    }

    public function update(XxxRequest $request, Xxx $xxx): JsonResponse
    {
        $xxx->update($request->validated());

        return successResponse(new XxxResource($xxx->refresh()), __('api.updated_success'));
    }
}
```

> `HasDeleteMethods` provides `destroy()`, `restore()`, `forceDelete()` automatically.
> `HasToggleActiveMethods` provides `toggleActive()` automatically.
> Both traits read IDs from `request('ids')` or `request('id')` or route params.

---

## 10. Routes

In `routes/admin.php` inside the auth middleware group:

```php
Route::prefix('xxxs')->group(function () {
    Route::delete('force-delete', [XxxController::class, 'forceDelete']);
    Route::delete('delete',       [XxxController::class, 'destroy']);
    Route::post('restore',        [XxxController::class, 'restore']);
    Route::put('toggle-active',   [XxxController::class, 'toggleActive']);
    Route::apiResource('/', XxxController::class)
        ->parameters(['' => 'xxx'])
        ->except(['destroy']);
});
```

---

## Namespace Map

| Layer | Namespace pattern |
|---|---|
| Controllers | `App\Http\Controllers\API\{Admin\|Landing\}{Domain}\` |
| Requests | `App\Http\Requests\{Admin\|Global\}{Domain}\` |
| Resources | `App\Http\Resources\{Admin\|Global\}{Domain}\` |
| Filters | `App\Filters\{Admin\|Global\}{Domain}\` |
| Services | `App\Services\{Domain}\` |
| Models | `App\Models\` |
| Enums | `App\Enum\{Domain}\` |
| Scopes | `App\Scopes\{Domain}\` |
