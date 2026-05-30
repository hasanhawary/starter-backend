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

    protected static ?array $normalizationMap = null;

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

        foreach (self::PUNCTUATION_MAP as $char => $replacement) {
            $text = str_replace($char, $replacement, $text);
        }

        $text = mb_strtolower($text, 'UTF-8');

        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public static function normalizeForMatch(string $text): string
    {
        return self::normalize($text);
    }

    public static function words(string $text): array
    {
        $normalized = self::normalize($text);

        return explode(' ', $normalized);
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
