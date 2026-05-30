---
name: security
description: Use when implementing authentication, authorization, validation, Form Requests, policies, rate limiting, or exception handling. Covers Sanctum auth, OTP verification, Gate/Policy authorization, BaseFormRequest, custom rules, and ThrottleService.
---

# Security

## Philosophy
Security is multi-layered: Sanctum token auth, OTP verification, LDAP support, policy-based authorization, Form Request validation, rate limiting via ThrottleService, and password encryption. Authorization uses either `Gate::authorize()` in controllers or Policy classes with ownership checks. Protected resources (root users, self-deletion) are enforced at the policy level.

## Rules
- Authentication uses Laravel Sanctum v4 with custom `SanctumGuard` for token expiration
- OTP verification is conditional based on `config('project.auth.login_methods.otp')`
- Password encryption supports incoming base64-encoded passwords via config toggle
- Authorization uses `Gate::authorize()` or Policy classes — NEVER raw permission checks in controllers
- Policies check ownership via `ownsOrAll()` — users can manage their own records or all records
- Policies protect root users and current user from modification/deletion
- Form Requests extend `BaseFormRequest` which auto-converts empty strings to null
- Validation rules use array notation: `['required', 'email', 'max:255']`
- Custom validation rules in `app/Rules/` for complex validation (StrongPassword, CheckSamePassword, ValidLength, TranslatableRequired)
- Rate limiting via `ThrottleService` on login endpoint
- Sensitive response data (roles, permissions) is AES-256-CBC encrypted via `EncryptionService`
- All exceptions render as JSON for API requests via `bootstrap/app.php` exception handlers
- `LanguageMiddleware` sets locale on every API request

## Naming Conventions
- Policies: `{Model}Policy.php` in `Policies/{Domain}/`
- Exceptions: `{Description}Exception.php` in `app/Exceptions/`
- Rules: `{ValidationName}.php` in `app/Rules/`
- Middleware: `{Purpose}Middleware.php` in `app/Http/Middleware/`

## Folder Structure
```
app/
  Exceptions/           # Custom exceptions (7 classes)
  Guards/               # Custom auth guards (SanctumGuard)
  Http/Middleware/      # HTTP middleware (LanguageMiddleware)
  Policies/{Domain}/    # Authorization policies
  Rules/                # Custom validation rules
  Services/Auth/        # Auth services (Login, OTP, Throttle, ResetPassword)
```

## Best Practices
- Always use Form Requests for validation — never inline `validate()`
- Always authorize before processing data
- Use `__()` or `trans()` for all user-facing messages (supports Arabic/English)
- Use `$request->validated()` — never `$request->all()`
- Password fields use `confirmed` + `min:8` + `StrongPassword` rule
- Use `Rule::unique()->ignore($id)->withoutTrashed()` for update uniqueness
- Protect root users from self-deletion and modification
- Use `HasMiddleware` interface for middleware-based permission on specific actions

## Anti-Patterns
- Never skip authorization on write operations
- Never trust client-side input without validation
- Never store raw passwords — always use `hashed` cast
- Never expose sensitive data without encryption
- Never use raw SQL with user input
- Never commit `.env` files or secrets

## Real Examples
Policy with ownership:
```php
// app/Policies/User/UserPolicy.php
class UserPolicy
{
    public function update(Authenticatable $user, User $model): bool
    {
        if (! $user->can('update-user')) {
            return false;
        }
        if ($this->isProtectedUser($model, $user)) {
            return false;
        }
        return $this->ownsOrAll($user, $model);
    }

    protected function ownsOrAll(Authenticatable $user, ?User $model): bool
    {
        return ! $model
            || $user->can('view-all-user')
            || $model->created_by === $user->id;
    }

    protected function isProtectedUser(User $model, Authenticatable $user): bool
    {
        return in_array($model->id, [...rootUsers(), $user->id], true);
    }
}
```

Base Form Request:
```php
abstract class BaseFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->replace(collect($this->all())
            ->map(fn ($value) => resolveEmptyToNull($value))
            ->toArray());
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        $firstMessage = collect($errors)->flatten()->first();
        throw new HttpResponseException(response()->json([
            'message' => $firstMessage,
            'errors' => $errors,
        ], 422));
    }
}
```

Exception rendering:
```php
// bootstrap/app.php
$exceptions->render(function (NotFoundHttpException $e, Request $request) {
    if ($request->is('api/*')) {
        return failResponse(__('api.record_not_found'), code: $e->getStatusCode());
    }
});
```

## AI Instructions
When implementing security:
1. Create a Form Request for every endpoint that accepts input
2. Extend `BaseFormRequest` for the auto null-conversion and error formatting
3. Use `Gate::authorize()` or Policies for authorization
4. Protect root users and self-modification in policies
5. Use `$request->validated()` for data access
6. Render all exceptions as JSON for API routes
7. Use `ThrottleService` for rate-limited endpoints
