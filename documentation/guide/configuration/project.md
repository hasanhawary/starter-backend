---
title: Project Configuration
description: Application-wide settings for authentication, LDAP, OTP, and more
---

# Project Configuration

The `config/project.php` file contains application-wide settings. These control authentication behavior, LDAP integration, OTP configuration, and general project settings.

## Project Information

```php
'project' => [
    'name' => env('APP_NAME', 'MyProject'),
    'version' => env('APP_VERSION', '1.0.0'),
    'env' => env('APP_ENV', 'production'),
    'locale' => 'ar',                    // Default language
    'fallback_locale' => 'en',
    'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
    'currency' => 'EGP',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
    'datetime_format' => 'Y-m-d H:i:s',
],
```

## Authentication Settings

```php
'auth' => [
    'login_methods' => [
        'password' => true,
        'otp' => env('AUTH_LOGIN_OTP', true),
    ],

    'encryption' => [
        'key' => env('FRONT_SHARED_KEY', 'default_secret_key'),

        // Decrypt incoming data from frontend
        'incoming' => [
            'password' => false,
            'otp' => false,
        ],

        // Encrypt outgoing data to frontend
        'outgoing' => [
            'roles' => true,
            'permissions' => true,
            'token' => true,
            'user_data' => false,
        ],
    ],

    'otp' => [
        'required_for' => [
            'admin' => false,    // Require OTP for admin login
            'user' => false,     // Require OTP for user login
        ],
        'fallback_to_password' => true,
    ],

    'max_login_attempts' => 5,
    'lockout_time' => 180,           // Seconds
    'default_role' => 'default_role',
    'default_phone_code_id' => 1,
],
```

### Login Methods

- **password**: Traditional email/password login
- **otp**: One-time password via SMS/email

### Encryption

Frontend encryption for sensitive data transfer:

```php
// Check if encryption is enabled for a field
if (config('project.auth.encryption.incoming.password')) {
    $password = decrypt($request->password);
}
```

### Rate Limiting

- `max_login_attempts`: Maximum failed attempts before lockout
- `lockout_time`: Lockout duration in seconds

## LDAP Configuration

```php
'ldap' => [
    'active' => env('LDAP_ACTIVE', false),   // Enable/disable LDAP login
    'type' => env('LDAP_TYPE', 'ad'),        // 'ad' or 'openldap'
    'local' => env('LDAP_LOCAL', true),      // true = OpenLDAP, false = Active Directory
],
```

### Environment Variables for LDAP

```env
LDAP_ACTIVE=true
LDAP_TYPE=ad
LDAP_LOCAL=false
LDAP_HOST=ldap.example.com
LDAP_BASE_DN=dc=example,dc=com
LDAP_ADMIN_USERNAME=admin@example.com
LDAP_ADMIN_PASSWORD=secret
LDAP_PORT=389
LDAP_TIMEOUT=5
```

### LDAP Login Flow

1. Check if LDAP is active (`config('project.ldap.active')`)
2. Attempt LDAP authentication
3. Fall back to database authentication if LDAP fails
4. Create/update local user record on successful LDAP auth

```php
// In LoginService.php
$user = config('project.ldap.active')
    ? $this->attemptLdapLogin($data)
    : $this->attemptDefaultLogin($data);
```

## OTP Configuration

```php
'otp' => [
    'default' => '1111',          // Fixed OTP for testing (set to null in production)
    'length' => 6,                // Number of characters
    'type' => 'alpha',            // 'numeric', 'alpha', or 'alphanumeric'
    'delay' => '30',              // Seconds between sends
    'expires_in' => 10,           // Minutes until expiration
    'max_attempts' => 1,          // Max verification attempts
    'lock_time' => 120,           // Lock duration in seconds after max attempts
],
```

### OTP Types

| Type | Example |
|------|---------|
| `numeric` | `123456` |
| `alpha` | `ABCDEF` |
| `alphanumeric` | `A1B2C3` |

### Testing OTP

Set `default` to a fixed value during development:

```php
'default' => '1111',  // Always use this OTP in development
```

Set to `null` in production to generate random OTPs.

## Pagination Defaults

```php
'pagination' => [
    'per_page' => 15,    // Default items per page
    'max' => 100,        // Maximum items per page
],
```

Used by the `fetchData()` helper:

```php
// In controllers
return successResponse(fetchData($query, $request->pageSize, UserResource::class));
```

## Real-time Configuration

```php
'realtime' => [
    'enable' => env('REALTIME', false),
],
```

When enabled, notifications are broadcast via WebSocket:

```php
// In NotificationService.php
if (config('project.realtime.enable')) {
    event(new NotificationEvent($user->id, $data));
}
```

## Usage Examples

### Check Login Method

```php
if (config('project.auth.login_methods.otp')) {
    // OTP login is enabled
}
```

### Get OTP Settings

```php
$length = config('project.otp.length');      // 6
$expiresIn = config('project.otp.expires_in'); // 10 minutes
```

### Check LDAP Status

```php
if (config('project.ldap.active')) {
    // Use LDAP authentication
}
```

### Get Pagination Defaults

```php
$perPage = config('project.pagination.per_page'); // 15
```

## See Also

- [Authentication Guide](/guide/authentication)
- [Configuration Overview](/guide/configuration/)
- [Auth Services](/guide/services/auth-services)
