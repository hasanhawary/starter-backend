# API Standards Skill

## Purpose
Define the API response format, routing conventions, resource transformation, pagination, and filtering patterns.

## Philosophy
All API responses follow a consistent JSON envelope format using global helper functions (`successResponse()` and `failResponse()`). Resources transform models for the API layer. Filtering uses the Pipeline pattern for composable query filters. Pagination is handled via `wrapPaginate()` helper. Routes are organized by domain prefix.

## Rules
- ALL responses MUST use `successResponse()` or `failResponse()` — never raw `response()->json()`
- Success response format: `{ status: true, code: 200, message: "...", data: {...} }`
- Failure response format: `{ status: false, code: 400+, message: "...", data: {...} }`
- All resources MUST extend `JsonResource`
- Use `whenLoaded()` for conditional relations in resources
- Include both raw and display fields for translatable/enum values (e.g., `name` + `display_name`)
- List endpoints MUST use Pipeline with composable filter classes
- Pagination via `wrapPaginate($query, ResourceClass::class)`
- Routes are prefixed by domain: `/users`, `/roles`, `/export-log`, etc.
- Delete/restore/force-delete are separate route endpoints, NOT part of apiResource
- Toggle-active is a separate PUT endpoint
- Auth routes (login, OTP, reset-password) are public (no middleware)
- Protected routes use `auth:sanctum` middleware group

## Naming Conventions
- Route prefixes: `kebab-case` (e.g., `toggle-active`, `force-delete`, `activity-logs`)
- Resource classes: `PascalCaseResource`
- Response fields: `snake_case`
- Display fields: `display_{field_name}` (e.g., `display_name`, `display_status`, `display_gender`)

## Folder Structure
```
routes/
  api.php                    # Main API routes
  channels.php               # Broadcasting channels
resources/js/echo.js         # Laravel Echo setup
```

## Best Practices
- Use `PageRequest` for paginated list endpoints
- Use domain-specific Form Requests for create/update endpoints
- Use `$request->validated()` — never `$request->all()`
- Authorize BEFORE processing: `Gate::authorize('create', User::class)`
- Single-action controllers use `__invoke()`
- Resources should provide both raw and translated/display values
- Use `successResponse(new Resource($model), __('api.created_success'))`

## Anti-Patterns
- Never return unstructured JSON
- Never mix response formats
- Never put pagination logic in controllers
- Never skip authorization on protected endpoints
- Never access `$request->all()` — always use validated()

## Real Examples
Response helpers:
```php
// app/Helpers/App.php
function successResponse($data = [], $msg = null, $code = 200): JsonResponse
{
    return response()->json([
        'status' => true,
        'code' => $code,
        'message' => $msg ?? __('api.success'),
        'data' => $data,
    ], $code);
}

function failResponse($msg = 'fail', $data = [], $code = 400): JsonResponse
{
    return response()->json([
        'status' => false,
        'code' => $code,
        'message' => $msg,
        'data' => $data,
    ], $code);
}
```

Pipeline filtering:
```php
// In controller index()
$query = app(Pipeline::class)
    ->send(User::with('roles')->related())
    ->through([UserFilter::class, ActiveFilter::class, TrashedFilter::class, OrderByFilter::class])
    ->thenReturn();
return successResponse(wrapPaginate($query, UserResource::class));
```

Resource with display fields:
```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gender' => $this->gender,
            'display_gender' => UserGenderEnum::resolve($this->gender),
            'roles' => $this->whenLoaded('roles', fn () => BasicResource::collection($this->roles), []),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator)),
        ];
    }
}
```

Route organization:
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('users')->group(function () {
        Route::delete('force-delete', [UserController::class, 'forceDelete']);
        Route::delete('delete', [UserController::class, 'destroy']);
        Route::post('restore', [UserController::class, 'restore']);
        Route::put('toggle-active', [UserController::class, 'toggleActive']);
        Route::apiResource('/', UserController::class)->parameters(['' => 'user'])->except(['destroy']);
    });
});
```

## AI Instructions
When building new API endpoints:
1. Create a Form Request for validation
2. Create a Resource for response transformation
3. Use Pipeline filters for list endpoints
4. Use `successResponse()` / `failResponse()` for all responses
5. Include `display_` fields for enums and translatable values
6. Authorize before processing
7. Follow the route prefix convention
