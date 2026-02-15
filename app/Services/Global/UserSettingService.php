<?php

namespace App\Services\Global;

use App\Models\User;

class UserSettingService
{
    public function __construct(protected User $user)
    {
    }

    /**
     * Get a setting value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->user->userSettings()->byKey($key)->first();
        return $setting?->value ?? $default;
    }

    /**
     * Set a setting (create or update)
     */
    public function set(string $key, mixed $value): void
    {
        $this->user->userSettings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all settings as array
     */
    public function all(): array
    {
        return $this->user->userSettings()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Delete a setting
     */
    public function delete(string $key): bool
    {
        return (bool)$this->user->userSettings()->byKey($key)->delete();
    }

    /**
     * Check if setting exists
     */
    public function has(string $key): bool
    {
        return $this->user->userSettings()->byKey($key)->exists();
    }
}
