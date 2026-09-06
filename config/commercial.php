<?php

return [

    'license' => [
        'algorithm' => 'Ed25519',
        'lease_version' => 1,
        'signing_key_id' => env('PILOT_LICENSE_SIGNING_KEY_ID', 'pilot-dev-1'),
        'signing_private_key' => env('PILOT_LICENSE_SIGNING_PRIVATE_KEY'),
        'trusted_public_keys' => json_decode(env('PILOT_LICENSE_TRUSTED_PUBLIC_KEYS', '{}'), true) ?: [],
        'lease_duration_seconds' => (int) env('PILOT_LICENSE_LEASE_DURATION_SECONDS', 604800),
        'refresh_window_seconds' => (int) env('PILOT_LICENSE_REFRESH_WINDOW_SECONDS', 172800),
        'clock_tolerance_seconds' => (int) env('PILOT_LICENSE_CLOCK_TOLERANCE_SECONDS', 120),
        'max_forward_jump_seconds' => (int) env('PILOT_LICENSE_MAX_FORWARD_JUMP_SECONDS', 86400),
        'refresh_backoff_seconds' => (int) env('PILOT_LICENSE_REFRESH_BACKOFF_SECONDS', 900),
    ],

    'releases' => [
        'manifest_version' => 1,
        'algorithm' => 'Ed25519',
        'signing_key_id' => env('PILOT_RELEASE_SIGNING_KEY_ID', 'pilot-release-dev-1'),
        'signing_private_key' => env('PILOT_RELEASE_SIGNING_PRIVATE_KEY'),
        'trusted_public_keys' => json_decode(env('PILOT_RELEASE_TRUSTED_PUBLIC_KEYS', '{}'), true) ?: [],
        'default_channel' => 'STABLE',
        'check_interval_seconds' => (int) env('PILOT_UPDATE_CHECK_INTERVAL_SECONDS', 21600),
        'download_backoff_seconds' => (int) env('PILOT_UPDATE_DOWNLOAD_BACKOFF_SECONDS', 900),
        'minimum_free_space_bytes' => (int) env('PILOT_UPDATE_MINIMUM_FREE_SPACE_BYTES', 52428800),
        'backup_retention' => (int) env('PILOT_UPDATE_BACKUP_RETENTION', 3),
    ],

    'default_entitlements' => [
        'pos' => true,
        'kds' => true,
        'tables' => true,
        'delivery' => true,
        'inventory' => true,
        'employees' => true,
        'payroll' => true,
        'menu_builder' => true,
        'advanced_reports' => true,
        'promotions' => true,
    ],

];
