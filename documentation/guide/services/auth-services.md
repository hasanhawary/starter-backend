---
title: Authentication Services
description: Login, OTP, password reset, and rate limiting services
---

# Authentication Services

The authentication services handle all login, logout, password recovery, and rate limiting logic.

## LoginService

Handles user and admin authentication with LDAP and OTP support.

```php
// app/Services/Auth/LoginService.php
namespace App\Services\Auth;

class LoginService extends BaseAuthService
{
    public function __construct(protected OTPService $otpService) {}

    public function attempt(?array $data): array
    {
        // LDAP login if enabled, otherwise default login
        $user = config('project.ldap.active')
            ? $this->attemptLdapLogin($data)
            : $this->attemptDefaultLogin($data);

        if (!$user->is_active) {
            throw new InActiveUserException(__('api.account_not_active'));
        }

        // OTP verification if required
        if (shouldVerifyOtp(getModelKey($this->model))) {
            $this->verifyOtp($data);
        }

        $this->setLastLogin($user);

        return [
            'user' => $user,
            'token' => $user->createToken($this->getGuard())->plainTextToken,
        ];
    }
}
```

### Usage

```php
use App\Services\Auth\LoginService;
use App\Services\Auth\OTPService;

$loginService = new LoginService(new OTPService());

// For tenant users
$result = $loginService
    ->setGuard('api')
    ->setModel(\App\Models\Tenant\User::class)
    ->attempt([
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

// For central admins
$result = $loginService
    ->setGuard('admin')
    ->setModel(\App\Models\Central\Admin::class)
    ->attempt([
        'email' => 'admin@example.com',
        'password' => 'password123',
    ]);

// Result
[
    'user' => User instance,
    'token' => 'Bearer token string',
]
```

### LDAP Authentication

When LDAP is enabled (`config('project.ldap.active')`):

```php
protected function attemptLdapLogin(array $data): mixed
{
    $ldapUserModel = config('project.ldap.local') 
        ? OpenLdapUser::class 
        : ActiveDirectoryLdapUser::class;

    $ldapUser = $ldapUserModel::where('mail', $data['email'])->first();

    if (!$ldapUser) {
        // Fallback to database authentication
        return $this->attemptDefaultLogin($data);
    }

    // Authenticate against LDAP
    if (!$ldapUser->auth($data['password'])) {
        throw new InvalidEmailAndPasswordCombinationException();
    }

    // Create or update local user
    return $this->syncLdapUser($ldapUser);
}
```

### Exceptions

| Exception | Description |
|-----------|-------------|
| `InvalidEmailAndPasswordCombinationException` | Wrong credentials |
| `InActiveUserException` | User account is disabled |
| `EmailVerifiedException` | Email not verified |
| `InvalidOtpException` | OTP verification failed |

## OTPService

Generates, sends, and verifies one-time passwords.

```php
// app/Services/Auth/OTPService.php
namespace App\Services\Auth;

class OTPService
{
    public function generate(string $type, array $data): string;
    public function send(Authenticatable $user, string $otp): void;
    public function verify(Authenticatable $user, string $otp): bool;
    public function canResend(Authenticatable $user): bool;
}
```

### Generate OTP

```php
use App\Services\Auth\OTPService;

$otpService = new OTPService();

// Generate OTP for password reset
$otp = $otpService->generate('password_reset', [
    'email' => 'user@example.com',
]);
```

### OTP Configuration

```php
// config/project.php
'otp' => [
    'default' => '1111',      // Fixed OTP for testing
    'length' => 6,            // OTP length
    'type' => 'alpha',        // numeric, alpha, alphanumeric
    'delay' => '30',          // Seconds between sends
    'expires_in' => 10,       // Minutes until expiration
    'max_attempts' => 1,      // Max verification attempts
    'lock_time' => 120,       // Lock time after max attempts
],
```

### OTP Types

| Type | Example |
|------|---------|
| `numeric` | `123456` |
| `alpha` | `ABCDEF` |
| `alphanumeric` | `A1B2C3` |

## ResetPasswordService

Handles password reset via OTP.

```php
// app/Services/Auth/ResetPasswordService.php
namespace App\Services\Auth;

class ResetPasswordService extends BaseAuthService
{
    public function __construct(protected OTPService $otpService) {}

    public function sendOtp(array $data): void;
    public function verifyOtp(array $data): bool;
    public function resetPassword(array $data): void;
}
```

### Password Reset Flow

```php
use App\Services\Auth\ResetPasswordService;
use App\Services\Auth\OTPService;

$resetService = new ResetPasswordService(new OTPService());

// Step 1: Send OTP
$resetService
    ->setGuard('api')
    ->setModel(User::class)
    ->sendOtp(['email' => 'user@example.com']);

// Step 2: Verify OTP
$valid = $resetService->verifyOtp([
    'email' => 'user@example.com',
    'otp' => '123456',
]);

// Step 3: Reset password
$resetService->resetPassword([
    'email' => 'user@example.com',
    'otp' => '123456',
    'password' => 'newPassword123',
    'password_confirmation' => 'newPassword123',
]);
```

## ThrottleService

Rate limiting for login attempts.

```php
// app/Services/Auth/ThrottleService.php
namespace App\Services\Auth;

class ThrottleService
{
    public function check(string $key): void;
    public function increment(string $key): void;
    public function clear(string $key): void;
    public function remainingAttempts(string $key): int;
}
```

### Usage

```php
use App\Services\Auth\ThrottleService;

$throttle = new ThrottleService();
$key = 'login:' . $request->email;

// Check if locked
$throttle->check($key);  // Throws TooManyRequestsException if locked

// Increment on failed attempt
$throttle->increment($key);

// Clear on successful login
$throttle->clear($key);

// Get remaining attempts
$remaining = $throttle->remainingAttempts($key);
```

### Configuration

```php
// config/project.php
'auth' => [
    'max_login_attempts' => 5,   // Max attempts before lockout
    'lockout_time' => 180,       // Lockout duration in seconds
],
```

## BaseAuthService

Base class providing common functionality:

```php
// app/Services/Auth/BaseAuthService.php
namespace App\Services\Auth;

abstract class BaseAuthService
{
    protected string $guard = 'api';
    protected string $model;

    public function setGuard(string $guard): self
    {
        $this->guard = $guard;
        return $this;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getGuard(): string
    {
        return $this->guard;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
```

## Controller Usage

```php
// app/Http/Controllers/API/Central/Auth/LoginController.php
class LoginController extends BaseController
{
    public function __construct(
        protected LoginService $loginService,
        protected ThrottleService $throttleService
    ) {
        parent::__construct();
    }

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $key = 'login:' . $request->email;
        
        $this->throttleService->check($key);

        try {
            $result = $this->loginService
                ->setGuard($this->guard)
                ->setModel($this->userModel)
                ->attempt($request->validated());

            $this->throttleService->clear($key);

            return successResponse(new LoginResource($result));
        } catch (InvalidEmailAndPasswordCombinationException $e) {
            $this->throttleService->increment($key);
            throw $e;
        }
    }
}
```

## See Also

- [Authentication Guide](/guide/authentication)
- [Services Overview](/guide/services/)
- [Project Configuration](/guide/configuration/project)
