<?php

return [
    'general' => [
        'info' => [
            ['key' => 'name', 'value' => ['ar' => 'الهوارى', 'en' => 'Elhawary']],
            ['key' => 'copyright_name', 'value' => null],
            ['key' => 'website_address', 'value' => null],
            ['key' => 'website_description', 'value' => null],
            ['key' => 'meta_description', 'value' => null],
        ],
        'contact' => [
            ['key' => 'name', 'value' => 'Elhawary'],
            ['key' => 'country', 'value' => 'Egypt'],
            ['key' => 'phone', 'value' => '01005164154'],
            ['key' => 'email', 'value' => null],
            ['key' => 'address', 'value' => null],
        ],
        'social' => [
            ['key' => 'instagram', 'value' => null],
            ['key' => 'facebook', 'value' => null],
            ['key' => 'linkedin', 'value' => null],
            ['key' => 'twitter', 'value' => null],
            ['key' => 'youtube', 'value' => null],
        ],
    ],

    'properties' => [
        ['key' => 'website_logo_large', 'value' => null],
        ['key' => 'website_dark_logo_large', 'value' => null],
        ['key' => 'website_logo_small', 'value' => null],
        ['key' => 'website_dark_logo_small', 'value' => null],
        ['key' => 'website_favorite_place_icon', 'value' => null],
    ],

    'notifications' => [
        ['key' => 'mail_support', 'value' => true],
        ['key' => 'sms_support', 'value' => true],
        ['key' => 'push_support', 'value' => true],
        ['key' => 'realtime_support', 'value' => true],
    ],

    'theme' => [
        'colors' => [
            ['key' => 'primary_color', 'value' => null],
            ['key' => 'secondary_color', 'value' => null],
            ['key' => 'text_color', 'value' => null],
            ['key' => 'muted_color', 'value' => null],
        ],
        'font' => [
            ['key' => 'font_family_ar', 'value' => null],
            ['key' => 'font_family_en', 'value' => null],
            ['key' => 'font_size', 'value' => null],
        ],
    ],

    'mail_templates' => [
        'otp' => [
            ['key' => 'otp_bg', 'value' => null],
            ['key' => 'otp_border_color', 'value' => null],
            ['key' => 'otp_font_size', 'value' => null],
            ['key' => 'otp_letter_spacing', 'value' => null],
            ['key' => 'otp_text_color', 'value' => null],
        ],
        'theme' => [
            ['key' => 'button_bg_color', 'value' => null],
            ['key' => 'button_text_color', 'value' => null],
            ['key' => 'header_image', 'value' => null],
            ['key' => 'header_text', 'value' => null],
            ['key' => 'footer_image', 'value' => null],
            ['key' => 'footer_text', 'value' => null],
        ],
    ],

    'config' => [
        'mail' => [
            ['key' => 'mailer', 'value' => null],
            ['key' => 'host', 'value' => null],
            ['key' => 'port', 'value' => null],
            ['key' => 'username', 'value' => null],
            ['key' => 'password', 'value' => null],
            ['key' => 'encryption', 'value' => null],
            ['key' => 'from_address', 'value' => null],
            ['key' => 'from_name', 'value' => null],
        ],
        'sms' => [
            ['key' => 'gateway', 'value' => null],
            ['key' => 'username', 'value' => null],
            ['key' => 'password', 'value' => null],
            ['key' => 'sender_id', 'value' => null],
        ],
        'ldap' => [
            ['key' => 'enabled', 'value' => null],
            ['key' => 'host', 'value' => null],
            ['key' => 'port', 'value' => null],
            ['key' => 'base_dn', 'value' => null],
            ['key' => 'username', 'value' => null],
            ['key' => 'password', 'value' => null],
        ],
        'reverb' => [
            ['key' => 'app_id', 'value' => null],
            ['key' => 'app_key', 'value' => null],
            ['key' => 'app_secret', 'value' => null],
            ['key' => 'host', 'value' => null],
            ['key' => 'port', 'value' => null],
            ['key' => 'scheme', 'value' => null],
        ],
    ],
];
