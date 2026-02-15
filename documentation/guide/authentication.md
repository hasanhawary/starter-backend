---
title: Authentication
description: Complete authentication system including login, LDAP, password reset, and OTP
---

# Authentication

The Admin Dashboard Kit uses **Laravel Sanctum** for stateless, token-based API authentication with support for traditional login, LDAP, and OTP-based password recovery.

## Login

### Basic Login

```bash
curl -X POST http://starter-backend.test/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "root@wakeb.com",
    "password": "Your123456"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "root@wakeb.com",
      "phone": "+1234567890",
      "gender": "male",
      "is_active": true,
      "last_login": "2024-01-20T15:30:00Z",
      "roles": ["Admin", "Editor"]
    },
    "token": "1|abc123xyz789def456ghi789jkl012mno345pqr"
  }
}
```

Save the `token` for subsequent API requests.

### LDAP Login (Optional)

If LDAP is configured, the system automatically attempts LDAP authentication:

**Configuration in `.env`:**

```env
LDAP_ACTIVE=true
LDAP_LOCAL=false  # false = Active Directory, true = OpenLDAP
LDAP_HOST=ldap.example.com
LDAP_BASE_DN=dc=example,dc=com
LDAP_ADMIN_USERNAME=root_user@name.com
LDAP_ADMIN_PASSWORD=ldap_password
```

**Login Flow:**

1. User provides email/username and password
2. System checks LDAP first (if enabled)
3. Falls back to database authentication
4. Creates/updates user in database
5. Returns Sanctum token

### Rate Limiting

Login attempts are rate-limited to prevent brute force attacks:

**Limit:** 5 login attempts per email per minute

## Logout

Revoke the current authentication token:

```bash
curl -X POST http://starter-backend.test/api/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
  "success": true,
  "message": "User logged out successfully"
}
```

The token is immediately revoked and cannot be used for future requests.

## Forget Password (OTP-Based)

For users who forget their password, initiate the recovery process:

### Step 1: Request Password Reset

```bash
curl -X POST http://starter-backend.test/api/forget \
  -H "Content-Type: application/json" \
  -d '{
    "email": "root@wakeb.com"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "OTP sent to your email address",
  "data": {
    "message": "A one-time password has been sent to your registered email"
  }
}
```

**What happens:**

1. User email is validated
2. 6-digit OTP is generated
3. OTP is sent via email
4. OTP expires in 10 minutes

### Step 2: Verify OTP

```bash
curl -X POST http://starter-backend.test/api/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "root@wakeb.com",
    "otp": "123456"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "OTP verified successfully",
  "data": {
    "reset_token": "reset_token_abc123xyz..."
  }
}
```

Save the `reset_token` for the next step.

**Error - Invalid OTP:**

```json
{
  "success": false,
  "message": "Invalid or expired OTP",
  "errors": null
}
```

### Step 3: Reset Password

```bash
curl -X POST http://starter-backend.test/api/reset \
  -H "Content-Type: application/json" \
  -d '{
    "email": "root@wakeb.com",
    "reset_token": "reset_token_abc123xyz...",
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Password reset successfully",
  "data": {
    "message": "Your password has been reset. Please log in with your new password."
  }
}
```

**Error - Invalid Token:**

```json
{
  "success": false,
  "message": "Invalid or expired reset token",
  "errors": null
}
```

## Using the Authentication Token

Include the token in the `Authorization` header for all protected endpoints:

### Option 1: Bearer Token

```bash
curl http://starter-backend.test/api/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Token Management

### Get Current User

```bash
curl http://starter-backend.test/api/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "root@wakeb.com",
    "roles": ["Admin"]
  }
}
```

## Protected Routes

All routes under the `auth:sanctum` middleware require a valid token:

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('me', [ProfileController::class, 'user']);
    Route::post('update-profile', [ProfileController::class, 'updateProfile']);
    Route::apiResource('users', UserController::class);
    // ... more routes
});
```

**Error - Missing Token:**

```json
{
  "success": false,
  "message": "Unauthenticated",
  "errors": null
}
```

**Error - Invalid/Expired Token:**

```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": null
}
```

## Inactive User Handling

Users can be marked as inactive to prevent login:

```bash
curl -X POST http://starter-backend.test/api/users/5/change-status \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "is_active": false
  }'
```

**Trying to login as inactive user:**

```json
{
  "success": false,
  "message": "Your account is not active",
  "errors": null
}
```

## Common Errors

### Invalid Credentials

```json
{
  "success": false,
  "message": "Invalid email or password combination"
}
```

**Fix:** Verify email and password are correct

### Inactive Account

```json
{
  "success": false,
  "message": "Your account is not active"
}
```

**Fix:** Contact administrator to reactivate account

### Expired OTP

```json
{
  "success": false,
  "message": "OTP has expired. Request a new one."
}
```

**Fix:** Call `/api/forget` again to get a new OTP

### Invalid Reset Token

```json
{
  "success": false,
  "message": "Invalid or expired reset token"
}
```

**Fix:** Complete forget password process again

## Next Steps

- [Authorization & Policies](/guide/authorization) — Control access
- [API Reference - Authentication](/guide/api-reference) — Full endpoint docs
