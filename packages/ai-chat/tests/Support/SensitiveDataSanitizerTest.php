<?php

namespace AiChat\Tests\Support;

use AiChat\Support\SensitiveDataSanitizer;
use PHPUnit\Framework\TestCase;

class SensitiveDataSanitizerTest extends TestCase
{
    public function test_sanitizes_password(): void
    {
        $data = ['password' => 'secret123', 'name' => 'John'];

        $result = SensitiveDataSanitizer::sanitize($data);

        $this->assertSame('[REDACTED]', $result['password']);
        $this->assertSame('John', $result['name']);
    }

    public function test_sanitizes_api_key(): void
    {
        $data = ['api_key' => 'sk-abc123', 'config' => 'value'];

        $result = SensitiveDataSanitizer::sanitize($data);

        $this->assertSame('[REDACTED]', $result['api_key']);
        $this->assertSame('value', $result['config']);
    }

    public function test_sanitizes_authorization(): void
    {
        $data = ['authorization' => 'Bearer token123', 'id' => 1];

        $result = SensitiveDataSanitizer::sanitize($data);

        $this->assertSame('[REDACTED]', $result['authorization']);
    }

    public function test_sanitizes_bearer(): void
    {
        $data = ['bearer_token' => 'abc123xyz'];

        $result = SensitiveDataSanitizer::sanitize($data);

        $this->assertSame('[REDACTED]', $result['bearer_token']);
    }

    public function test_sanitizes_nested_data(): void
    {
        $data = [
            'user' => [
                'password' => 'secret',
                'email' => 'test@example.com',
                'profile' => [
                    'access_token' => 'token123',
                    'bio' => 'Hello world',
                ],
            ],
        ];

        $result = SensitiveDataSanitizer::sanitize($data);

        $this->assertSame('[REDACTED]', $result['user']['password']);
        $this->assertSame('test@example.com', $result['user']['email']);
        $this->assertSame('[REDACTED]', $result['user']['profile']['access_token']);
        $this->assertSame('Hello world', $result['user']['profile']['bio']);
    }

    public function test_sanitizes_all_known_fields(): void
    {
        $fields = [
            'password' => 'val',
            'remember_token' => 'val',
            'token' => 'val',
            'secret' => 'val',
            'api_key' => 'val',
            'access_token' => 'val',
            'refresh_token' => 'val',
            'credit_card' => 'val',
            'card_number' => 'val',
            'cvv' => 'val',
            'private_key' => 'val',
            'otp_data' => 'val',
            'two_factor_secret' => 'val',
            'two_factor_recovery_codes' => 'val',
            'authorization' => 'val',
            'bearer' => 'val',
        ];

        $result = SensitiveDataSanitizer::sanitize($fields);

        foreach ($fields as $key => $value) {
            $this->assertSame('[REDACTED]', $result[$key], "Field {$key} should be redacted");
        }
    }

    public function test_is_sensitive_field_detects_fields(): void
    {
        $this->assertTrue(SensitiveDataSanitizer::isSensitiveField('user_password'));
        $this->assertTrue(SensitiveDataSanitizer::isSensitiveField('my_api_key'));
        $this->assertTrue(SensitiveDataSanitizer::isSensitiveField('auth_access_token'));
        $this->assertFalse(SensitiveDataSanitizer::isSensitiveField('username'));
        $this->assertFalse(SensitiveDataSanitizer::isSensitiveField('email_address'));
    }

    public function test_add_custom_sensitive_field(): void
    {
        SensitiveDataSanitizer::addSensitiveField('custom_secret');

        $data = ['custom_secret' => 'hidden_value', 'public' => 'visible'];
        $result = SensitiveDataSanitizer::sanitize($data);

        $this->assertSame('[REDACTED]', $result['custom_secret']);
        $this->assertSame('visible', $result['public']);
    }

    public function test_empty_array_unchanged(): void
    {
        $result = SensitiveDataSanitizer::sanitize([]);

        $this->assertEmpty($result);
    }

    public function test_no_sensitive_fields_unchanged(): void
    {
        $data = ['name' => 'John', 'age' => 30, 'city' => 'Paris'];

        $result = SensitiveDataSanitizer::sanitize($data);

        $this->assertEquals($data, $result);
    }
}
