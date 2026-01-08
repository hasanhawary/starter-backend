<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    */
    // Create admin
    'create_admin_data_title' => 'بيانات المشرف الجديد',
    'create_admin_data_msg' => 'مرحبًا :name، تم إنشاء حسابك على منصة واكب.<br><br>
    البريد الإلكتروني: :email<br>
    الهاتف: :phone<br>
    كلمة المرور: :password<br>
    تاريخ الإنشاء: :created_at<br><br>
    مع تحيات فريق واكب',

    // Update admin
    'update_admin_data_title' => 'تحديث بيانات المشرف',
    'update_admin_data_msg' => 'مرحبًا :name، تم تحديث بيانات حسابك على منصة واكب.<br><br>
    البريد الإلكتروني: :email<br>
    الهاتف: :phone<br>
    كلمة المرور: :password<br>
    تاريخ التحديث: :updated_at<br><br>
    إذا لم تطلب التحديث، يرجى تجاهل هذا البريد.<br><br>
    مع تحيات فريق واكب',

        /*
        |--------------------------------------------------------------------------
        | OTP Notifications
        |--------------------------------------------------------------------------
        */
        // Login OTP
        'login_otp_title' => 'رمز تحقق تسجيل الدخول',
        'login_otp_msg' => 'مرحبًا،<br><br>
    لقد طلبت تسجيل الدخول إلى حسابك على المنصة.<br>
    رمز التحقق الخاص بك هو: <strong>:otp</strong><br>
    صالح حتى: <strong>:expires_at</strong><br><br>
    إذا لم تطلب تسجيل الدخول، يرجى تجاهل هذه الرسالة.<br><br>
    مع تحيات فريق المنصة.',

        // Reset Password OTP
        'reset_password_otp_title' => 'رمز تحقق إعادة تعيين كلمة المرور',
        'reset_password_otp_msg' => 'مرحبًا،<br><br>
    لقد طلبت إعادة تعيين كلمة المرور لحسابك.<br>
    رمز التحقق الخاص بك هو: <strong>:otp</strong><br>
    صالح حتى: <strong>:expires_at</strong><br><br>
    إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة.<br><br>
    مع تحيات فريق المنصة.',

        // Verify Email OTP
        'verify_email_otp_title' => 'رمز تحقق البريد الإلكتروني',
        'verify_email_otp_msg' => 'مرحبًا،<br><br>
    لتأكيد بريدك الإلكتروني، يرجى استخدام رمز التحقق التالي:<br>
    <strong>:otp</strong><br>
    صالح حتى: <strong>:expires_at</strong><br><br>
    إذا لم تطلب تأكيد البريد الإلكتروني، يرجى تجاهل هذه الرسالة.<br><br>
    مع تحيات فريق المنصة.',

        // Default OTP
        'default_otp_title' => 'رمز التحقق',
        'default_otp_msg' => 'مرحبًا،<br><br>
    رمز التحقق الخاص بك هو: <strong>:otp</strong><br>
    صالح حتى: <strong>:expires_at</strong><br><br>
    إذا لم تطلب هذا الإجراء، يرجى تجاهل هذه الرسالة.<br><br>
    مع تحيات فريق المنصة.',

];
