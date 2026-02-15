---
title: Custom Validation Rules
description: Custom validation rules for complex validation scenarios
---

# Custom Validation Rules

Your project includes 7 custom validation rules for specific validation scenarios.

## StrongPassword

Validates passwords meet strong security requirements including uppercase, lowercase, numbers, special characters, and checks against dictionary words and personal information.

**Location:** `app/Rules/StrongPassword.php`

**Constructor:**
```php
new StrongPassword(
    ?string $firstName = null,
    ?string $middleName = null,
    ?string $lastName = null
)
```

**Validation Checks:**
- Minimum 8 characters
- Contains uppercase letters (A-Z)
- Contains lowercase letters (a-z)
- Contains numbers (0-9)
- Contains special characters
- No repeating characters (3+ times)
- No sequential characters (abc, 123, etc.)
- No personal information from names
- No dictionary words (password, admin, welcome, etc.)

**Usage:**
```php
$validated = $request->validate([
    'password' => [
        'required',
        new StrongPassword(
            $request->first_name,
            $request->middle_name,
            $request->last_name
        )
    ]
]);
```

## CheckSamePassword

Ensures the new password is different from the currently authenticated user's password using hash comparison.

**Location:** `app/Rules/CheckSamePassword.php`

**Usage:**
```php
$validated = $request->validate([
    'new_password' => [
        'required',
        new CheckSamePassword()
    ]
]);
```

**Error Message:** "Your new password must be different from your current password"

## UniqueCheck

Validates uniqueness in database with support for soft deletes, JSON columns, and model-based exceptions.

**Location:** `app/Rules/UniqueCheck.php`

**Constructor:**
```php
new UniqueCheck(
    string $modelClass,      // Full model class name
    string $resourceClass,   // Resource class for response
    ?string $ignoreId = null // ID to exclude from check
)
```

**Features:**
- Supports soft-deleted models
- Handles JSON column searches
- Throws `ModelAlreadyExistsException` with resource data
- Automatically excludes specified ID on updates

**Usage:**
```php
$validated = $request->validate([
    'email' => [
        'required',
        new UniqueCheck(User::class, UserResource::class, $userId)
    ]
]);
```

## TotalFileSize

Validates the combined size of uploaded files doesn't exceed a maximum.

**Location:** `app/Rules/TotalFileSize.php`

**Constructor:**
```php
new TotalFileSize(
    int $maxMB,                          // Maximum size in MB
    array $existingAttachments = []      // Existing files to include in total
)
```

**Features:**
- Converts MB to bytes internally
- Includes existing attachment sizes
- Checks storage file sizes for existing attachments

**Usage:**
```php
$validated = $request->validate([
    'attachments' => [
        'array',
        new TotalFileSize(10, $existingAttachments)
    ]
]);
```

## TranslatableRequired

Validates translatable fields with language-specific requirements from config.

**Location:** `app/Rules/TranslatableRequired.php`

**Constructor:**
```php
new TranslatableRequired(
    string $table,           // Database table name
    array $rules = [],       // Validation rules per language
    ?string $route = null    // Route parameter for update ignore
)
```

**Features:**
- Reads language requirements from `config('lang.languages_validation')`
- Supports unique validation per language with JSON paths
- Handles update scenarios with route parameter

**Usage:**
```php
$validated = $request->validate([
    'name' => [
        new TranslatableRequired(
            'users',
            ['required', 'max:191'],
            'user'  // route parameter to ignore on update
        )
    ]
]);
```

## TranslatableNullable

Validates translatable fields that can be nullable per language.

**Location:** `app/Rules/TranslatableNullable.php`

**Constructor:**
```php
new TranslatableNullable(
    string $table,           // Database table name
    array $rules = [],       // Validation rules
    ?string $route = null    // Route parameter for update
)
```

**Features:**
- Marks all languages as `sometimes` and `nullable`
- Validates each language separately
- Flattens nested attribute labels for error messages

**Usage:**
```php
$validated = $request->validate([
    'description' => [
        new TranslatableNullable('users')
    ]
]);
```

## ValidLength

Validates a value has exact length based on a model's length column.

**Location:** `app/Rules/ValidLength.php`

**Constructor:**
```php
new ValidLength(
    ?int $referenceId,                    // ID of reference model
    string $modelClass,                   // Model class to fetch length from
    string $lengthColumn = 'length'       // Column name containing length
)
```

**Features:**
- Validates model class exists and extends Eloquent
- Fetches length requirement from reference model
- Compares exact string length

**Usage:**
```php
$validated = $request->validate([
    'phone' => [
        'required',
        new ValidLength($countryId, Country::class, 'phone_length')
    ]
]);
```

**Error Message:** "The phone must be exactly {length} characters."
