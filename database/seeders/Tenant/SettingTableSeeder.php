<?php

namespace Database\Seeders\Tenant;

use App\Models\Central\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'general_settings' => [
                ['key' => 'company_name', 'value' => null],
                ['key' => 'name_of_copyrights', 'value' => null],
                ['key' => 'website_address', 'value' => null],
                ['key' => 'website_description', 'value' => null, 'type' => 'textarea'],
                ['key' => 'meta_description', 'value' => null, 'type' => 'textarea'],
            ],
            'properties' => [
                ['key' => 'website_logo_large', 'value' => null, 'type' => 'imageUploader'],
                ['key' => 'website_dark_logo_large', 'value' => null, 'type' => 'imageUploader'],
                ['key' => 'website_logo_small', 'value' => null, 'type' => 'imageUploader'],
                ['key' => 'website_dark_logo_small', 'value' => null, 'type' => 'imageUploader'],
                ['key' => 'website_favorite_place_icon', 'value' => null, 'type' => 'imageUploader'],
            ],
            'notifications' => [
                ['key' => 'mail_support', 'value' => true, 'type' => 'switchbox'],
                ['key' => 'sms_support', 'value' => true, 'type' => 'switchbox'],
                ['key' => 'push_support', 'value' => true, 'type' => 'switchbox'],
                ['key' => 'real_time_support', 'value' => true, 'type' => 'switchbox'],
            ],
            'theme' => [
                ['key' => 'primary_color', 'value' => null],
                ['key' => 'font_family_ar', 'value' => null, 'type' => 'select'],
                ['key' => 'font_family_en', 'value' => null, 'type' => 'select'],
                ['key' => 'font_size', 'value' => null, 'type' => 'select'],
            ],
            'site_content.home' => [
                ['key' => 'home_title', 'value' => null],
                ['key' => 'home_description', 'value' => null],
                ['key' => 'home_image', 'value' => null, 'type' => 'imageUploader'],
            ],
            'site_content.contact' => [
                ['key' => 'description', 'value' => null, 'type' => 'textarea'],
                ['key' => 'email', 'value' => null],
                ['key' => 'phone', 'value' => null],
                ['key' => 'address', 'value' => null],
                ['key' => 'facebook', 'value' => null],
                ['key' => 'twitter', 'value' => null],
                ['key' => 'linkedin', 'value' => null],
                ['key' => 'instagram', 'value' => null],
                ['key' => 'youtube', 'value' => null],
            ],
            'mail_server' => [
                ['key' => 'mail_mailer', 'value' => null, 'is_env' => true],
                ['key' => 'mail_host', 'value' => null, 'is_env' => true],
                ['key' => 'mail_port', 'value' => null, 'is_env' => true],
                ['key' => 'mail_username', 'value' => null, 'is_env' => true],
                ['key' => 'mail_password', 'value' => null, 'is_env' => true],
                ['key' => 'mail_encryption', 'value' => null, 'is_env' => true],
                ['key' => 'mail_from_address', 'value' => null, 'is_env' => true],
                ['key' => 'mail_from_name', 'value' => null, 'is_env' => true],
            ],
        ];

        foreach ($settings as $group => $groupSettings) {
            foreach ($groupSettings as $setting) {
                Setting::create(array_merge(['group' => $group], $setting));
            }
        }

    }
}
