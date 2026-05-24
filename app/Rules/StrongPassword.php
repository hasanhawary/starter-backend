<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Collection;

class StrongPassword implements InvokableRule
{
    protected Collection $forbiddenWords;

    protected array $dictionaryWords = [
        'password',
        'p@ssw0rd',
        'secret123',
        '123456',
        'qwerty',
        'admin',
        'welcome',
        'login',
        'letmein',
        'monkey',
    ];

    public function __construct(
        protected ?string $firstName = null,
        protected ?string $middleName = null,
        protected ?string $lastName = null
    ) {
        $this->forbiddenWords = collect()
            ->merge($this->splitName($this->firstName))
            ->merge($this->splitName($this->middleName))
            ->merge($this->splitName($this->lastName))
            ->filter(fn ($word) => strlen($word) > 2)
            ->map(fn ($word) => strtolower($word))
            ->unique();
    }

    public function __invoke($attribute, $value, $fail): void
    {
        $checks = [
            'min_length' => strlen($value) >= 8,
            'has_upper' => preg_match('/[A-Z]/', $value),
            'has_lower' => preg_match('/[a-z]/', $value),
            'has_digit' => preg_match('/[0-9]/', $value),
            'has_special' => preg_match('/[\W]/', $value),
            'no_repeats' => ! preg_match('/(.)\1{2,}/', $value),
            'no_sequences' => ! $this->hasSequentialChars($value),
            'no_personal_info' => ! $this->containsForbiddenWords($value),
            'no_dictionary' => ! $this->hasDictionaryWord($value),
        ];

        foreach ($checks as $key => $passed) {
            if (! $passed) {
                $fail(__("validation.password_$key", ['attribute' => __("validation.attributes.{$attribute}")]));
            }
        }
    }

    protected function splitName(?string $name): Collection
    {
        if (empty($name)) {
            return collect();
        }

        return collect(explode(' ', $name))
            ->filter(fn ($part) => strlen($part) > 2);
    }

    protected function hasSequentialChars(string $password, int $length = 3): bool
    {
        $sequences = ['abcdefghijklmnopqrstuvwxyz', '0123456789'];
        $password = strtolower($password);

        foreach ($sequences as $seq) {
            for ($i = 0; $i <= strlen($seq) - $length; $i++) {
                $part = substr($seq, $i, $length);
                if (str_contains($password, $part) || str_contains($password, strrev($part))) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function hasDictionaryWord(string $password): bool
    {
        $password = strtolower($password);

        return collect($this->dictionaryWords)
            ->contains(fn ($word) => str_contains($password, strtolower($word)));
    }

    protected function containsForbiddenWords(string $password): bool
    {
        $password = strtolower($password);

        return $this->forbiddenWords
            ->contains(fn ($word) => str_contains($password, $word));
    }
}
