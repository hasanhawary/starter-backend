<?php

namespace App\Services\Global;

use Random\RandomException;

class EncryptionService
{
    /**
     * @throws RandomException
     * @throws \JsonException
     */
    public static function encrypt(string|array $data): array
    {
        $key = config('project.auth.encryption.key');
        $cipher = config('project.auth.encryption.cipher', 'AES-256-CBC');

        $iv = random_bytes(openssl_cipher_iv_length($cipher));
        $encrypted = openssl_encrypt(json_encode($data, JSON_THROW_ON_ERROR), $cipher, $key, 0, $iv);

        return [
            'iv' => base64_encode($iv),
            'data' => $encrypted,
        ];
    }

    public static function decrypt(string $encryptedData, string $iv): string
    {
        $key = config('project.auth.encryption.key');
        $cipher = config('project.auth.encryption.cipher', 'AES-256-CBC');

        return openssl_decrypt($encryptedData, $cipher, $key, 0, base64_decode($iv));
    }
}
