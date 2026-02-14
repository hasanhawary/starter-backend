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

## UniqueWithTrashed

- **What it does:** Ensures uniqueness across soft-deleted records (`withTrashed()`), and throws a `ModelAlreadyExistsException` (containing a resource payload) when a duplicate is found.

- **Usage (Form Request):**

```php
use App\Rules\UniqueWithTrashed;

public function rules(): array
{
    return [
        'email' => [new UniqueWithTrashed(\App\Models\User::class, \App\Http\Resources\User\UserResource::class, $this->user?->id)],
    ];
}
```

- **Notes:** For API consumers, the thrown `ModelAlreadyExistsException` is converted into a structured error response; handle or let the global exception handler return the payload.

## ValidLength

- **What it does:** Validates a value's length equals a reference model's specified `length` column (useful for phone numbers where each country has a specific length).

- **Usage (Form Request):**

```php
use App\Rules\ValidLength;

public function rules(): array
{
    return [
        'phone' => ['required', new ValidLength($this->country_id, \App\Models\Country::class, 'length')],
    ];
}
```

- **Notes:** Pass the reference model id (e.g., country id) so the rule can look up the expected length.

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