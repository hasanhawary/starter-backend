---
title: Global Validation Rules
description: Built-in validation rules and examples
---

# Global Validation Rules

This page documents the custom validation rules in `app/Rules` and how to use them in Form Requests.

## How to use

Import the rule at the top of your Form Request (or inline) and add it to the validation rules array:

```php
use App\Rules\StrongPassword;

public function rules(): array
{
    return [
        'password' => ['required', 'confirmed', new StrongPassword($this->first_name, $this->middle_name, $this->last_name)],
    ];
}
```

## StrongPassword

- **What it does:** Enforces password strength (min length 8, upper/lower/digits/special, no long repeated chars, no common dictionary words, no sequential chars, avoid personal names).

- **Usage (Form Request):**

```php
use App\Rules\StrongPassword;

public function rules(): array
{
    return [
        'password' => ['required', 'confirmed', new StrongPassword($this->first_name, $this->middle_name, $this->last_name)],
    ];
}
```

- **Notes:** The rule will call `$fail()` with localized messages (e.g., `validation.password_min_length`) for each failing check.

## CheckSamePassword

- **What it does:** Validates that a new password is different than the current password (`Hash::check`).

- **Usage (Form Request):**

```php
use App\Rules\CheckSamePassword;

public function rules(): array
{
    return [
        'new_password' => ['required', new CheckSamePassword()],
    ];
}
```

- **Notes:** Compares against `auth()->user()->password` and fails validation when the new password matches the current one.

## UniqueCheck

- **What it does:** Ensures uniqueness across soft-deleted records (`withTrashed()`), and throws a `ModelAlreadyExistsException` (containing a resource payload) when a duplicate is found. Supports JSON fields.

- **Usage (Form Request):**

```php
use App\Rules\UniqueCheck;

public function rules(): array
{
    return [
        'name' => [
            'required',
            'array',
            new UniqueCheck(
                Country::class,
                CountryResource::class,
                $this->route('country')?->id  // Ignore current record on update
            )
        ],
    ];
}
```

- **Notes:** Returns HTTP 433 with the existing resource data, allowing frontend to offer restore/edit options.

## ValidLength

- **What it does:** Validates a value's length equals a reference model's specified column value (useful for phone numbers where each country has a specific length).

- **Usage (Form Request):**

```php
use App\Rules\ValidLength;

public function rules(): array
{
    return [
        'phone' => [
            'required', 
            new ValidLength(
                $this->phone_code_id,      // Reference ID
                \App\Models\Country::class, // Model to lookup
                'phone_length'              // Column containing expected length
            )
        ],
    ];
}
```

## TranslatableRequired

- **What it does:** Validates translatable (JSON) attributes across languages using `config('lang.languages_validation')` and supports rules like `unique` that are injected per language.

- **Usage (Form Request):**

```php
use App\Rules\TranslatableRequired;

public function rules(): array
{
    return [
        'name' => [
            'required',
            'array',
            new TranslatableRequired(
                'countries',                    // Table name
                ['string', 'max:191'],          // Rules per language
                'country'                       // Route param for updates
            )
        ],
    ];
}
```

- **Notes:** Uses `config('lang.languages_validation')` to determine which languages are required vs optional. Handles `unique` rule by checking against JSON paths (e.g., `name->en`).

## TranslatableNullable

- **What it does:** Same as `TranslatableRequired` but allows null/empty values.

- **Usage:**

```php
use App\Rules\TranslatableNullable;

public function rules(): array
{
    return [
        'description' => ['nullable', 'array', new TranslatableNullable('posts', ['string'])],
    ];
}
```

## TotalFileSize

- **What it does:** Validates the total size of multiple uploaded files doesn't exceed a limit.

- **Usage:**

```php
use App\Rules\TotalFileSize;

public function rules(): array
{
    return [
        'attachments' => [
            'array',
            new TotalFileSize(
                10,                          // Max total MB
                $this->existingAttachments   // Optional: include existing files in calculation
            )
        ],
        'attachments.*' => ['file'],
    ];
}
```

### Notes & Best Practices

- Rules throw typed exceptions or call `$fail()` with localized messages.
- Add translations under `resources/lang/{locale}/validation.php`.
- All rules implement `ValidationRule` interface for Laravel 10+ compatibility.

### Notes

- Rules throw typed exceptions or call `$fail()` with localized messages — Form Requests will convert these to JSON errors automatically.
- Add translations under `resources/lang` for custom validation messages (e.g., `validation.password_min_length`).

## See also

- [API Reference](/guide/api-reference) — see endpoints list