---
title: Enums & Constants
description: Type-safe enums used throughout the application
---

# Enums & Constants

Your project uses type-safe enums for common values. All enums use the `EnumMethods` trait from the lookup manager package.

## Dashboard Project Enums

### ActiveTypeEnum

Represents the active/inactive status of entities.

**Location:** `app/Enum/Global/ActiveTypeEnum.php`

**Values:**
```php
case Active = 1;      // Active status
case InActive = 0;    // Inactive status
```

**Usage:**
```php
use App\Enum\Global\ActiveTypeEnum;

// Check status
if ($user->is_active === ActiveTypeEnum::Active->value) {
    // User is active
}

// Filter by status
$activeUsers = User::where('is_active', ActiveTypeEnum::Active->value)->get();

// In model casting
protected $casts = [
    'is_active' => ActiveTypeEnum::class,
];
```

### OtpTypeEnum

Represents different OTP purposes.

**Location:** `app/Enum/Global/OtpTypeEnum.php`

**Values:**
```php
case Login = 'login';
case ResetPassword = 'reset_password';
case VerifyEmail = 'verify_email';
```

**Usage:**
```php
use App\Enum\Global\OtpTypeEnum;

// Send OTP for login
$otp = $otpService->send($request, OtpTypeEnum::Login->value);

// Send OTP for password reset
$otp = $otpService->send($request, OtpTypeEnum::ResetPassword->value);

// Verify OTP
$user = $otpService->verify($request, OtpTypeEnum::Login->value);
```

## Landing Project Enums

### UserGenderEnum

Represents user gender options.

**Location:** `app/Enum/User/UserGenderEnum.php`

**Values:**
```php
case Male = 'male';
case Female = 'female';
```

**Usage:**
```php
use App\Enum\User\UserGenderEnum;

$user->gender = UserGenderEnum::Male->value;
$user->save();

// Filter by gender
$maleUsers = User::where('gender', UserGenderEnum::Male->value)->get();
```

### ActiveTypeEnum

Same as dashboard project.

**Location:** `app/Enum/Global/ActiveTypeEnum.php`

### OtpTypeEnum

Same as dashboard project.

**Location:** `app/Enum/Global/OtpTypeEnum.php`

## Tenant Project Enums

### TenantStatusEnum

Represents tenant provisioning status.

**Location:** `app/Enum/Tenant/TenantStatusEnum.php`

**Values:**
```php
case Pending = 'pending';              // Created, not provisioned yet
case Provisioning = 'provisioning';    // Database/tenant setup in progress
case Ready = 'ready';                  // Tenant fully active
case Failed = 'failed';                // Provisioning failed
```

**Default:** `Pending`

**Usage:**
```php
use App\Enum\Tenant\TenantStatusEnum;

// Create new tenant
$tenant = Tenant::create([
    'name' => 'Acme Corp',
    'status' => TenantStatusEnum::default()  // 'pending'
]);

// Check status
if ($tenant->status === TenantStatusEnum::Ready->value) {
    // Tenant is ready
}

// Update status
$tenant->status = TenantStatusEnum::Ready->value;
$tenant->save();
```

### SubscriptionStatusEnum

Represents subscription status.

**Location:** `app/Enum/Subscription/SubscriptionStatusEnum.php`

**Values:**
```php
case Active = 'active';
case Expired = 'expired';
case Cancelled = 'cancelled';
```

**Usage:**
```php
use App\Enum\Subscription\SubscriptionStatusEnum;

// Get active subscriptions
$active = Subscription::where('status', SubscriptionStatusEnum::Active->value)->get();

// Check if subscription is active
if ($subscription->status === SubscriptionStatusEnum::Active->value) {
    // Subscription is active
}

// Cancel subscription
$subscription->status = SubscriptionStatusEnum::Cancelled->value;
$subscription->save();
```

## Using Enums in Models

### Model Casting

```php
use App\Enum\Global\ActiveTypeEnum;

class User extends Model
{
    protected $casts = [
        'is_active' => ActiveTypeEnum::class,
    ];
}

// Usage
$user = User::find(1);
$user->is_active;  // Returns ActiveTypeEnum::Active or ActiveTypeEnum::InActive

// Check
if ($user->is_active === ActiveTypeEnum::Active) {
    // User is active
}

// Set
$user->is_active = ActiveTypeEnum::InActive;
$user->save();
```

## Using Enums in Validation

```php
use App\Enum\Global\ActiveTypeEnum;
use App\Enum\Global\OtpTypeEnum;

$request->validate([
    'is_active' => [
        'required',
        'in:' . implode(',', array_column(ActiveTypeEnum::cases(), 'value'))
    ],
    'otp_type' => [
        'required',
        'in:' . implode(',', array_column(OtpTypeEnum::cases(), 'value'))
    ]
]);
```

## EnumMethods Trait

All enums use the `EnumMethods` trait which provides helper methods:

```php
use App\Enum\Global\ActiveTypeEnum;

// Get all values
ActiveTypeEnum::values();           // [1, 0]

// Get all names
ActiveTypeEnum::names();            // ['Active', 'InActive']

// Get options for select
ActiveTypeEnum::options();          // [['value' => 1, 'label' => 'Active'], ...]

// Get label for value
ActiveTypeEnum::label(1);           // 'Active'

// Try from value
$enum = ActiveTypeEnum::tryFrom(1); // ActiveTypeEnum::Active
```

## Using Enums in Queries

```php
use App\Enum\Global\ActiveTypeEnum;

// Get active users
$activeUsers = User::where('is_active', ActiveTypeEnum::Active->value)->get();

// Get inactive users
$inactiveUsers = User::where('is_active', ActiveTypeEnum::InActive->value)->get();

// Toggle status
$user->is_active = $user->is_active === ActiveTypeEnum::Active 
    ? ActiveTypeEnum::InActive 
    : ActiveTypeEnum::Active;
$user->save();
```

## Using Enums in API Responses

```php
use App\Http\Resources\UserResource;

class UserResource extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active->value,  // Returns 1 or 0
            'is_active_label' => $this->is_active->name,  // Returns 'Active' or 'InActive'
        ];
    }
}
```
