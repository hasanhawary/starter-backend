---
title: Mail Classes & Email Notifications
description: Sending emails with Laravel Mailable classes
---

# Mail Classes & Email Notifications

This guide documents the mail classes used for sending emails throughout the application.

## Overview

Mail classes encapsulate email logic and are located in `app/Mail/`. They extend Laravel's `Mailable` class and support both queued and synchronous sending.

## Available Mail Classes

### BasicMail

**Location:** `app/Mail/BasicMail.php`

Queued mail class for sending emails with custom data and templates.

**Constructor:**
```php
public function __construct(
    public Authenticatable|null $user,
    public array $data
)
```

**Parameters:**
- `$user` - User model (optional)
- `$data` - Email data including title, msg, url, urlText, etc.

**Usage:**

```php
use App\Mail\BasicMail;
use Illuminate\Support\Facades\Mail;

// Simple email
Mail::to('user@example.com')->send(
    new BasicMail(
        user: $user,
        data: [
            'title' => 'Welcome to Our Platform',
            'msg' => 'Thank you for signing up!'
        ]
    )
);

// Email with action URL
Mail::to('user@example.com')->send(
    new BasicMail(
        user: $user,
        data: [
            'title' => 'Password Reset',
            'msg' => 'Click the link below to reset your password',
            'url' => '/reset-password/token',
            'urlText' => 'Reset Password'
        ]
    )
);
```

---

**Features:**
- Queued by default (runs in background)
- Uses `emails.basic_mail` template
- Includes brand settings in template
- Automatic subject translation via `transWithParams()`
- Supports multi-language titles

---

**Configuration:**
```php
// config/mail.php
'default' => env('MAIL_DRIVER', 'smtp'),
'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
    'name' => env('MAIL_FROM_NAME', 'Application'),
],
'queue' => [
    'connection' => env('QUEUE_CONNECTION', 'database'),
    'queue' => env('QUEUE_NAME', 'default'),
],
```

---

### BasicMailWithoutQueue

**Location:** `app/Mail/BasicMailWithoutQueue.php`

Synchronous mail class for immediate email sending.

**Constructor:**
```php
public function __construct(
    public Authenticatable|null $user,
    public array $data
)
```

**Usage:**

```php
use App\Mail\BasicMailWithoutQueue;
use Illuminate\Support\Facades\Mail;

// Send immediately (not queued)
Mail::to('user@example.com')->send(
    new BasicMailWithoutQueue(
        user: $user,
        data: [
            'title' => 'Urgent: Account Verification',
            'msg' => 'Please verify your account immediately',
            'url' => '/verify/token',
            'urlText' => 'Verify Account'
        ]
    )
);
```

---

**When to Use:**
- Critical emails that need immediate delivery
- Verification emails
- Account alerts
- Time-sensitive notifications

---

## Sending Emails in Controllers

### Using Mail Facade

```php
<?php

namespace App\Http\Controllers\API\Global\Auth;

use App\Mail\BasicMail;
use App\Services\Auth\OTPService;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function __construct(
        private OTPService $otpService
    ) {}

    public function sendResetOtp(SendOtpRequest $request)
    {
        try {
            // Generate and send OTP
            $otp = $this->otpService->send($request, 'reset_password');

            return successResponse(null, 'OTP sent to email');
        } catch (Exception $e) {
            return failResponse($e->getMessage(), 422);
        }
    }
}
```

---

## Sending Emails in Services

### Using Dependency Injection

```php
<?php

namespace App\Services\Auth;

use App\Mail\BasicMail;
use Illuminate\Support\Facades\Mail;

class ResetPasswordService extends BaseAuthService
{
    public function __construct(protected OTPService $otpService)
    {
    }

    public function reset(ResetPasswordRequest $request): bool
    {
        // Verify OTP and reset password
        $user = $this->otpService->verify($request, 'reset_password');

        // Password is updated in OTPService
        return true;
    }
}
```

---

## Sending Emails in Jobs

### Queued Email Job

```php
<?php

namespace App\Jobs;

use App\Mail\BasicMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $user,
        public array $data
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new BasicMail($this->user, $this->data)
        );
    }
}
```

