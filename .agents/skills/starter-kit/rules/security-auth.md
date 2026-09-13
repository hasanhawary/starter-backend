# Security And Authorization

Use this rule when implementing authentication, Sanctum tokens, OTP, LDAP, policies, permissions, rate limiting, sensitive data handling, uploads, or exception security.

## Authentication

- Sanctum is the API token layer.
- Logout revokes tokens through the current authenticated user/token flow.
- OTP may be required depending on `shouldVerifyOtp()` and `config/project.php` settings.
- LDAP login may be enabled by project config; do not remove fallback/default login behavior without explicit requirement.
- Rate-limit login and sensitive endpoints through existing middleware/services such as `ThrottleService`.

## Authorization Patterns

- Use `PermissionMiddleware` for simple action-level permissions like create/update on data-entry CRUD.
- Use Policies when ownership, root-user protection, current-user protection, or resource-specific checks are needed, and call `Gate::authorize()` as the **first line of the action** that needs it — the class for collection and create checks (`viewAny`, `create`), the route-bound model for record checks.
- Use middleware only when the rule is genuinely flat: a bare action permission with no model and no context (`PermissionMiddleware::using('create-country')`), or an action supplied by a shared trait, which has no body of yours to hold the call. A `middleware()` block with one `can:` entry per action is the shape to avoid.
- Avoid raw `$user->can()` controller branching when a Policy expresses the rule.
- A rule that can only be resolved from the request payload — a workflow transition's eligibility — belongs inside the collaborator that owns the decision (the status Context), so every caller is gated and none can skip it.
- Protect root users from modification/deletion and prevent self-deletion where user policy rules require it.
- Do not place project authorization in Form Request `authorize()` unless sibling code does.

## Policy Template

```php
class AdminPolicy
{
    use HandlesAuthorization;

    public function view(Authenticatable $user, ?Admin $model = null): bool
    {
        return $this->canAny($user, $model, [
            'view-all-admin',
            'view-own-admin',
        ]);
    }

    public function update(Authenticatable $user, Admin $model): bool
    {
        if (! $user->can('update-admin')) {
            return false;
        }

        if ($this->isProtectedUser($model, $user)) {
            return false;
        }

        return $this->ownsOrAll($user, $model);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    protected function ownsOrAll(Authenticatable $user, ?Admin $model): bool
    {
        return ! $model
            || $user->can('view-all-admin')
            || $model->created_by === $user->id;
    }
}
```

## Sensitive Data

- Never expose passwords, tokens, secrets, OTPs, private keys, or raw encrypted values.
- Use `$hidden` for sensitive model fields.
- Never log passwords, OTPs, tokens, or full request bodies containing secrets.
- Use `config()` for config values; use `env()` only inside config files.
- `.env` must not be committed; `.env.example` can be updated safely.
- `APP_DEBUG` must be false in production.

## Uploads

- Validate MIME, extension, image rules, size, and total size when relevant.
- Use media-manager helpers for storage/replacement if the feature stores media.
- Generate controlled storage paths; do not trust original filenames as final names.

## SQL And Mass Assignment

- Use Eloquent/query builder parameter binding.
- Do not use raw SQL with user input.
- If raw SQL is unavoidable, bind parameters.
- Define `$fillable` explicitly.
- Never include privileged/sensitive fields in `$fillable` unless the feature intentionally writes them.

## Exceptions

- Use specific exception classes for domain failures.
- Render API exceptions as JSON through existing exception handling.
- User-facing messages must be translated.
- Log system failures with context, not sensitive data.
