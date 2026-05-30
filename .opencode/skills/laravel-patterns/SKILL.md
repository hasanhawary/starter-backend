---
name: laravel-patterns
description: Use when creating or modifying traits, Pipeline filters, query scopes, backed enums, custom exceptions, or global helpers. Covers the specific Laravel implementation patterns including HasDeleteMethods, Pipeline filters, EnumMethods, and helper functions.
---

# Laravel Patterns

## Philosophy
This codebase relies heavily on **traits** for reusable cross-cutting behavior, the **Pipeline pattern** for composable query filtering, **custom exceptions** with JSON rendering, **backed enums** for type-safe constants, and a rich **global helpers** file. Custom packages (`hasanhawary/*`) provide specialized functionality (media, permissions, exports, reports, lookups).

## Rules
- Reusable behavior goes in traits at `app/Trait/Global/`
- Query filtering uses `Illuminate\Pipeline\Pipeline` with filter classes
- Each filter class has a `handle($query, Closure $next)` method
- Scopes use traits (not static methods on models)
- Enums are backed (string or int) and use `HasanHawary\LookupManager\Trait\EnumMethods`
- Custom exceptions extend generic PHP `\Exception` with specific HTTP status codes
- All exceptions render as JSON for API requests via `bootstrap/app.php`
- Global helpers are in `app/Helpers/App.php` loaded via Composer autoload files
- Helper classes like `DelimiterParamValue` provide typed parameter values for notifications
- Use `when()` helper for conditional execution instead of if/else

## Naming Conventions
- Traits: `Has{Behavior}` or `{Purpose}` (e.g., `HasDeleteMethods`, `CreatedByObserver`)
- Filters: `{Domain}{Concern}Filter` (e.g., `ActiveFilter`, `UserFilter`, `DateFilter`)
- Scopes: `{Model}Scopes` (e.g., `UserScopes`, `ExportFileScopes`)
- Enums: `{Name}Enum` with TitleCase cases (e.g., `OtpTypeEnum`, `UserGenderEnum`)
- Exceptions: `{Description}Exception`
- Helpers: `camelCase` functions

## Folder Structure
```
app/Trait/Global/           # 9 reusable traits
app/Filters/Global/         # 9 reusable filters
app/Filters/{Domain}/       # Domain-specific filters
app/Scopes/{Domain}/        # Scope traits
app/Enum/Global/            # Shared enums
app/Enum/User/              # Domain enums
app/Exceptions/             # 7 custom exceptions
app/Helpers/                # App.php + DelimiterParamValue.php
app/Rules/                  # 7 custom validation rules
app/Guards/                 # Custom SanctumGuard
app/Tools/{Domain}/         # Export/Report tool definitions
```

## Best Practices
- Traits should be self-contained and use composition
- Filter classes should use `request()` helper directly for query params
- Use `$query->when()` for conditional filter application
- Enums should have all cases as TitleCase
- Use `DelimiterParamValue::plain()`, `::json()`, `::enum()` for typed notification params
- Use `buildDelimiterMessage()` to create packed messages
- Use `transWithParams()` to unpack and translate delimiter messages
- Use `resolveTrans()` for safe translation key resolution

## Anti-Patterns
- Never duplicate filter logic across controllers
- Never use hardcoded status constants — use enums
- Never throw generic exceptions — use specific custom exception classes
- Never use `DB::raw()` with user input
- Never use `request()` inside services

## Real Examples
Delete methods trait:
```php
// app/Trait/Global/HasDeleteMethods.php
trait HasDeleteMethods
{
    public string $model;
    protected array $deleteGuards = [];
    protected array $beforeDeleteCallbacks = [];

    public function __construct()
    {
        parent::__construct();
        $this->model = User::class;
        $this->beforeDelete('force', fn (User $user) => Media::delete($user->avatar));
    }

    public function destroy(): JsonResponse
    {
        return $this->handle('delete');
    }

    private function handle(string $action): JsonResponse
    {
        $ids = $this->resolveDeleteIds();
        $query = $this->buildDeleteQuery($action, $ids);
        $models = $query->get();
        foreach ($models as $model) {
            if ($this->useDeletePolicy) {
                $this->applyDeleteAuthorize($action, $model);
            }
            $this->executeDelete($model, $action);
        }
        return successResponse(msg: __('api.deleted_success'));
    }
}
```

Pipeline filter:
```php
// app/Filters/Global/ActiveFilter.php
class ActiveFilter
{
    public function handle($query, Closure $next)
    {
        if (request()->filled('is_active')) {
            $query->where('is_active', request()->boolean('is_active'));
        }
        return $next($query);
    }
}
```

Backed enum with EnumMethods:
```php
enum UserGenderEnum: string
{
    use EnumMethods;

    case Male = 'male';
    case Female = 'female';
}
```

## AI Instructions
When implementing patterns:
1. Create traits in `app/Trait/Global/` for reusable behavior
2. Create filter classes in `app/Filters/` for query composition
3. Use Pipeline to chain filters in list endpoints
4. Create backed enums with `EnumMethods` trait
5. Create specific exception classes in `app/Exceptions/`
6. Add helpers to `app/Helpers/App.php`
7. Use `when()` helper for conditional execution
