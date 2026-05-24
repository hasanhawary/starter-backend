<?php

// --- 1. Shared Base Strings (The "Single Source of Truth") ---

// --- 2. Shared Short Messages (For SMS/Realtime/Notify) ---
$createAdminTitle = 'انشاء مشرف جديد';
$createAdminMsg = 'تم إنشاء حسابك بنجاح.';

$updateAdminTitle = 'تحديث بيانات المشرف';
$updateAdminMsg = 'تم تحديث بيانات حسابك بنجاح.';

$loginOtpTitle = 'رمز تحقق تسجيل الدخول';
$loginOtpMsg = 'رمز التحقق الخاص بك لتسجيل الدخول هو: :otp';

$resetOtpTitle = 'رمز تحقق إعادة تعيين كلمة المرور';
$resetOtpMsg = 'رمز التحقق لإعادة تعيين كلمة المرور هو: :otp';

$verifyEmailOtpTitle = 'رمز تحقق البريد الإلكتروني';
$verifyEmailOtpMsg = 'رمز التحقق لتأكيد البريد الإلكتروني هو: :otp';

$defaultOtpTitle = 'رمز التحقق';
$defaultOtpMsg = 'رمز التحقق الخاص بك هو: :otp';

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    */
    'realtime' => [
        'create_admin_data_title' => $createAdminTitle,
        'create_admin_data_msg' => $createAdminMsg,
        'update_admin_data_title' => $updateAdminTitle,
        'update_admin_data_msg' => $updateAdminMsg,
        'login_otp_title' => $loginOtpTitle,
        'login_otp_msg' => $loginOtpMsg,
        'reset_password_otp_title' => $resetOtpTitle,
        'reset_password_otp_msg' => $resetOtpMsg,
        'verify_email_otp_title' => $verifyEmailOtpTitle,
        'verify_email_otp_msg' => $verifyEmailOtpMsg,
        'default_otp_title' => $defaultOtpTitle,
        'default_otp_msg' => $defaultOtpMsg,
    ],

    'notify' => [
        'create_admin_data_title' => $createAdminTitle,
        'create_admin_data_msg' => $createAdminMsg,
        'update_admin_data_title' => $updateAdminTitle,
        'update_admin_data_msg' => $updateAdminMsg,
        'login_otp_title' => $loginOtpTitle,
        'login_otp_msg' => $loginOtpMsg,
        'reset_password_otp_title' => $resetOtpTitle,
        'reset_password_otp_msg' => $resetOtpMsg,
        'verify_email_otp_title' => $verifyEmailOtpTitle,
        'verify_email_otp_msg' => $verifyEmailOtpMsg,
        'default_otp_title' => $defaultOtpTitle,
        'default_otp_msg' => $defaultOtpMsg,
    ],

    'sms' => [
        'create_admin_data_title' => $createAdminTitle,
        'create_admin_data_msg' => $createAdminMsg,
        'update_admin_data_title' => $updateAdminTitle,
        'update_admin_data_msg' => $updateAdminMsg,
        'login_otp_title' => $loginOtpTitle,
        'login_otp_msg' => $loginOtpMsg,
        'reset_password_otp_title' => $resetOtpTitle,
        'reset_password_otp_msg' => $resetOtpMsg,
        'verify_email_otp_title' => $verifyEmailOtpTitle,
        'verify_email_otp_msg' => $verifyEmailOtpMsg,
        'default_otp_title' => $defaultOtpTitle,
        'default_otp_msg' => $defaultOtpMsg,
    ],

    'email' => [

        // Create admin
        'create_admin_data_title' => 'بيانات المشرف الجديد',
        'create_admin_data_msg' => 'مرحبًا :name،<br><br>
        تم إنشاء حسابك على منصة :platform_name بنجاح.<br><br>
        <strong>تفاصيل الحساب:</strong><br>
        البريد الإلكتروني: :email<br>
        الهاتف: :phone<br>
        كلمة المرور: :password<br>
        تاريخ الإنشاء: :created_at<br><br>
        إذا لم تطلب إنشاء الحساب، يرجى تجاهل هذه الرسالة.<br><br>
        مع تحيات فريق :platform_name.',

        // Update admin
        'update_admin_data_title' => 'تحديث بيانات المشرف',
        'update_admin_data_msg' => 'مرحبًا :name،<br><br>
        تم تحديث بيانات حسابك على منصة :platform_name بنجاح.<br><br>
        <strong>تفاصيل الحساب المحدثة:</strong><br>
        البريد الإلكتروني: :email<br>
        الهاتف: :phone<br>
        كلمة المرور: :password<br>
        تاريخ التحديث: :updated_at<br><br>
        إذا لم تطلب هذا التحديث، يرجى تجاهل هذه الرسالة.<br><br>
        مع تحيات فريق :platform_name.',

        /*
        |--------------------------------------------------------------------------
        | OTP Notifications
        |--------------------------------------------------------------------------
        */
        // Login OTP
        'login_otp_title' => 'رمز تحقق تسجيل الدخول',
        'login_otp_msg' => 'مرحبًا :name،<br><br>
        لقد طلبت تسجيل الدخول إلى حسابك على منصة :platform_name.<br>
        يرجى استخدام رمز التحقق أدناه.<br>
        رمز التحقق صالح حتى: <strong>:expires_at</strong>.<br><br>
        إذا لم تطلب تسجيل الدخول، يرجى تجاهل هذه الرسالة بأمان.<br><br>
        مع تحيات فريق :platform_name.',

        // Reset Password OTP
        'reset_password_otp_title' => 'رمز تحقق إعادة تعيين كلمة المرور',
        'reset_password_otp_msg' => 'مرحبًا :name،<br><br>
        تلقينا طلبًا لإعادة تعيين كلمة المرور لحسابك على منصة :platform_name.<br>
        يرجى استخدام رمز التحقق أدناه.<br>
        رمز التحقق صالح حتى: <strong>:expires_at</strong>.<br><br>
        إذا لم تطلب إعادة التعيين، يرجى تجاهل هذه الرسالة بأمان.<br><br>
        مع تحيات فريق :platform_name.',

        // Verify Email OTP
        'verify_email_otp_title' => 'رمز تحقق البريد الإلكتروني',
        'verify_email_otp_msg' => 'مرحبًا :name،<br><br>
        لتأكيد بريدك الإلكتروني على منصة :platform_name، يرجى استخدام رمز التحقق أدناه.<br>
        رمز التحقق صالح حتى: <strong>:expires_at</strong>.<br><br>
        إذا لم تطلب تأكيد البريد الإلكتروني، يرجى تجاهل هذه الرسالة.<br><br>
        مع تحيات فريق :platform_name.',

        // Default OTP
        'default_otp_title' => 'رمز التحقق',
        'default_otp_msg' => 'مرحبًا :name،<br><br>
        يرجى استخدام رمز التحقق أدناه لإكمال العملية المطلوبة على منصة :platform_name.<br>
        رمز التحقق صالح حتى: <strong>:expires_at</strong>.<br><br>
        إذا لم تطلب هذا الإجراء، يرجى تجاهل هذه الرسالة بأمان.<br><br>
        مع تحيات فريق :platform_name.',
    ],

    'test_email_credentials' => 'اختبار إعدادات البريد الإلكتروني',

];
