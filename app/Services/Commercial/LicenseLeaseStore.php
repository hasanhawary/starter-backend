<?php

namespace App\Services\Commercial;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LicenseLeaseStore
{
    /**
     * @return array<string, mixed>|null
     */
    public function readLease(): ?array
    {
        return $this->read($this->path('current-lease.json'));
    }

    public function leaseFileExists(): bool
    {
        return File::exists($this->path('current-lease.json'));
    }

    /**
     * @param  array<string, mixed>  $lease
     */
    public function writeLease(array $lease): void
    {
        $this->write($this->path('current-lease.json'), $lease);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readTrustedTime(): ?array
    {
        return $this->read($this->path('trusted-time.json'));
    }

    /**
     * @param  array<string, mixed>  $trustedTime
     */
    public function writeTrustedTime(array $trustedTime): void
    {
        $this->write($this->path('trusted-time.json'), $trustedTime);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readRefreshState(): ?array
    {
        return $this->read($this->path('refresh-state.json'));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function writeRefreshState(array $state): void
    {
        $this->write($this->path('refresh-state.json'), $state);
    }

    private function path(string $filename): string
    {
        return rtrim((string) config('edge.paths.license'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function read(string $path): ?array
    {
        if (! File::exists($path)) {
            return null;
        }

        try {
            $value = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

            return is_array($value) ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function write(string $path, array $value): void
    {
        File::ensureDirectoryExists(dirname($path));
        $temporaryPath = dirname($path).DIRECTORY_SEPARATOR.'.'.basename($path).'.'.Str::uuid().'.tmp';
        File::put($temporaryPath, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        if (! @rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new \RuntimeException('Unable to atomically replace the commercial lease file.');
        }
    }
}
