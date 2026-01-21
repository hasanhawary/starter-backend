<?php

return [

    'default_brand' => env('DEFAULT_BRAND', 'wakeb'),

    'brands' => [

        'wakeb' => [

            'general' => [
                'info' => [
                    ['key' => 'name', 'value' => ['ar' => 'واكب', 'en' => 'Wakeb'], 'type' => 'text', 'placeholder' => ['ar' => 'اسم الشركة هنا', 'en' => 'Enter company name'], 'label' => ['ar' => 'اسم الشركة', 'en' => 'Company name'], 'is_multi_lang' => true],
                    ['key' => 'copyright_name', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'حقوق الطبع والنشر', 'en' => 'Copyright name'], 'label' => ['ar' => 'حقوق الطبع والنشر', 'en' => 'Copyright name'], 'is_multi_lang' => true],
                    ['key' => 'website_address', 'value' => 'https://wakeb.tech', 'type' => 'text', 'placeholder' => ['ar' => 'رابط الموقع الإلكتروني', 'en' => 'Website URL'], 'label' => ['ar' => 'رابط الموقع', 'en' => 'Website URL'], 'is_multi_lang' => false],
                    ['key' => 'website_description', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'وصف الموقع بالعربية', 'en' => 'Website description in English'], 'label' => ['ar' => 'وصف الموقع', 'en' => 'Website description'], 'is_multi_lang' => true],
                    ['key' => 'meta_description', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'وصف الميتا بالعربية', 'en' => 'Meta description in English'], 'label' => ['ar' => 'وصف الميتا', 'en' => 'Meta description'], 'is_multi_lang' => true],
                ],
                'contact' => [
                    ['key' => 'contact_email', 'value' => 'info@wakeb.tech', 'type' => 'text', 'placeholder' => ['ar' => 'البريد الإلكتروني للدعم', 'en' => 'Support email'], 'label' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email'], 'is_multi_lang' => false],
                    ['key' => 'contact_phone', 'value' => '+966123456789', 'type' => 'text', 'placeholder' => ['ar' => 'رقم الهاتف', 'en' => 'Phone number'], 'label' => ['ar' => 'رقم الهاتف', 'en' => 'Phone'], 'is_multi_lang' => false],
                    ['key' => 'contact_address', 'value' => 'King Abdullah Street, Riyadh, KSA', 'type' => 'text', 'placeholder' => ['ar' => 'العنوان الكامل', 'en' => 'Full address'], 'label' => ['ar' => 'العنوان', 'en' => 'Address'], 'is_multi_lang' => true],
                ],
            ],

            'properties' => [
                'logos' => [
                    ['key' => 'website_logo_large', 'value' => 'template_design/wakebLg.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار كبير للويب', 'en' => 'Large website logo'], 'label' => ['ar' => 'شعار كبير', 'en' => 'Large logo'], 'is_multi_lang' => false],
                    ['key' => 'website_dark_logo_large', 'value' => 'template_design/wakebLg-dark.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار كبير للويب النسخة الداكنة', 'en' => 'Large dark logo'], 'label' => ['ar' => 'شعار كبير داكن', 'en' => 'Large dark logo'], 'is_multi_lang' => false],
                    ['key' => 'website_logo_small', 'value' => 'template_design/wakebSM.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار صغير للويب', 'en' => 'Small website logo'], 'label' => ['ar' => 'شعار صغير', 'en' => 'Small logo'], 'is_multi_lang' => false],
                    ['key' => 'website_dark_logo_small', 'value' => 'template_design/wakebSM-dark.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار صغير للويب النسخة الداكنة', 'en' => 'Small dark logo'], 'label' => ['ar' => 'شعار صغير داكن', 'en' => 'Small dark logo'], 'is_multi_lang' => false],
                ],
                'icons' => [
                    ['key' => 'website_favorite_place_icon', 'value' => 'template_design/favicon.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'أيقونة المفضلة', 'en' => 'Favicon icon'], 'label' => ['ar' => 'أيقونة المفضلة', 'en' => 'Favicon'], 'is_multi_lang' => false],
                ],
            ],

            'notifications' => [
                ['key' => 'mail_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين إشعارات البريد الإلكتروني', 'en' => 'Enable email notifications'], 'label' => ['ar' => 'إشعارات البريد', 'en' => 'Email notifications'], 'is_multi_lang' => false],
                ['key' => 'sms_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين إشعارات الرسائل القصيرة', 'en' => 'Enable SMS notifications'], 'label' => ['ar' => 'إشعارات الرسائل', 'en' => 'SMS notifications'], 'is_multi_lang' => false],
                ['key' => 'push_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين الإشعارات الفورية', 'en' => 'Enable push notifications'], 'label' => ['ar' => 'إشعارات فورية', 'en' => 'Push notifications'], 'is_multi_lang' => false],
                ['key' => 'real_time_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين الإشعارات الحية', 'en' => 'Enable realtime notifications'], 'label' => ['ar' => 'إشعارات حية', 'en' => 'Realtime notifications'], 'is_multi_lang' => false],
            ],

            'theme' => [
                'colors' => [
                    ['key' => 'primary_color', 'value' => '#03001B', 'type' => 'text', 'placeholder' => ['ar' => 'اللون الرئيسي', 'en' => 'Primary color'], 'label' => ['ar' => 'اللون الرئيسي', 'en' => 'Primary color'], 'is_multi_lang' => false],
                    ['key' => 'secondary_color', 'value' => '#F4F7FF', 'type' => 'text', 'placeholder' => ['ar' => 'اللون الثانوي', 'en' => 'Secondary color'], 'label' => ['ar' => 'اللون الثانوي', 'en' => 'Secondary color'], 'is_multi_lang' => false],
                    ['key' => 'text_color', 'value' => '#1F1F1F', 'type' => 'text', 'placeholder' => ['ar' => 'لون النص الرئيسي', 'en' => 'Text color'], 'label' => ['ar' => 'لون النص', 'en' => 'Text color'], 'is_multi_lang' => false],
                    ['key' => 'muted_color', 'value' => '#8E8E93', 'type' => 'text', 'placeholder' => ['ar' => 'لون النص الثانوي', 'en' => 'Muted text color'], 'label' => ['ar' => 'لون النص الثانوي', 'en' => 'Muted color'], 'is_multi_lang' => false],
                ],
                'font' => [
                    ['key' => 'font_family_ar', 'value' => 'Tajawal', 'type' => 'select', 'placeholder' => ['ar' => 'خط عربي', 'en' => 'Arabic font'], 'label' => ['ar' => 'خط عربي', 'en' => 'Arabic font'], 'is_multi_lang' => true],
                    ['key' => 'font_family_en', 'value' => 'Montserrat', 'type' => 'select', 'placeholder' => ['ar' => 'خط انجليزي', 'en' => 'English font'], 'label' => ['ar' => 'خط انجليزي', 'en' => 'English font'], 'is_multi_lang' => true],
                    ['key' => 'font_size', 'value' => '16px', 'type' => 'select', 'placeholder' => ['ar' => 'حجم الخط', 'en' => 'Font size'], 'label' => ['ar' => 'حجم الخط', 'en' => 'Font size'], 'is_multi_lang' => false],
                ],
            ],

            'mail_templates' => [
                'otp' => [
                    ['key' => 'otp_bg', 'value' => '#F4F4F5', 'type' => 'text', 'placeholder' => ['ar' => 'لون خلفية OTP', 'en' => 'OTP background color'], 'label' => ['ar' => 'خلفية OTP', 'en' => 'OTP background'], 'is_multi_lang' => false],
                    ['key' => 'otp_border_color', 'value' => '#D1D5DB', 'type' => 'text', 'placeholder' => ['ar' => 'لون حد OTP', 'en' => 'OTP border color'], 'label' => ['ar' => 'حد OTP', 'en' => 'OTP border'], 'is_multi_lang' => false],
                    ['key' => 'otp_font_size', 'value' => '18px', 'type' => 'text', 'placeholder' => ['ar' => 'حجم خط OTP', 'en' => 'OTP font size'], 'label' => ['ar' => 'حجم خط OTP', 'en' => 'OTP font size'], 'is_multi_lang' => false],
                    ['key' => 'otp_letter_spacing', 'value' => '4px', 'type' => 'text', 'placeholder' => ['ar' => 'المسافة بين أحرف OTP', 'en' => 'OTP letter spacing'], 'label' => ['ar' => 'تباعد أحرف OTP', 'en' => 'OTP letter spacing'], 'is_multi_lang' => false],
                    ['key' => 'otp_text_color', 'value' => '#111827', 'type' => 'text', 'placeholder' => ['ar' => 'لون نص OTP', 'en' => 'OTP text color'], 'label' => ['ar' => 'لون النص', 'en' => 'OTP text color'], 'is_multi_lang' => false],
                ],
                'generate' => [
                    ['key' => 'button_bg_color', 'value' => '#03001B', 'type' => 'text', 'placeholder' => ['ar' => 'لون خلفية الزر', 'en' => 'Button background color'], 'label' => ['ar' => 'خلفية الزر', 'en' => 'Button background'], 'is_multi_lang' => false],
                    ['key' => 'button_text_color', 'value' => '#FFFFFF', 'type' => 'text', 'placeholder' => ['ar' => 'لون نص الزر', 'en' => 'Button text color'], 'label' => ['ar' => 'لون نص الزر', 'en' => 'Button text color'], 'is_multi_lang' => false],
                    ['key' => 'header_image', 'value' => null, 'type' => 'imageUploader', 'placeholder' => ['ar' => 'صورة الهيدر', 'en' => 'Header image'], 'label' => ['ar' => 'صورة الهيدر', 'en' => 'Header image'], 'is_multi_lang' => false],
                    ['key' => 'header_text', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'نص الهيدر', 'en' => 'Header text'], 'label' => ['ar' => 'نص الهيدر', 'en' => 'Header text'], 'is_multi_lang' => true],
                    ['key' => 'footer_image', 'value' => null, 'type' => 'imageUploader', 'placeholder' => ['ar' => 'صورة الفوتر', 'en' => 'Footer image'], 'label' => ['ar' => 'صورة الفوتر', 'en' => 'Footer image'], 'is_multi_lang' => false],
                    ['key' => 'footer_text', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'نص الفوتر', 'en' => 'Footer text'], 'label' => ['ar' => 'نص الفوتر', 'en' => 'Footer text'], 'is_multi_lang' => true],
                ],
            ],

            'social' => [
                ['key' => 'instagram', 'value' => 'https://www.instagram.com/wakeb_data', 'type' => 'text', 'placeholder' => ['ar' => 'انستجرام', 'en' => 'Instagram'], 'label' => ['ar' => 'انستجرام', 'en' => 'Instagram'], 'is_multi_lang' => false],
                ['key' => 'facebook', 'value' => 'https://www.facebook.com/Wakeb.tech', 'type' => 'text', 'placeholder' => ['ar' => 'فيسبوك', 'en' => 'Facebook'], 'label' => ['ar' => 'فيسبوك', 'en' => 'Facebook'], 'is_multi_lang' => false],
                ['key' => 'linkedin', 'value' => 'https://www.linkedin.com/company/wakeb-data', 'type' => 'text', 'placeholder' => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'], 'label' => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'], 'is_multi_lang' => false],
                ['key' => 'twitter', 'value' => 'https://twitter.com/WAKEB_Data', 'type' => 'text', 'placeholder' => ['ar' => 'تويتر', 'en' => 'Twitter'], 'label' => ['ar' => 'تويتر', 'en' => 'Twitter'], 'is_multi_lang' => false],
                ['key' => 'youtube', 'value' => 'https://www.youtube.com/channel/UCG2IozJnWW-IzA3j2cSlhmg', 'type' => 'text', 'placeholder' => ['ar' => 'يوتيوب', 'en' => 'Youtube'], 'label' => ['ar' => 'يوتيوب', 'en' => 'Youtube'], 'is_multi_lang' => false],
            ],

            'config' => [
                'mail' => [
                    ['key' => 'mailer', 'value' => env('MAIL_MAILER', 'smtp'), 'type' => 'text', 'placeholder' => ['ar' => 'Mailer البريد الإلكتروني', 'en' => 'Mail mailer'], 'label' => ['ar' => 'Mailer', 'en' => 'Mailer'], 'is_multi_lang' => false],
                    ['key' => 'host', 'value' => env('MAIL_HOST', 'smtp.mailtrap.io'), 'type' => 'text', 'placeholder' => ['ar' => 'Host البريد الإلكتروني', 'en' => 'Mail host'], 'label' => ['ar' => 'Host', 'en' => 'Mail host'], 'is_multi_lang' => false],
                    ['key' => 'port', 'value' => env('MAIL_PORT', 2525), 'type' => 'text', 'placeholder' => ['ar' => 'Port البريد الإلكتروني', 'en' => 'Mail port'], 'label' => ['ar' => 'Port', 'en' => 'Port'], 'is_multi_lang' => false],
                    ['key' => 'username', 'value' => env('MAIL_USERNAME', null), 'type' => 'text', 'placeholder' => ['ar' => 'اسم المستخدم', 'en' => 'Mail username'], 'label' => ['ar' => 'اسم المستخدم', 'en' => 'Mail username'], 'is_multi_lang' => false],
                    ['key' => 'password', 'value' => env('MAIL_PASSWORD', null), 'type' => 'text', 'placeholder' => ['ar' => 'كلمة المرور', 'en' => 'Mail password'], 'label' => ['ar' => 'كلمة المرور', 'en' => 'Mail password'], 'is_multi_lang' => false],
                    ['key' => 'encryption', 'value' => env('MAIL_ENCRYPTION', null), 'type' => 'text', 'placeholder' => ['ar' => 'نوع التشفير', 'en' => 'Mail encryption'], 'label' => ['ar' => 'نوع التشفير', 'en' => 'Mail encryption'], 'is_multi_lang' => false],
                    ['key' => 'from_address', 'value' => env('MAIL_FROM_ADDRESS', 'info@wakeb.tech'), 'type' => 'text', 'placeholder' => ['ar' => 'من البريد الإلكتروني', 'en' => 'Mail from address'], 'label' => ['ar' => 'البريد المرسل', 'en' => 'From address'], 'is_multi_lang' => false],
                    ['key' => 'from_name', 'value' => env('MAIL_FROM_NAME', 'Wakeb'), 'type' => 'text', 'placeholder' => ['ar' => 'اسم المرسل', 'en' => 'Mail from name'], 'label' => ['ar' => 'اسم المرسل', 'en' => 'From name'], 'is_multi_lang' => true],
                ],
                'sms' => [
                    ['key' => 'gateway', 'value' => 'twilio', 'type' => 'text', 'placeholder' => ['ar' => 'بوابة الرسائل القصيرة', 'en' => 'SMS gateway'], 'label' => ['ar' => 'بوابة الرسائل', 'en' => 'SMS gateway'], 'is_multi_lang' => false],
                    ['key' => 'username', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'اسم المستخدم', 'en' => 'SMS username'], 'label' => ['ar' => 'اسم المستخدم', 'en' => 'Username'], 'is_multi_lang' => false],
                    ['key' => 'password', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'كلمة المرور', 'en' => 'SMS password'], 'label' => ['ar' => 'كلمة المرور', 'en' => 'Password'], 'is_multi_lang' => false],
                    ['key' => 'sender_id', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'اسم المرسل', 'en' => 'SMS sender ID'], 'label' => ['ar' => 'اسم المرسل', 'en' => 'Sender ID'], 'is_multi_lang' => false],
                ],
                'ldap' => [
                    ['key' => 'host', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP host', 'en' => 'LDAP host'], 'label' => ['ar' => 'LDAP host', 'en' => 'LDAP host'], 'is_multi_lang' => false],
                    ['key' => 'port', 'value' => 389, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP port', 'en' => 'LDAP port'], 'label' => ['ar' => 'LDAP port', 'en' => 'LDAP port'], 'is_multi_lang' => false],
                    ['key' => 'base_dn', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP Base DN', 'en' => 'LDAP Base DN'], 'label' => ['ar' => 'LDAP Base DN', 'en' => 'LDAP Base DN'], 'is_multi_lang' => false],
                    ['key' => 'username', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP username', 'en' => 'LDAP username'], 'label' => ['ar' => 'LDAP اسم المستخدم', 'en' => 'LDAP username'], 'is_multi_lang' => false],
                    ['key' => 'password', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP password', 'en' => 'LDAP password'], 'label' => ['ar' => 'LDAP كلمة المرور', 'en' => 'LDAP password'], 'is_multi_lang' => false],
                ],
            ],

        ],

        'jervis' => [
            'general' => [
                'info' => [
                    ['key' => 'name', 'value' => ['ar' => 'جارفيس', 'en' => 'Jervis'], 'type' => 'text', 'placeholder' => ['ar' => 'اسم الشركة هنا', 'en' => 'Enter company name'], 'label' => ['ar' => 'اسم الشركة', 'en' => 'Company name'], 'is_multi_lang' => true],
                    ['key' => 'copyright_name', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'حقوق الطبع والنشر', 'en' => 'Copyright name'], 'label' => ['ar' => 'حقوق الطبع والنشر', 'en' => 'Copyright name'], 'is_multi_lang' => true],
                    ['key' => 'website_address', 'value' => 'https://jervis.com', 'type' => 'text', 'placeholder' => ['ar' => 'رابط الموقع الإلكتروني', 'en' => 'Website URL'], 'label' => ['ar' => 'رابط الموقع', 'en' => 'Website URL'], 'is_multi_lang' => false],
                    ['key' => 'website_description', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'وصف الموقع بالعربية', 'en' => 'Website description in English'], 'label' => ['ar' => 'وصف الموقع', 'en' => 'Website description'], 'is_multi_lang' => true],
                    ['key' => 'meta_description', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'وصف الميتا بالعربية', 'en' => 'Meta description in English'], 'label' => ['ar' => 'وصف الميتا', 'en' => 'Meta description'], 'is_multi_lang' => true],
                ],
                'contact' => [
                    ['key' => 'contact_email', 'value' => 'info@jervis.com', 'type' => 'text', 'placeholder' => ['ar' => 'البريد الإلكتروني للدعم', 'en' => 'Support email'], 'label' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email'], 'is_multi_lang' => false],
                    ['key' => 'contact_phone', 'value' => '+966987654321', 'type' => 'text', 'placeholder' => ['ar' => 'رقم الهاتف', 'en' => 'Phone number'], 'label' => ['ar' => 'رقم الهاتف', 'en' => 'Phone'], 'is_multi_lang' => false],
                    ['key' => 'contact_address', 'value' => 'King Fahad Road, Riyadh, KSA', 'type' => 'text', 'placeholder' => ['ar' => 'العنوان الكامل', 'en' => 'Full address'], 'label' => ['ar' => 'العنوان', 'en' => 'Address'], 'is_multi_lang' => true],
                ],
            ],

            'properties' => [
                'logos' => [
                    ['key' => 'website_logo_large', 'value' => 'template_design/jervisLg.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار كبير للويب', 'en' => 'Large website logo'], 'label' => ['ar' => 'شعار كبير', 'en' => 'Large logo'], 'is_multi_lang' => false],
                    ['key' => 'website_dark_logo_large', 'value' => 'template_design/jervisLg-dark.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار كبير للويب النسخة الداكنة', 'en' => 'Large dark logo'], 'label' => ['ar' => 'شعار كبير داكن', 'en' => 'Large dark logo'], 'is_multi_lang' => false],
                    ['key' => 'website_logo_small', 'value' => 'template_design/jervisSM.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار صغير للويب', 'en' => 'Small website logo'], 'label' => ['ar' => 'شعار صغير', 'en' => 'Small logo'], 'is_multi_lang' => false],
                    ['key' => 'website_dark_logo_small', 'value' => 'template_design/jervisSM-dark.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'شعار صغير للويب النسخة الداكنة', 'en' => 'Small dark logo'], 'label' => ['ar' => 'شعار صغير داكن', 'en' => 'Small dark logo'], 'is_multi_lang' => false],
                ],
                'icons' => [
                    ['key' => 'website_favorite_place_icon', 'value' => 'template_design/favicon_jervis.png', 'type' => 'imageUploader', 'placeholder' => ['ar' => 'أيقونة المفضلة', 'en' => 'Favicon icon'], 'label' => ['ar' => 'أيقونة المفضلة', 'en' => 'Favicon'], 'is_multi_lang' => false],
                ],
            ],

            'notifications' => [
                ['key' => 'mail_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين إشعارات البريد الإلكتروني', 'en' => 'Enable email notifications'], 'label' => ['ar' => 'إشعارات البريد', 'en' => 'Email notifications'], 'is_multi_lang' => false],
                ['key' => 'sms_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين إشعارات الرسائل القصيرة', 'en' => 'Enable SMS notifications'], 'label' => ['ar' => 'إشعارات الرسائل', 'en' => 'SMS notifications'], 'is_multi_lang' => false],
                ['key' => 'push_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين الإشعارات الفورية', 'en' => 'Enable push notifications'], 'label' => ['ar' => 'إشعارات فورية', 'en' => 'Push notifications'], 'is_multi_lang' => false],
                ['key' => 'real_time_support', 'value' => true, 'type' => 'switchbox', 'placeholder' => ['ar' => 'تمكين الإشعارات الحية', 'en' => 'Enable realtime notifications'], 'label' => ['ar' => 'إشعارات حية', 'en' => 'Realtime notifications'], 'is_multi_lang' => false],
            ],

            'theme' => [
                'colors' => [
                    ['key' => 'primary_color', 'value' => '#101010', 'type' => 'text', 'placeholder' => ['ar' => 'اللون الرئيسي', 'en' => 'Primary color'], 'label' => ['ar' => 'اللون الرئيسي', 'en' => 'Primary color'], 'is_multi_lang' => false],
                    ['key' => 'secondary_color', 'value' => '#F0F0F0', 'type' => 'text', 'placeholder' => ['ar' => 'اللون الثانوي', 'en' => 'Secondary color'], 'label' => ['ar' => 'اللون الثانوي', 'en' => 'Secondary color'], 'is_multi_lang' => false],
                    ['key' => 'text_color', 'value' => '#111111', 'type' => 'text', 'placeholder' => ['ar' => 'لون النص الرئيسي', 'en' => 'Text color'], 'label' => ['ar' => 'لون النص', 'en' => 'Text color'], 'is_multi_lang' => false],
                    ['key' => 'muted_color', 'value' => '#8E8E93', 'type' => 'text', 'placeholder' => ['ar' => 'لون النص الثانوي', 'en' => 'Muted text color'], 'label' => ['ar' => 'لون النص الثانوي', 'en' => 'Muted color'], 'is_multi_lang' => false],
                ],
                'font' => [
                    ['key' => 'font_family_ar', 'value' => 'Cairo', 'type' => 'select', 'placeholder' => ['ar' => 'خط عربي', 'en' => 'Arabic font'], 'label' => ['ar' => 'خط عربي', 'en' => 'Arabic font'], 'is_multi_lang' => true],
                    ['key' => 'font_family_en', 'value' => 'Roboto', 'type' => 'select', 'placeholder' => ['ar' => 'خط انجليزي', 'en' => 'English font'], 'label' => ['ar' => 'خط انجليزي', 'en' => 'English font'], 'is_multi_lang' => true],
                    ['key' => 'font_size', 'value' => '16px', 'type' => 'select', 'placeholder' => ['ar' => 'حجم الخط', 'en' => 'Font size'], 'label' => ['ar' => 'حجم الخط', 'en' => 'Font size'], 'is_multi_lang' => false],
                ],
            ],
            'mail_templates' => [
                'otp' => [
                    ['key' => 'otp_bg', 'value' => '#EEF2FF', 'type' => 'text', 'placeholder' => ['ar' => 'لون خلفية OTP', 'en' => 'OTP background color'], 'label' => ['ar' => 'خلفية OTP', 'en' => 'OTP background'], 'is_multi_lang' => false],
                    ['key' => 'otp_border_color', 'value' => '#C7D2FE', 'type' => 'text', 'placeholder' => ['ar' => 'لون حد OTP', 'en' => 'OTP border color'], 'label' => ['ar' => 'حد OTP', 'en' => 'OTP border'], 'is_multi_lang' => false],
                    ['key' => 'otp_font_size', 'value' => '18px', 'type' => 'text', 'placeholder' => ['ar' => 'حجم خط OTP', 'en' => 'OTP font size'], 'label' => ['ar' => 'حجم خط OTP', 'en' => 'OTP font size'], 'is_multi_lang' => false],
                    ['key' => 'otp_letter_spacing', 'value' => '4px', 'type' => 'text', 'placeholder' => ['ar' => 'المسافة بين أحرف OTP', 'en' => 'OTP letter spacing'], 'label' => ['ar' => 'مسافة الأحرف', 'en' => 'Letter spacing'], 'is_multi_lang' => false],
                    ['key' => 'otp_text_color', 'value' => '#1E40AF', 'type' => 'text', 'placeholder' => ['ar' => 'لون نص OTP', 'en' => 'OTP text color'], 'label' => ['ar' => 'لون النص', 'en' => 'Text color'], 'is_multi_lang' => false],
                ],
                'generate' => [
                    ['key' => 'button_bg_color', 'value' => '#0057D9', 'type' => 'text', 'placeholder' => ['ar' => 'لون خلفية الزر', 'en' => 'Button background color'], 'label' => ['ar' => 'لون خلفية الزر', 'en' => 'Button background'], 'is_multi_lang' => false],
                    ['key' => 'button_text_color', 'value' => '#FFFFFF', 'type' => 'text', 'placeholder' => ['ar' => 'لون نص الزر', 'en' => 'Button text color'], 'label' => ['ar' => 'لون نص الزر', 'en' => 'Button text color'], 'is_multi_lang' => false],
                    ['key' => 'header_image', 'value' => null, 'type' => 'imageUploader', 'placeholder' => ['ar' => 'صورة الهيدر', 'en' => 'Header image'], 'label' => ['ar' => 'صورة الهيدر', 'en' => 'Header image'], 'is_multi_lang' => false],
                    ['key' => 'header_text', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'نص الهيدر', 'en' => 'Header text'], 'label' => ['ar' => 'نص الهيدر', 'en' => 'Header text'], 'is_multi_lang' => true],
                    ['key' => 'footer_image', 'value' => null, 'type' => 'imageUploader', 'placeholder' => ['ar' => 'صورة الفوتر', 'en' => 'Footer image'], 'label' => ['ar' => 'صورة الفوتر', 'en' => 'Footer image'], 'is_multi_lang' => false],
                    ['key' => 'footer_text', 'value' => null, 'type' => 'textarea', 'placeholder' => ['ar' => 'نص الفوتر', 'en' => 'Footer text'], 'label' => ['ar' => 'نص الفوتر', 'en' => 'Footer text'], 'is_multi_lang' => true],
                ],
            ],

            'social' => [
                ['key' => 'instagram', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'انستجرام', 'en' => 'Instagram'], 'label' => ['ar' => 'انستجرام', 'en' => 'Instagram'], 'is_multi_lang' => false],
                ['key' => 'facebook', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'فيسبوك', 'en' => 'Facebook'], 'label' => ['ar' => 'فيسبوك', 'en' => 'Facebook'], 'is_multi_lang' => false],
                ['key' => 'linkedin', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'], 'label' => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'], 'is_multi_lang' => false],
                ['key' => 'twitter', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'تويتر', 'en' => 'Twitter'], 'label' => ['ar' => 'تويتر', 'en' => 'Twitter'], 'is_multi_lang' => false],
                ['key' => 'youtube', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'يوتيوب', 'en' => 'Youtube'], 'label' => ['ar' => 'يوتيوب', 'en' => 'Youtube'], 'is_multi_lang' => false],
            ],
            'config' => [
                'mail' => [
                    ['key' => 'mailer', 'value' => env('MAIL_MAILER', 'smtp'), 'type' => 'text', 'placeholder' => ['ar' => 'Mailer البريد الإلكتروني', 'en' => 'Mail mailer'], 'label' => ['ar' => 'Mailer', 'en' => 'Mailer'], 'is_multi_lang' => false],
                    ['key' => 'host', 'value' => env('MAIL_HOST', 'smtp.mailtrap.io'), 'type' => 'text', 'placeholder' => ['ar' => 'Host البريد الإلكتروني', 'en' => 'Mail host'], 'label' => ['ar' => 'Host', 'en' => 'Host'], 'is_multi_lang' => false],
                    ['key' => 'port', 'value' => env('MAIL_PORT', 2525), 'type' => 'text', 'placeholder' => ['ar' => 'Port البريد الإلكتروني', 'en' => 'Mail port'], 'label' => ['ar' => 'Port', 'en' => 'Port'], 'is_multi_lang' => false],
                    ['key' => 'username', 'value' => env('MAIL_USERNAME', null), 'type' => 'text', 'placeholder' => ['ar' => 'اسم المستخدم', 'en' => 'Mail username'], 'label' => ['ar' => 'اسم المستخدم', 'en' => 'Username'], 'is_multi_lang' => false],
                    ['key' => 'password', 'value' => env('MAIL_PASSWORD', null), 'type' => 'text', 'placeholder' => ['ar' => 'كلمة المرور', 'en' => 'Mail password'], 'label' => ['ar' => 'كلمة المرور', 'en' => 'Password'], 'is_multi_lang' => false],
                    ['key' => 'encryption', 'value' => env('MAIL_ENCRYPTION', null), 'type' => 'text', 'placeholder' => ['ar' => 'نوع التشفير', 'en' => 'Mail encryption'], 'label' => ['ar' => 'نوع التشفير', 'en' => 'Encryption'], 'is_multi_lang' => false],
                    ['key' => 'from_address', 'value' => env('MAIL_FROM_ADDRESS', 'info@jervis.com'), 'type' => 'text', 'placeholder' => ['ar' => 'من البريد الإلكتروني', 'en' => 'Mail from address'], 'label' => ['ar' => 'من البريد', 'en' => 'From address'], 'is_multi_lang' => false],
                    ['key' => 'from_name', 'value' => env('MAIL_FROM_NAME', 'Jervis'), 'type' => 'text', 'placeholder' => ['ar' => 'اسم المرسل', 'en' => 'Mail from name'], 'label' => ['ar' => 'اسم المرسل', 'en' => 'From name'], 'is_multi_lang' => true],
                ],
                'sms' => [
                    ['key' => 'gateway', 'value' => 'twilio', 'type' => 'text', 'placeholder' => ['ar' => 'بوابة الرسائل القصيرة', 'en' => 'SMS gateway'], 'label' => ['ar' => 'بوابة الرسائل', 'en' => 'Gateway'], 'is_multi_lang' => false],
                    ['key' => 'username', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'اسم المستخدم', 'en' => 'SMS username'], 'label' => ['ar' => 'اسم المستخدم', 'en' => 'Username'], 'is_multi_lang' => false],
                    ['key' => 'password', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'كلمة المرور', 'en' => 'SMS password'], 'label' => ['ar' => 'كلمة المرور', 'en' => 'Password'], 'is_multi_lang' => false],
                    ['key' => 'sender_id', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'اسم المرسل', 'en' => 'SMS sender ID'], 'label' => ['ar' => 'اسم المرسل', 'en' => 'Sender ID'], 'is_multi_lang' => false],
                ],
                'ldap' => [
                    ['key' => 'host', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP host', 'en' => 'LDAP host'], 'label' => ['ar' => 'LDAP host', 'en' => 'Host'], 'is_multi_lang' => false],
                    ['key' => 'port', 'value' => 389, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP port', 'en' => 'LDAP port'], 'label' => ['ar' => 'LDAP port', 'en' => 'Port'], 'is_multi_lang' => false],
                    ['key' => 'base_dn', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP Base DN', 'en' => 'LDAP Base DN'], 'label' => ['ar' => 'LDAP Base DN', 'en' => 'Base DN'], 'is_multi_lang' => false],
                    ['key' => 'username', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP username', 'en' => 'LDAP username'], 'label' => ['ar' => 'LDAP اسم المستخدم', 'en' => 'Username'], 'is_multi_lang' => false],
                    ['key' => 'password', 'value' => null, 'type' => 'text', 'placeholder' => ['ar' => 'LDAP password', 'en' => 'LDAP password'], 'label' => ['ar' => 'LDAP كلمة المرور', 'en' => 'Password'], 'is_multi_lang' => false],
                ],
            ],

        ]
    ]
];


