---
title: Custom Validation Rules
description: Built-in validation rules in app/Rules and how to use them
---

# Custom Validation Rules

This page documents the custom validation rules in `app/Rules/` and how to use them in Form Requests.

## Overview

All custom rules implement Laravel's `ValidationRule` interface with a `validate` method:

```php
use Illuminate\Contracts\Validation\ValidationRule;

class CustomRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (/* condition fails */) {
            $fail(__('validation.custom_error'));
        }
    }
}
```

## UniqueCheck

Validates uniqueness across all records including soft-deleted ones. Throws `ModelAlreadyExistsException` with the existing record data when a duplicate is found.

**Location:** `app/Rules/UniqueCheck.php`

**Constructor:**
```php
new UniqueCheck(
    string $modelClass,      // Model to check against
    string $resourceClass,   // Resource class for error response
    ?string $ignoreId = null // ID to ignore (for updates)
)
```

**Usage:**
```php
use App\Rules\UniqueCheck;

public function rules(): array
{
    $userId = $this->route('user')?->getKey();

    return [
        'email' => [
            'required',
            'email',
            Rule::unique('users', 'email')->ignore($userId)->withoutTrashed(),
            new UniqueCheck(
                User::class,
                UserResource::class,
                $userId
            ),
        ],
    ];
}
```

**Behavior:**
- Checks `withTrashed()` to include soft-deleted records
- Throws `ModelAlreadyExistsException` with HTTP 433 status
- Exception includes the existing record as a resource for conflict resolution

## ValidLength

Validates that a field's length matches a value from a reference model (e.g., phone number length by country).

**Location:** `app/Rules/ValidLength.php`

**Constructor:**
```php
new ValidLength(
    mixed $referenceId,     // ID of the reference model
    string $modelClass,     // Reference model class
    string $lengthColumn    // Column containing expected length
)
```

**Usage:**
```php
use App\Rules\ValidLength;

public function rules(): array
{
    return [
        'phone' => [
            'nullable',
            'regex:/^[0-9]+$/',
            new ValidLength(
                $this->input('phone_code_id'),
                Country::class,
                'phone_length'
            ),
        ],
    ];
}
```

**Behavior:**
- Looks up the reference model by ID
- Compares input length against the `lengthColumn` value
- Fails if lengths don't match


## StrongPassword

Enforces comprehensive password strength requirements.

**Location:** `app/Rules/StrongPassword.php`

**Constructor:**
```php
new StrongPassword(
    ?string $firstName = null,
    ?string $middleName = null,
    ?string $lastName = null
)
```

**Usage:**
```php
use App\Rules\StrongPassword;

public function rules(): array
{
    return [
        'password' => [
            'required',
            'confirmed',
            new StrongPassword(
                $this->first_name,
                $this->middle_name,
                $this->last_name
            ),
        ],
    ];
}
```

**Validation Checks:**
- Minimum 8 characters
- Contains uppercase and lowercase letters
- Contains digits
- Contains special characters
- No more than 3 repeated consecutive characters
- Not a common dictionary word
- No sequential characters (abc, 123)
- Doesn't contain user's name


## CheckSamePassword

Validates that a new password differs from the current password.

**Location:** `app/Rules/CheckSamePassword.php`

**Usage:**
```php
use App\Rules\CheckSamePassword;

public function rules(): array
{
    return [
        'new_password' => [
            'required',
            'confirmed',
            new CheckSamePassword(),
        ],
    ];
}
```

**Behavior:**
- Uses `Hash::check()` to compare against `auth()->user()->password`
- Fails if the new password matches the current one

## TranslatableRequired

Validates that translatable fields have required language values.

**Location:** `app/Rules/TranslatableRequired.php`

**Constructor:**
```php
new TranslatableRequired(
    array $requiredLocales = ['en', 'ar']
)
```

