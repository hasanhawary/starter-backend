<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use function PHPUnit\Framework\isEmpty;

class TotalFileSize implements ValidationRule
{
    protected int $maxBytes;
//    protected mixed $existingAttachments = [];

    public function __construct(int $maxMB, $existingAttachments = [])
    {
        $this->maxBytes = $maxMB * pow(1024, 2);
//        $this->existingAttachments = $existingAttachments;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $totalSize = 0;

        foreach ((array)$value as $file) {
            if ($file instanceof UploadedFile) {
                $totalSize += $file->getSize();
            }
        }

//        foreach ($this->existingAttachments as $attachment) {
//            $path = $attachment->getRawOriginal('path') ?? null;
//            if ($path && \Storage::exists($path)) {
//                $totalSize += \Storage::size($path);
//            }
//        }


        if ($totalSize > $this->maxBytes) {
            $fail(__('validation.max_total_file_size', [
                'max' => $this->maxBytes / pow(1024, 2),
            ]));
        }
    }
}
