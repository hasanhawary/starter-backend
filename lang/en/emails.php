<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    */
    // Create admin
    'create_admin_data_title' => 'New Admin Data',
    'create_admin_data_msg' => 'Hello :name,<br><br>
    Your account has been created successfully on :platform_name platform.<br><br>
    <strong>Account Details:</strong><br>
    Email: :email<br>
    Phone: :phone<br>
    Password: :password<br>
    Creation Date: :created_at<br><br>
    If you did not request account creation, please ignore this message.<br><br>
    Best regards, :platform_name team.',

    // Update admin
    'update_admin_data_title' => 'Admin Data Update',
    'update_admin_data_msg' => 'Hello :name,<br><br>
    Your account data has been updated successfully on :platform_name platform.<br><br>
    <strong>Updated Account Details:</strong><br>
    Email: :email<br>
    Phone: :phone<br>
    Password: :password<br>
    Update Date: :updated_at<br><br>
    If you did not request this update, please ignore this message.<br><br>
    Best regards, :platform_name team.',

    /*
    |--------------------------------------------------------------------------
    | OTP Notifications
    |--------------------------------------------------------------------------
    */
    // Login OTP
    'login_otp_title' => 'Login Verification Code',
    'login_otp_msg' => 'Hello :name,<br><br>
    You have requested to log in to your account on :platform_name platform.<br>
    Please use the verification code below.<br>
    Verification code is valid until: <strong>:expires_at</strong>.<br><br>
    If you did not request to log in, please ignore this message safely.<br><br>
    Best regards, :platform_name team.',

    // Reset Password OTP
    'reset_password_otp_title' => 'Password Reset Verification Code',
    'reset_password_otp_msg' => 'Hello :name,<br><br>
    We received a request to reset the password for your account on :platform_name platform.<br>
    Please use the verification code below.<br>
    Verification code is valid until: <strong>:expires_at</strong>.<br><br>
    If you did not request a password reset, please ignore this message safely.<br><br>
    Best regards, :platform_name team.',

    // Verify Email OTP
    'verify_email_otp_title' => 'Email Verification Code',
    'verify_email_otp_msg' => 'Hello :name,<br><br>
    To confirm your email on :platform_name platform, please use the verification code below.<br>
    Verification code is valid until: <strong>:expires_at</strong>.<br><br>
    If you did not request email confirmation, please ignore this message.<br><br>
    Best regards, :platform_name team.',

    // Default OTP
    'default_otp_title' => 'Verification Code',
    'default_otp_msg' => 'Hello :name,<br><br>
    Please use the verification code below to complete the required operation on :platform_name platform.<br>
    Verification code is valid until: <strong>:expires_at</strong>.<br><br>
    If you did not request this action, please ignore this message safely.<br><br>
    Best regards, :platform_name team.',

];