**Usage:**
```php
use App\Rules\TranslatableRequired;

public function rules(): array
{
    return [
        'name' => [
            'required',
            'array',
            new TranslatableRequired(['en', 'ar']),
        ],
    ];
}
```

**Expected Input:**
```json
{
    "name": {
        "en": "English Name",
        "ar": "الاسم العربي"
    }
}
```


## TranslatableNullable

Allows translatable fields to be null or have partial translations.

**Location:** `app/Rules/TranslatableNullable.php`

**Usage:**
```php
use App\Rules\TranslatableNullable;

public function rules(): array
{
    return [
        'description' => [
            'nullable',
            'array',
            new TranslatableNullable(),
        ],
    ];
}
```


## TotalFileSize

Validates that the total size of uploaded files doesn't exceed a limit.

**Location:** `app/Rules/TotalFileSize.php`

**Constructor:**
```php
new TotalFileSize(
    int $maxSizeInKB = 10240  // Default 10MB
)
```

**Usage:**
```php
use App\Rules\TotalFileSize;

public function rules(): array
{
    return [
        'attachments' => [
            'nullable',
            'array',
            new TotalFileSize(20480),  // 20MB max total
        ],
        'attachments.*' => [
            'file',
            'max:5120',  // 5MB per file
        ],
    ];
}
```


## Using Rules in Form Requests

All Form Requests should extend `BaseFormRequest`:

```php
<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use App\Rules\UniqueCheck;
use App\Rules\ValidLength;

class UserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $userId = $this->route('user')?->getKey();

        return [
            'name' => ['required', 'string', 'max:50'],

            'phone_code_id' => [
                'nullable',
                'numeric',
                Rule::exists('countries', 'id')->withoutTrashed(),
            ],

            'phone' => [
                'nullable',
                'regex:/^[0-9]+$/',
                new ValidLength($this->input('phone_code_id'), Country::class, 'phone_length'),
            ],

            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId)->withoutTrashed(),
                new UniqueCheck(User::class, UserResource::class, $userId),
            ],

            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Handle phone object format
        $phone = $this->input('phone');
        if (is_array($phone)) {
            $this->merge([
                'phone' => $phone['phone'] ?? null,
                'phone_code_id' => $phone['phone_code_id'] ?? null,
            ]);
        }
    }
}
```


## Creating Custom Rules

1. Generate a new rule:
```bash
php artisan make:rule CustomRule
```

2. Implement the rule:
```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomRule implements ValidationRule
{
    public function __construct(
        protected string $param
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (/* validation fails */) {
            $fail(__('validation.custom_message', ['attribute' => $attribute]));
        }
    }
}
```

3. Add translation in `lang/en/validation.php`:
```php
'custom_message' => 'The :attribute is invalid.',
```

## See Also

- [Architecture](/guide/architecture) — BaseFormRequest pattern
- [Helper Functions](/guide/helpers) — resolveEmptyToNull helper
- [API Reference](/guide/api-reference) — Endpoint validation

## TranslatableRequired

- **What it does:** Validates translatable (JSON) attributes across languages using `config('lang.languages_validation')` and supports rules like `unique` that are injected per language.

- **Usage (Form Request):**

```php
use App\Rules\TranslatableRequired;

public function rules(): array
{
    // Validate `title` for each language configured in `lang.languages_validation`
    return [
        'title' => [new TranslatableRequired('pages', ['required', 'unique'], 'page')],
    ];
}
```

- **Notes:** When using `unique` in the rules array, the rule will convert it to a `Rule::unique` check against the JSON path (e.g., `title->en`) and will respect `request()->route($route)` to ignore the current resource on updates.

### Notes

- Rules throw typed exceptions or call `$fail()` with localized messages — Form Requests will convert these to JSON errors automatically.
- Add translations under `resources/lang` for custom validation messages (e.g., `validation.password_min_length`).

## See also

- [API Reference](/guide/api-reference) — see endpoints list