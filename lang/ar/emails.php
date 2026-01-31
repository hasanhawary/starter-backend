<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    */
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


];
