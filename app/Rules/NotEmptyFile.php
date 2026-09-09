<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects uploaded files that carry no content. Laravel's `min` rule measures
 * files in kilobytes, so it cannot express "at least one byte" without also
 * rejecting legitimately small uploads.
 */
class NotEmptyFile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile && ! $value->getSize()) {
            $fail(__('validation.empty_file'));
        }
    }
}
