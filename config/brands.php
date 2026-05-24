<?php

return [
    'default_brand' => env('DEFAULT_BRAND', 'default'),
    'template' => [
        'general' => [
            'info' => [
                ['key' => 'name', 'type' => 'text', 'label' => ['ar' => 'اسم الشركة', 'en' => 'Company name'], 'placeholder' => ['ar' => 'مثال: اسم الشركة', 'en' => 'Example: Company name'], 'is_multi_lang' => true],
                ['key' => 'copyright_name', 'type' => 'text', 'label' => ['ar' => 'حقوق الطبع والنشر', 'en' => 'Copyright name'], 'placeholder' => ['ar' => 'مثال: © 2024 الشركة', 'en' => 'Example: © 2024 Company'], 'is_multi_lang' => true],
                ['key' => 'website_address', 'type' => 'text', 'label' => ['ar' => 'رابط الموقع', 'en' => 'Website URL'], 'placeholder' => ['ar' => 'مثال: https://example.com', 'en' => 'Example: https://example.com'], 'is_multi_lang' => false],
                ['key' => 'website_description', 'type' => 'textarea', 'label' => ['ar' => 'وصف الموقع', 'en' => 'Website description'], 'placeholder' => ['ar' => 'مثال: نحن شركة متخصصة في...', 'en' => 'Example: We are a company specialized in...'], 'is_multi_lang' => true],
                ['key' => 'meta_description', 'type' => 'textarea', 'label' => ['ar' => 'وصف الميتا', 'en' => 'Meta description'], 'placeholder' => ['ar' => 'مثال: وصف قصير للموقع (160 حرف)', 'en' => 'Example: Short description for SEO (160 chars)'], 'is_multi_lang' => true],
            ],
            'contact' => [
                ['key' => 'contact_email', 'type' => 'text', 'label' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email'], 'placeholder' => ['ar' => 'مثال: support@example.com', 'en' => 'Example: support@example.com'], 'is_multi_lang' => false],
                ['key' => 'contact_phone', 'type' => 'text', 'label' => ['ar' => 'رقم الهاتف', 'en' => 'Phone'], 'placeholder' => ['ar' => 'مثال: +966 (12) 3456-789', 'en' => 'Example: +966 (12) 3456-789'], 'is_multi_lang' => false],
                ['key' => 'contact_address', 'type' => 'text', 'label' => ['ar' => 'العنوان', 'en' => 'Address'], 'placeholder' => ['ar' => 'مثال: شارع الملك، المدينة', 'en' => 'Example: King Street, City'], 'is_multi_lang' => true],
            ],
            'social' => [
                ['key' => 'instagram', 'type' => 'text', 'label' => ['ar' => 'انستجرام', 'en' => 'Instagram'], 'placeholder' => ['ar' => 'مثال: https://instagram.com/username', 'en' => 'Example: https://instagram.com/username'], 'is_multi_lang' => false],
                ['key' => 'facebook', 'type' => 'text', 'label' => ['ar' => 'فيسبوك', 'en' => 'Facebook'], 'placeholder' => ['ar' => 'مثال: https://facebook.com/pagename', 'en' => 'Example: https://facebook.com/pagename'], 'is_multi_lang' => false],
                ['key' => 'linkedin', 'type' => 'text', 'label' => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'], 'placeholder' => ['ar' => 'مثال: https://linkedin.com/company/name', 'en' => 'Example: https://linkedin.com/company/name'], 'is_multi_lang' => false],
                ['key' => 'twitter', 'type' => 'text', 'label' => ['ar' => 'تويتر', 'en' => 'Twitter'], 'placeholder' => ['ar' => 'مثال: https://twitter.com/username', 'en' => 'Example: https://twitter.com/username'], 'is_multi_lang' => false],
                ['key' => 'youtube', 'type' => 'text', 'label' => ['ar' => 'يوتيوب', 'en' => 'Youtube'], 'placeholder' => ['ar' => 'مثال: https://youtube.com/c/channelname', 'en' => 'Example: https://youtube.com/c/channelname'], 'is_multi_lang' => false],
            ],
        ],

        'properties' => [
            ['key' => 'website_logo_large', 'type' => 'imageUploader', 'label' => ['ar' => 'شعار كبير', 'en' => 'Large logo'], 'placeholder' => ['ar' => 'شعار كبير للويب', 'en' => 'Large website logo'], 'is_multi_lang' => false],
            ['key' => 'website_dark_logo_large', 'type' => 'imageUploader', 'label' => ['ar' => 'شعار كبير داكن', 'en' => 'Large dark logo'], 'placeholder' => ['ar' => 'شعار كبير للويب النسخة الداكنة', 'en' => 'Large dark logo'], 'is_multi_lang' => false],
            ['key' => 'website_logo_small', 'type' => 'imageUploader', 'label' => ['ar' => 'شعار صغير', 'en' => 'Small logo'], 'placeholder' => ['ar' => 'شعار صغير للويب', 'en' => 'Small website logo'], 'is_multi_lang' => false],
            ['key' => 'website_dark_logo_small', 'type' => 'imageUploader', 'label' => ['ar' => 'شعار صغير داكن', 'en' => 'Small dark logo'], 'placeholder' => ['ar' => 'شعار صغير للويب النسخة الداكنة', 'en' => 'Small dark logo'], 'is_multi_lang' => false],
            ['key' => 'website_favorite_place_icon', 'type' => 'imageUploader', 'label' => ['ar' => 'أيقونة المفضلة', 'en' => 'Favicon'], 'placeholder' => ['ar' => 'أيقونة المفضلة', 'en' => 'Favicon icon'], 'is_multi_lang' => false],
        ],

        'notifications' => [
            ['key' => 'mail_support', 'type' => 'switchbox', 'label' => ['ar' => 'إشعارات البريد', 'en' => 'Email notifications'], 'placeholder' => ['ar' => 'تمكين إشعارات البريد الإلكتروني', 'en' => 'Enable email notifications'], 'is_multi_lang' => false],
            ['key' => 'sms_support', 'type' => 'switchbox', 'label' => ['ar' => 'إشعارات الرسائل', 'en' => 'SMS notifications'], 'placeholder' => ['ar' => 'تمكين إشعارات الرسائل القصيرة', 'en' => 'Enable SMS notifications'], 'is_multi_lang' => false],
            ['key' => 'push_support', 'type' => 'switchbox', 'label' => ['ar' => 'إشعارات فورية', 'en' => 'Push notifications'], 'placeholder' => ['ar' => 'تمكين الإشعارات الفورية', 'en' => 'Enable push notifications'], 'is_multi_lang' => false],
            ['key' => 'real_time_support', 'type' => 'switchbox', 'label' => ['ar' => 'إشعارات حية', 'en' => 'Realtime notifications'], 'placeholder' => ['ar' => 'تمكين الإشعارات الحية', 'en' => 'Enable realtime notifications'], 'is_multi_lang' => false],
        ],

        'theme' => [
            'colors' => [
                ['key' => 'primary_color', 'type' => 'text', 'label' => ['ar' => 'اللون الرئيسي', 'en' => 'Primary color'], 'placeholder' => ['ar' => 'مثال: #000000', 'en' => 'Example: #000000'], 'is_multi_lang' => false],
                ['key' => 'secondary_color', 'type' => 'text', 'label' => ['ar' => 'اللون الثانوي', 'en' => 'Secondary color'], 'placeholder' => ['ar' => 'مثال: #FFFFFF', 'en' => 'Example: #FFFFFF'], 'is_multi_lang' => false],
                ['key' => 'text_color', 'type' => 'text', 'label' => ['ar' => 'لون النص', 'en' => 'Text color'], 'placeholder' => ['ar' => 'مثال: #333333', 'en' => 'Example: #333333'], 'is_multi_lang' => false],
                ['key' => 'muted_color', 'type' => 'text', 'label' => ['ar' => 'لون النص الثانوي', 'en' => 'Muted color'], 'placeholder' => ['ar' => 'مثال: #999999', 'en' => 'Example: #999999'], 'is_multi_lang' => false],
            ],
            'font' => [
                ['key' => 'font_family_ar', 'type' => 'text', 'label' => ['ar' => 'خط عربي', 'en' => 'Arabic font'], 'placeholder' => ['ar' => 'اكتب خط عربي', 'en' => 'Write Arabic font'], 'is_multi_lang' => true],
                ['key' => 'font_family_en', 'type' => 'text', 'label' => ['ar' => 'خط انجليزي', 'en' => 'English font'], 'placeholder' => ['ar' => 'اكتب خط انجليزي', 'en' => 'Write English font'], 'is_multi_lang' => true],
                ['key' => 'font_size', 'type' => 'text', 'label' => ['ar' => 'حجم الخط', 'en' => 'Font size'], 'placeholder' => ['ar' => 'اكتب حجم الخط', 'en' => 'Write font size'], 'is_multi_lang' => false],
            ],
        ],

        'mail_templates' => [
            'otp' => [
                ['key' => 'otp_bg', 'type' => 'text', 'label' => ['ar' => 'خلفية OTP', 'en' => 'OTP background'], 'placeholder' => ['ar' => 'مثال: #F4F4F5', 'en' => 'Example: #F4F4F5'], 'is_multi_lang' => false],
                ['key' => 'otp_border_color', 'type' => 'text', 'label' => ['ar' => 'حد OTP', 'en' => 'OTP border'], 'placeholder' => ['ar' => 'مثال: #D1D5DB', 'en' => 'Example: #D1D5DB'], 'is_multi_lang' => false],
                ['key' => 'otp_font_size', 'type' => 'text', 'label' => ['ar' => 'حجم خط OTP', 'en' => 'OTP font size'], 'placeholder' => ['ar' => 'مثال: 18px', 'en' => 'Example: 18px'], 'is_multi_lang' => false],
                ['key' => 'otp_letter_spacing', 'type' => 'text', 'label' => ['ar' => 'تباعد أحرف OTP', 'en' => 'OTP letter spacing'], 'placeholder' => ['ar' => 'مثال: 4px', 'en' => 'Example: 4px'], 'is_multi_lang' => false],
                ['key' => 'otp_text_color', 'type' => 'text', 'label' => ['ar' => 'لون النص', 'en' => 'OTP text color'], 'placeholder' => ['ar' => 'مثال: #111827', 'en' => 'Example: #111827'], 'is_multi_lang' => false],
            ],
            'theme' => [
                ['key' => 'button_bg_color', 'type' => 'text', 'label' => ['ar' => 'خلفية الزر', 'en' => 'Button background'], 'placeholder' => ['ar' => 'مثال: #000000', 'en' => 'Example: #000000'], 'is_multi_lang' => false],
                ['key' => 'button_text_color', 'type' => 'text', 'label' => ['ar' => 'لون نص الزر', 'en' => 'Button text color'], 'placeholder' => ['ar' => 'مثال: #FFFFFF', 'en' => 'Example: #FFFFFF'], 'is_multi_lang' => false],
                ['key' => 'header_image', 'type' => 'imageUploader', 'label' => ['ar' => 'صورة الهيدر', 'en' => 'Header image'], 'placeholder' => ['ar' => 'صورة الهيدر', 'en' => 'Header image'], 'is_multi_lang' => false],
                ['key' => 'header_text', 'type' => 'textarea', 'label' => ['ar' => 'نص الهيدر', 'en' => 'Header text'], 'placeholder' => ['ar' => 'نص الهيدر', 'en' => 'Header text'], 'is_multi_lang' => true],
                ['key' => 'footer_image', 'type' => 'imageUploader', 'label' => ['ar' => 'صورة الفوتر', 'en' => 'Footer image'], 'placeholder' => ['ar' => 'صورة الفوتر', 'en' => 'Footer image'], 'is_multi_lang' => false],
                ['key' => 'footer_text', 'type' => 'textarea', 'label' => ['ar' => 'نص الفوتر', 'en' => 'Footer text'], 'placeholder' => ['ar' => 'نص الفوتر', 'en' => 'Footer text'], 'is_multi_lang' => true],
            ],
        ],

        'config' => [
            'mail' => [
                ['key' => 'mailer', 'type' => 'text', 'label' => ['ar' => 'نوع البريد', 'en' => 'Mail driver'], 'placeholder' => ['ar' => 'الخيارات: smtp, sendmail, mailgun, ses, postmark, resend', 'en' => 'Options: smtp, sendmail, mailgun, ses, postmark, resend'], 'is_multi_lang' => false],
                ['key' => 'host', 'type' => 'text', 'label' => ['ar' => 'خادم البريد', 'en' => 'Mail host'], 'placeholder' => ['ar' => 'مثال: smtp.gmail.com, smtp.office365.com', 'en' => 'Example: smtp.gmail.com, smtp.office365.com'], 'is_multi_lang' => false],
                ['key' => 'port', 'type' => 'text', 'label' => ['ar' => 'منفذ البريد', 'en' => 'Mail port'], 'placeholder' => ['ar' => 'الخيارات: 25, 465, 587, 2525', 'en' => 'Options: 25, 465, 587, 2525'], 'is_multi_lang' => false],
                ['key' => 'username', 'type' => 'text', 'label' => ['ar' => 'اسم المستخدم', 'en' => 'Mail username'], 'placeholder' => ['ar' => 'مثال: your-email@gmail.com', 'en' => 'Example: your-email@gmail.com'], 'is_multi_lang' => false],
                ['key' => 'password', 'type' => 'text', 'label' => ['ar' => 'كلمة المرور', 'en' => 'Mail password'], 'placeholder' => ['ar' => 'مثال: your-app-password', 'en' => 'Example: your-app-password'], 'is_multi_lang' => false],
                ['key' => 'encryption', 'type' => 'text', 'label' => ['ar' => 'نوع التشفير', 'en' => 'Mail encryption'], 'placeholder' => ['ar' => 'الخيارات: null, tls, ssl', 'en' => 'Options: null, tls, ssl'], 'is_multi_lang' => false],
                ['key' => 'from_address', 'type' => 'text', 'label' => ['ar' => 'البريد المرسل', 'en' => 'From address'], 'placeholder' => ['ar' => 'مثال: noreply@example.com', 'en' => 'Example: noreply@example.com'], 'is_multi_lang' => false],
                ['key' => 'from_name', 'type' => 'text', 'label' => ['ar' => 'اسم المرسل', 'en' => 'From name'], 'placeholder' => ['ar' => 'مثال: اسم الشركة', 'en' => 'Example: Company name'], 'is_multi_lang' => true],
            ],
            'sms' => [
                ['key' => 'gateway', 'type' => 'text', 'label' => ['ar' => 'بوابة الرسائل', 'en' => 'SMS gateway'], 'placeholder' => ['ar' => 'الخيارات: twilio, nexmo, aws-sns, custom', 'en' => 'Options: twilio, nexmo, aws-sns, custom'], 'is_multi_lang' => false],
                ['key' => 'username', 'type' => 'text', 'label' => ['ar' => 'اسم المستخدم', 'en' => 'Username'], 'placeholder' => ['ar' => 'مثال: your-account-sid', 'en' => 'Example: your-account-sid'], 'is_multi_lang' => false],
                ['key' => 'password', 'type' => 'text', 'label' => ['ar' => 'كلمة المرور', 'en' => 'Password'], 'placeholder' => ['ar' => 'مثال: your-auth-token', 'en' => 'Example: your-auth-token'], 'is_multi_lang' => false],
                ['key' => 'sender_id', 'type' => 'text', 'label' => ['ar' => 'اسم المرسل', 'en' => 'Sender ID'], 'placeholder' => ['ar' => 'مثال: YourBrand أو 1234567890', 'en' => 'Example: YourBrand or 1234567890'], 'is_multi_lang' => false],
            ],
            'ldap' => [
                ['key' => 'host', 'type' => 'text', 'label' => ['ar' => 'خادم LDAP', 'en' => 'LDAP host'], 'placeholder' => ['ar' => 'مثال: ldap.example.com', 'en' => 'Example: ldap.example.com'], 'is_multi_lang' => false],
                ['key' => 'port', 'type' => 'text', 'label' => ['ar' => 'منفذ LDAP', 'en' => 'LDAP port'], 'placeholder' => ['ar' => 'الخيارات: 389 (عادي), 636 (SSL)', 'en' => 'Options: 389 (standard), 636 (SSL)'], 'is_multi_lang' => false],
                ['key' => 'base_dn', 'type' => 'text', 'label' => ['ar' => 'LDAP Base DN', 'en' => 'LDAP Base DN'], 'placeholder' => ['ar' => 'مثال: dc=example,dc=com', 'en' => 'Example: dc=example,dc=com'], 'is_multi_lang' => false],
                ['key' => 'username', 'type' => 'text', 'label' => ['ar' => 'اسم المستخدم', 'en' => 'LDAP username'], 'placeholder' => ['ar' => 'مثال: cn=admin,dc=example,dc=com', 'en' => 'Example: cn=admin,dc=example,dc=com'], 'is_multi_lang' => false],
                ['key' => 'password', 'type' => 'text', 'label' => ['ar' => 'كلمة المرور', 'en' => 'LDAP password'], 'placeholder' => ['ar' => 'مثال: ldap-password', 'en' => 'Example: ldap-password'], 'is_multi_lang' => false],
            ],
            'reverb' => [
                ['key' => 'enabled', 'type' => 'switchbox', 'label' => ['ar' => 'تفعيل البث المباشر', 'en' => 'Enable Realtime'], 'placeholder' => ['ar' => 'تفعيل/تعطيل', 'en' => 'Enable/Disable'], 'is_multi_lang' => false],
                ['key' => 'app_id', 'type' => 'text', 'label' => ['ar' => 'معرف التطبيق', 'en' => 'App ID'], 'placeholder' => ['ar' => 'مثال: 1080194', 'en' => 'Example: 1080194'], 'is_multi_lang' => false],
                ['key' => 'app_key', 'type' => 'text', 'label' => ['ar' => 'مفتاح التطبيق', 'en' => 'App Key'], 'placeholder' => ['ar' => 'مثال: bae3160ce349d284eace', 'en' => 'Example: bae3160ce349d284eace'], 'is_multi_lang' => false],
                ['key' => 'app_secret', 'type' => 'text', 'label' => ['ar' => 'سر التطبيق', 'en' => 'App Secret'], 'placeholder' => ['ar' => 'مثال: 976e5b64127df42af8b6', 'en' => 'Example: 976e5b64127df42af8b6'], 'is_multi_lang' => false],
                ['key' => 'host', 'type' => 'text', 'label' => ['ar' => 'المضيف', 'en' => 'Host'], 'placeholder' => ['ar' => 'مثال: reverb.example.com أو localhost', 'en' => 'Example: reverb.example.com or localhost'], 'is_multi_lang' => false],
                ['key' => 'port', 'type' => 'text', 'label' => ['ar' => 'المنفذ', 'en' => 'Port'], 'placeholder' => ['ar' => 'الخيارات: 8080, 9000, 3000', 'en' => 'Options: 8080, 9000, 3000'], 'is_multi_lang' => false],
                ['key' => 'scheme', 'type' => 'text', 'label' => ['ar' => 'البروتوكول', 'en' => 'Scheme'], 'placeholder' => ['ar' => 'الخيارات: http, https, ws, wss', 'en' => 'Options: http, https, ws, wss'], 'is_multi_lang' => false],
            ],
        ],
    ],
];
