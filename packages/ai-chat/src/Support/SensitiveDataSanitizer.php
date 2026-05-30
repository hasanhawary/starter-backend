<?php

namespace AiChat\Support;

class SensitiveDataSanitizer
{
    protected static array $sensitiveFields = [
        'password',
        'remember_token',
        'token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'credit_card',
        'card_number',
        'cvv',
        'private_key',
        'otp_data',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'authorization',
        'bearer',
    ];

    public static function sanitize(array $data): array
    {
        return collect($data)->map(function ($value, $key) {
            if (is_array($value)) {
                return static::sanitize($value);
            }

            if (static::isSensitiveField($key)) {
                return '[REDACTED]';
            }

            return $value;
        })->toArray();
    }

    public static function isSensitiveField(string $field): bool
    {
        $fieldLower = strtolower($field);

        foreach (static::$sensitiveFields as $sensitive) {
            if (str_contains($fieldLower, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    public static function addSensitiveField(string $field): void
    {
        static::$sensitiveFields[] = $field;
    }

    public static function getSensitiveFields(): array
    {
        return static::$sensitiveFields;
    }
}
