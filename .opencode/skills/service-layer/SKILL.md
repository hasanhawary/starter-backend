---
name: service-layer
description: Use when creating or modifying service classes. Covers service construction, dependency injection, transaction handling, Form Request acceptance, notification dispatching, and the boundary between controllers and services.
---

# Service Layer

## Philosophy
Controllers should be thin — they receive requests, authorize, delegate to services, and return responses. All complex business logic (transactions, relationship syncing, notification dispatching, multi-step operations) belongs in Service classes. Services are injected via constructor and contain no HTTP concerns. Simple reads (index, show) may stay in the controller.

## Rules
- Services are plain PHP classes in `app/Services/{Domain}/`
- Services are injected via constructor promotion: `public function __construct(private readonly UserService $userService)`
- Services receive Form Request objects, not raw request data
- Services return Eloquent models, not HTTP responses
- Database transactions wrap multi-step operations: `DB::transaction()`
- Notifications are dispatched after commit: `DB::afterCommit()`
- Services contain private helper methods for sub-operations
- Auth services extend `BaseAuthService` which provides `setModel()` and `setGuard()`
- No service should ever call `response()` or access `request()` directly

## Naming Conventions
- Service classes: `{Domain}Service.php` (e.g., `UserService.php`, `LoginService.php`, `OTPService.php`)
- Methods: `store()`, `update()`, `{action}()` — descriptive verb names
- Private helpers: descriptive names like `syncRelations()`, `sendCredentials()`

## Folder Structure
```
app/Services/
  Auth/
    BaseAuthService.php      # Abstract base with setModel, setGuard, resolveUser
    LoginService.php          # Login attempt, LDAP, token creation
    OTPService.php            # Send, verify, check OTP
    ResetPasswordService.php  # Reset password after OTP
    ThrottleService.php       # Rate limiting
  Global/
    EncryptionService.php    # AES-256-CBC encrypt/decrypt
    NotificationService.php  # Multi-channel notification dispatch
    QueryHelper.php           # Static JSON search helper
    SettingService.php        # Cached settings CRUD with env sync
  User/
    UserService.php           # User create/update/relations/credentials
Modules/Export/App/Services/
  ExportFileService.php       # Export lifecycle management
  ExportRegistry.php         # Export class resolution
```

## Best Practices
- One service per domain concept (Auth, User, Setting, etc.)
- Keep methods small and focused
- Use `DB::transaction()` for any write that touches multiple tables
- Use `DB::afterCommit()` for notifications dispatched inside transactions
- Return the model instance from `store()` and `update()` — let the controller wrap it in a resource
- Private methods handle internal concerns (syncing, sending, resolving)

## Anti-Patterns
- Never return `JsonResponse` from a service
- Never use `request()->input()` in a service — pass data via method parameters
- Never instantiate services with `new` — always use dependency injection
- Never put validation logic in services — use Form Requests
- Never call `Gate::authorize()` in services — authorization is the controller's job

## Real Examples
```php
// app/Services/User/UserService.php
class UserService
{
    public function store(UserRequest $request): User
    {
        return DB::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $this->syncRelations($user, $request);
            DB::afterCommit(fn () => $this->sendCredentials($user, $request));
            return $user->refresh();
        });
    }

    public function update(User $user, UserRequest $request): User
    {
        return DB::transaction(function () use ($user, $request) {
            $user->update($request->validated());
            $this->syncRelations($user, $request);
            DB::afterCommit(fn () => $this->sendCredentials($user->refresh(), $request, isCreate: false));
            return $user->refresh();
        });
    }

    private function syncRelations(User $user, UserRequest $request): void
    {
        when($request->filled('roles'), static fn () => $user->syncRoles(Role::whereId($request->roles)->pluck('name')));
        when($request->filled('permissions'), static fn () => $user->syncPermissions($request->permissions));
    }

    public function sendCredentials(User $user, UserRequest $request, bool $isCreate = true): void
    {
        if (! $isCreate && ! ($user->isDirty('email') || $user->isDirty('password'))) {
            return;
        }
        // ... notification dispatch
    }
}
```

```php
// app/Services/Auth/LoginService.php
class LoginService extends BaseAuthService
{
    public function __construct(protected OTPService $otpService) {}

    public function attempt(?array $data): array
    {
        $user = config('project.ldap.active')
            ? $this->attemptLdapLogin($data)
            : $this->attemptDefaultLogin($data);

        if (! $user->is_active) {
            throw new InActiveUserException(__('api.account_not_active'));
        }

        if (shouldVerifyOtp()) {
            $this->verifyOtp($data);
        }

        $this->setLastLogin($user);

        return [
            'user' => $user,
            'token' => $this->createUserToken($user, $data['meta'] ?? []),
        ];
    }
}
```

## AI Instructions
When creating a new service:
1. Place it in `app/Services/{Domain}/{Name}Service.php`
2. Inject via constructor promotion in the controller
3. Accept Form Request objects as parameters
4. Return model instances, not responses
5. Wrap multi-step writes in `DB::transaction()`
6. Dispatch notifications with `DB::afterCommit()`
7. Keep private helpers for sub-operations
