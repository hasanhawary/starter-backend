<?php

/**
 * Wakeb Brand Configuration
 * Brand-specific values that override the template
 */

return [
    'general' => [
        'info' => [
            ['key' => 'name', 'value' => ['ar' => 'واكب', 'en' => 'Wakeb']],
            ['key' => 'copyright_name', 'value' => null],
            ['key' => 'website_address', 'value' => 'https://wakeb.tech'],
            ['key' => 'website_description', 'value' => null],
            ['key' => 'meta_description', 'value' => null],
        ],
        'contact' => [
            ['key' => 'contact_email', 'value' => 'info@wakeb.tech'],
            ['key' => 'contact_phone', 'value' => '+966123456789'],
            ['key' => 'contact_address', 'value' => 'King Abdullah Street, Riyadh, KSA'],
        ],
        'social' => [
            ['key' => 'instagram', 'value' => 'https://www.instagram.com/wakeb_data'],
            ['key' => 'facebook', 'value' => 'https://www.facebook.com/Wakeb.tech'],
            ['key' => 'linkedin', 'value' => 'https://www.linkedin.com/company/wakeb-data'],
            ['key' => 'twitter', 'value' => 'https://twitter.com/WAKEB_Data'],
            ['key' => 'youtube', 'value' => 'https://www.youtube.com/channel/UCG2IozJnWW-IzA3j2cSlhmg'],
        ],
    ],

    'properties' => [
        ['key' => 'website_logo_large', 'value' => 'brands/wakeb/logo_light.svg'],
        ['key' => 'website_dark_logo_large', 'value' => 'brands/wakeb/logo_dark.svg'],
        ['key' => 'website_logo_small', 'value' => 'brands/wakeb/logo_sm_light.svg'],
        ['key' => 'website_dark_logo_small', 'value' => 'brands/wakeb/logo_sm_dark.svg'],
        ['key' => 'website_favorite_place_icon', 'value' => 'brands/wakeb/favicon.svg'],
    ],

    'notifications' => [
        ['key' => 'mail_support', 'value' => true],
        ['key' => 'sms_support', 'value' => true],
        ['key' => 'push_support', 'value' => true],
        ['key' => 'realtime_support', 'value' => true],
    ],

    'theme' => [
        'colors' => [
            ['key' => 'primary_color', 'value' => '#03001B'],
            ['key' => 'secondary_color', 'value' => '#F4F7FF'],
            ['key' => 'text_color', 'value' => '#1F1F1F'],
            ['key' => 'muted_color', 'value' => '#8E8E93'],
        ],
        'font' => [
            ['key' => 'font_family_ar', 'value' => null],
            ['key' => 'font_family_en', 'value' => null],
            ['key' => 'font_size', 'value' => '16px'],
        ],
    ],

    'mail_templates' => [
        'otp' => [
            ['key' => 'otp_bg', 'value' => '#F4F4F5'],
            ['key' => 'otp_border_color', 'value' => '#D1D5DB'],
            ['key' => 'otp_font_size', 'value' => '18px'],
            ['key' => 'otp_letter_spacing', 'value' => '4px'],
            ['key' => 'otp_text_color', 'value' => '#111827'],
        ],
        'theme' => [
            ['key' => 'button_bg_color', 'value' => '#03001B'],
            ['key' => 'button_text_color', 'value' => '#FFFFFF'],
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
