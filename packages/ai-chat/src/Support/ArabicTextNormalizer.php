<?php

namespace AiChat\Support;

class ArabicTextNormalizer
{
    protected const ARABIC_TASHKEEL = '/\p{M}/u';

    protected const TATWEEL = "\u{0640}";

    protected const ALIF_VARIANTS = [
        "\u{0622}", "\u{0623}", "\u{0625}",
    ];

    protected const ALIF_TARGET = "\u{0627}";

    protected const TA_MARBUTA = "\u{0629}";

    protected const HA_TARGET = "\u{0647}";

    protected const ALEF_MAQSURA = "\u{0649}";

    protected const YA_TARGET = "\u{064A}";

    protected const YAA_WITH_HAMZA = "\u{0626}";

    protected const WAW_WITH_HAMZA = "\u{0624}";

    protected const WAW_TARGET = "\u{0648}";

    protected const PUNCTUATION_MAP = [
        '؟' => ' ',
        '،' => ' ',
        '؛' => ' ',
        '«' => ' ',
        '»' => ' ',
        'ـ' => ' ',
        '...' => ' ',
        '..' => ' ',
        '!' => ' ',
        '?' => ' ',
        '.' => ' ',
        ',' => ' ',
        ';' => ' ',
        ':' => ' ',
        '"' => ' ',
        "'" => ' ',
        '(' => ' ',
        ')' => ' ',
        '[' => ' ',
        ']' => ' ',
        '{' => ' ',
        '}' => ' ',
        '-' => ' ',
        '_' => ' ',
        '&' => ' ',
        '#' => ' ',
        '@' => ' ',
    ];

    protected const ENGLISH_CONTRACTIONS = [
        "don't" => 'do not',
        "can't" => 'cannot',
        "won't" => 'will not',
        "isn't" => 'is not',
        "aren't" => 'are not',
        "wasn't" => 'was not',
        "weren't" => 'were not',
        "hasn't" => 'has not',
        "haven't" => 'have not',
        "hadn't" => 'had not',
        "doesn't" => 'does not',
        "didn't" => 'did not',
        "couldn't" => 'could not',
        "wouldn't" => 'would not',
        "shouldn't" => 'should not',
        "mightn't" => 'might not',
        "mustn't" => 'must not',
        "i'm" => 'i am',
        "you're" => 'you are',
        "he's" => 'he is',
        "she's" => 'she is',
        "it's" => 'it is',
        "we're" => 'we are',
        "they're" => 'they are',
        "i've" => 'i have',
        "you've" => 'you have',
        "we've" => 'we have',
        "they've" => 'they have',
        "i'll" => 'i will',
        "you'll" => 'you will',
        "he'll" => 'he will',
        "she'll" => 'she will',
        "it'll" => 'it will',
        "we'll" => 'we will',
        "they'll" => 'they will',
        "i'd" => 'i would',
        "you'd" => 'you would',
        "he'd" => 'he would',
        "she'd" => 'she would',
        "we'd" => 'we would',
        "they'd" => 'they would',
    ];

    public static function normalize(string $text): string
    {
        $text = trim($text);

        $text = preg_replace(self::ARABIC_TASHKEEL, '', $text);

        $text = str_replace(self::TATWEEL, '', $text);

        foreach (self::ALIF_VARIANTS as $variant) {
            $text = str_replace($variant, self::ALIF_TARGET, $text);
        }

        $text = str_replace(self::TA_MARBUTA, self::HA_TARGET, $text);
        $text = str_replace(self::ALEF_MAQSURA, self::YA_TARGET, $text);
        $text = str_replace(self::YAA_WITH_HAMZA, self::YA_TARGET, $text);
        $text = str_replace(self::WAW_WITH_HAMZA, self::WAW_TARGET, $text);

        $lower = mb_strtolower($text, 'UTF-8');

        foreach (self::ENGLISH_CONTRACTIONS as $contracted => $expanded) {
            $lower = str_replace($contracted, $expanded, $lower);
        }

        foreach (self::PUNCTUATION_MAP as $char => $replacement) {
            $lower = str_replace($char, $replacement, $lower);
        }

        $lower = preg_replace('/\s+/', ' ', $lower);

        return trim($lower);
    }

    public static function normalizeForMatch(string $text): string
    {
        return self::normalize($text);
    }

    public static function words(string $text): array
    {
        $normalized = self::normalize($text);

        return array_values(array_filter(explode(' ', $normalized), fn ($w) => $w !== ''));
    }

    public static function containsAny(string $text, array $needles): bool
    {
        $normalized = self::normalize($text);

        foreach ($needles as $needle) {
            if ($needle === '') {
                continue;
            }

            $normalizedNeedle = self::normalize($needle);

            if (str_contains($normalized, $normalizedNeedle)) {
                return true;
            }
        }

        return false;
    }

    public static function containsAnyExactWord(string $text, array $words): bool
    {
        $normalizedWords = self::words($text);

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            $normalizedWord = self::normalize($word);

            if (in_array($normalizedWord, $normalizedWords)) {
                return true;
            }
        }

        return false;
    }

    public static function exactMatch(string $text, string $expected): bool
    {
        return self::normalize($text) === self::normalize($expected);
    }

    public static function startsWithAny(string $text, array $prefixes): bool
    {
        $normalized = self::normalize($text);

        foreach ($prefixes as $prefix) {
            $normalizedPrefix = self::normalize($prefix);

            if (str_starts_with($normalized, $normalizedPrefix)) {
                return true;
            }
        }

        return false;
    }
}