---

## Email Templates

### Creating Email Templates

**Location:** `resources/views/emails/`

**Example: Password Reset Template**

```blade
<!-- resources/views/emails/password-reset.blade.php -->
@component('mail::message')
# Password Reset Request

Hello {{ $user_name }},

You requested a password reset for your account. Click the button below to reset your password.

@component('mail::button', ['url' => $reset_link])
Reset Password
@endcomponent

This link will expire in {{ $expiry_hours }} hours.

If you didn't request this, please ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

---

**Example: Welcome Email Template**

```blade
<!-- resources/views/emails/welcome.blade.php -->
@component('mail::message')
# Welcome to {{ config('app.name') }}

Hello {{ $user_name }},

Thank you for signing up! Your account has been created successfully.

**Account Details:**
- Email: {{ $email }}
- Username: {{ $username }}

@component('mail::button', ['url' => $login_url])
Login to Your Account
@endcomponent

If you have any questions, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

---

## Sending Emails with Notifications

### Using Notification Class

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserCreatedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Welcome to Our Platform')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your account has been created successfully.')
            ->action('Login', url('/login'))
            ->line('Thank you for using our application!');
    }
}
```

---

### Sending Notification

```php
use App\Notifications\UserCreatedNotification;

$user->notify(new UserCreatedNotification());
```

---

## Email Configuration

### Environment Variables

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Application Name"

QUEUE_CONNECTION=database
QUEUE_NAME=default
```

---

### Mail Configuration File

```php
// config/mail.php
return [
    'default' => env('MAIL_DRIVER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST'),
            'port' => env('MAIL_PORT'),
            'encryption' => env('MAIL_ENCRYPTION'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
];
```

---

## Testing Emails

### Using Mailtrap

1. Create account at [mailtrap.io](https://mailtrap.io)
2. Get SMTP credentials
3. Update `.env`:

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
```

---

### Testing in Code

```php
use Illuminate\Support\Facades\Mail;

// Fake mail sending
Mail::fake();

// Send email
Mail::to('user@example.com')->send(new BasicMail(...));

// Assert email was sent
Mail::assertSent(BasicMail::class);

// Assert email was sent to specific address
Mail::assertSent(BasicMail::class, function ($mail) {
    return $mail->hasTo('user@example.com');
});
```

---

## Best Practices

1. **Use queued mail** - Queue emails for better performance
2. **Use templates** - Create reusable email templates
3. **Pass data safely** - Use array data instead of concatenation
4. **Handle failures** - Implement retry logic for failed emails
5. **Test emails** - Test email sending in development
6. **Use markdown** - Use Laravel's markdown mail for consistency
7. **Localize emails** - Send emails in user's preferred language
8. **Monitor delivery** - Track email delivery status

---

## Common Email Scenarios

### Welcome Email

```php
use App\Services\Global\NotificationService;

NotificationService::resolve(
    user: $user,
    data: [
        'title' => 'welcome_title',
        'msg' => 'welcome_msg|name=' . $user->name,
        'url' => '/dashboard',
        'urlText' => 'Go to Dashboard'
    ],
    types: ['email', 'notify']
);
```

---

### Password Reset OTP Email

```php
use App\Services\Auth\OTPService;

$service = app(OTPService::class);
$otp = $service->send(new SendOtpRequest(['email' => $user->email]), 'reset_password');
// OTP is automatically sent via email
```

---

### OTP Email

```php
use App\Services\Auth\OTPService;

$service = app(OTPService::class);
$otp = $service->send(new SendOtpRequest(['email' => $user->email]), 'login');
// OTP is automatically sent via email
```

---

### Notification Email

```php
use App\Services\Global\NotificationService;

NotificationService::resolve(
    user: $user,
    data: [
        'title' => 'notification_title',
        'msg' => 'notification_msg',
        'url' => '/notifications',
        'urlText' => 'View Notification'
    ],
    types: ['email']
);
```

---

## See Also

- [Notifications](/guide/features/notifications) — Notification system
- [Jobs](/guide/features/jobs) — Background jobs
- [Services](/guide/features/services) — Business logic services
- [Events](/guide/features/events) — Event system
