<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class TotalFileSize implements ValidationRule
{
    protected int $maxBytes;

    protected array $existingAttachments;

    /**
     * @param  int  $maxMB  Maximum allowed total size in MB
     * @param  array  $existingAttachments  Optional array of existing attachments
     */
    public function __construct(int $maxMB, array $existingAttachments = [])
    {
        $this->maxBytes = $maxMB * 1024 * 1024; // Convert MB to bytes
        $this->existingAttachments = $existingAttachments;
    }

    /**
     * Validate the total size of uploaded files
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $totalSize = 0;

        // Sum the size of new uploaded files
        foreach ((array) $value as $file) {
            if ($file instanceof UploadedFile) {
                $totalSize += $file->getSize();
            }
        }

        // Sum the size of existing attachments (optional)
        foreach ($this->existingAttachments as $attachment) {
            $path = $attachment->getRawOriginal('path') ?? null;
            if ($path && \Storage::exists($path)) {
                $totalSize += \Storage::size($path);
            }
        }

        // Fail validation if total size exceeds maxBytes
        if ($totalSize > $this->maxBytes) {
            $fail(__('validation.max_total_file_size', [
                'max' => $this->maxBytes / 1024 / 1024,
            ]));
        }
    }
}
