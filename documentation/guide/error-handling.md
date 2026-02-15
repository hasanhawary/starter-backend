---
title: Error Handling
description: Exception handling and error responses
---

# Error Handling

This guide documents error handling, custom exceptions, and error response formats in the dashboard backend.

## Overview

The dashboard backend provides comprehensive error handling with custom exceptions, proper HTTP status codes, and consistent error response formats.

## Custom Exceptions

### Available Exceptions

All custom exceptions are located in `app/Exceptions/`.

#### 1. AccountNotFoundException

Thrown when a user account is not found.

```php
use App\Exceptions\AccountNotFoundException;

throw new AccountNotFoundException('User account not found');
```

**HTTP Status:** 404 Not Found

**Response:**
```json
{
  "success": false,
  "message": "User account not found",
  "code": 404
}
```

---

#### 2. EmailVerifiedException

Thrown when attempting to verify an already verified email.

```php
use App\Exceptions\EmailVerifiedException;

throw new EmailVerifiedException('Email is already verified');
```

**HTTP Status:** 422 Unprocessable Entity

**Response:**
```json
{
  "success": false,
  "message": "Email is already verified",
  "code": 422
}
```

---

#### 3. InActiveUserException

Thrown when an inactive user attempts to perform an action.

```php
use App\Exceptions\InActiveUserException;

throw new InActiveUserException('User account is inactive');
```

**HTTP Status:** 403 Forbidden

**Response:**
```json
{
  "success": false,
  "message": "User account is inactive",
  "code": 403
}
```

---

#### 4. InvalidEmailAndPasswordCombinationException

Thrown when login credentials are invalid.

```php
use App\Exceptions\InvalidEmailAndPasswordCombinationException;

throw new InvalidEmailAndPasswordCombinationException('Invalid email or password');
```

**HTTP Status:** 401 Unauthorized

**Response:**
```json
{
  "success": false,
  "message": "Invalid email or password",
  "code": 401
}
```

---

#### 5. InvalidOtpException

Thrown when OTP verification fails.

```php
use App\Exceptions\InvalidOtpException;

throw new InvalidOtpException('Invalid or expired OTP');
```

**HTTP Status:** 422 Unprocessable Entity

**Response:**
```json
{
  "success": false,
  "message": "Invalid or expired OTP",
  "code": 422
}
```

---

#### 6. InvalidPasswordResetTokenException

Thrown when password reset token is invalid or expired.

```php
use App\Exceptions\InvalidPasswordResetTokenException;

throw new InvalidPasswordResetTokenException('Invalid or expired reset token');
```

**HTTP Status:** 422 Unprocessable Entity

**Response:**
```json
{
  "success": false,
  "message": "Invalid or expired reset token",
  "code": 422
}
```

---

#### 7. ModelAlreadyExistsException

Thrown when attempting to create a duplicate model.

```php
use App\Exceptions\ModelAlreadyExistsException;

throw new ModelAlreadyExistsException('User with this email already exists');
```

**HTTP Status:** 409 Conflict

**Response:**
```json
{
  "success": false,
  "message": "User with this email already exists",
  "code": 409
}
```

---

## Exception Handler

**Location:** `app/Exceptions/Handler.php`

The exception handler processes all exceptions and returns appropriate responses.

### Rendering Exceptions

```php
public function render($request, Throwable $exception)
{
    // Handle JSON requests
    if ($request->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'code' => $this->getStatusCode($exception),
        ], $this->getStatusCode($exception));
    }

    return parent::render($request, $exception);
}
```

### Status Code Mapping

```php
protected function getStatusCode(Throwable $exception): int
{
    return match (get_class($exception)) {
        AccountNotFoundException::class => 404,
        EmailVerifiedException::class => 422,
        InActiveUserException::class => 403,
        InvalidEmailAndPasswordCombinationException::class => 401,
        InvalidOtpException::class => 422,
        InvalidPasswordResetTokenException::class => 422,
        ModelAlreadyExistsException::class => 409,
        default => 500,
    };
}
```

---

## Standard Error Response Format

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "code": 400,
  "errors": {
    "field_name": ["Error message for field"]
  }
}
```

### Validation Errors

```json
{
  "success": false,
  "message": "Validation failed",
  "code": 422,
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

---

## HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful request |
| 201 | Created | Resource created successfully |
| 204 | No Content | Successful request with no content |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Access denied |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource already exists |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Service temporarily unavailable |

---

## Handling Exceptions in Controllers

### Try-Catch Pattern

```php
use App\Exceptions\AccountNotFoundException;

public function show($id)
{
    try {
        $user = User::findOrFail($id);
        return successResponse($user);
    } catch (ModelNotFoundException $e) {
        throw new AccountNotFoundException('User not found');
    }
}
```

### Using Helper Functions

```php
// Success response
return successResponse($data, 'Operation successful', 200);

// Error response
return failResponse('Operation failed', 400);

// Validation error response
return failResponse('Validation failed', 422, $errors);
```

---

## Logging Exceptions

### Configure Logging

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
    ],
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
]
```

### Log Exceptions

```php
use Illuminate\Support\Facades\Log;

try {
    // Code that might throw exception
} catch (Exception $e) {
    Log::error('Operation failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => auth('sanctum')->id(),
    ]);
    
    throw new Exception('Operation failed');
}
```

---

## Common Error Scenarios

### 1. Authentication Failed

```php
// Request
POST /api/login
{
  "email": "user@example.com",
  "password": "wrong_password"
}

// Response
{
  "success": false,
  "message": "Invalid email or password",
  "code": 401
}
```

### 2. Validation Failed

```php
// Request
POST /api/users
{
  "email": "invalid-email"
}

// Response
{
  "success": false,
  "message": "Validation failed",
  "code": 422,
  "errors": {
    "email": ["The email must be a valid email address"],
    "name": ["The name field is required"]
  }
}
```

### 3. Resource Not Found

```php
// Request
GET /api/users/999

// Response
{
  "success": false,
  "message": "User not found",
  "code": 404
}
```

### 4. Access Denied

```php
// Request
DELETE /api/users/1
Authorization: Bearer {token_without_permission}

// Response
{
  "success": false,
  "message": "This action is unauthorized",
  "code": 403
}
```

### 5. Rate Limit Exceeded

```php
// Response
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "code": 429
}
```

---

## Best Practices

1. **Use specific exceptions** - Throw specific exceptions instead of generic Exception
2. **Provide meaningful messages** - Error messages should be clear and actionable
3. **Log important errors** - Log errors for debugging and monitoring
4. **Validate input** - Validate all user input before processing
5. **Handle exceptions gracefully** - Don't expose sensitive information in error messages
6. **Use appropriate status codes** - Use correct HTTP status codes for different scenarios
7. **Consistent error format** - Always return errors in the same format
8. **Test error scenarios** - Write tests for error handling

---

## Creating Custom Exceptions

### Example: Custom Exception

```php
<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    public function __construct($message = '', $code = 0)
    {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'code' => $this->code,
        ], $this->code);
    }
}
```

### Usage

```php
throw new CustomException('Custom error message', 400);
```

---

## See Also

- [Authentication](/guide/authentication) — Authentication errors
- [Authorization](/guide/authorization) — Authorization errors
- [API Reference](/guide/api-reference) — API error responses
