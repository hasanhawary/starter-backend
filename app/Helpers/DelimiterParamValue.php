<?php

namespace App\Helpers;

use BackedEnum;
use JsonSerializable;

readonly class DelimiterParamValue
{
    public string $type; // 'plain' | 'json' | 'enum'

    private function __construct(
        public string $formatted,
        string $type = 'plain',
    ) {
        $this->type = $type;
    }

    /**
     * A plain scalar value.
     *   name=John
     */
    public static function plain(mixed $value): self
    {
        return new self((string) $value, 'plain');
    }

    /**
     * A JSON value (array or JsonSerializable), e.g. translatable fields.
     *   name={"en":"John","ar":"جون"}
     */
    public static function json(array|JsonSerializable $value): self
    {
        return new self(json_encode($value, JSON_UNESCAPED_UNICODE), 'json');
    }

    /**
     * A backed enum — key becomes "enum_{$paramName}", value becomes "FQN@case".
     *   enum_status=App\Enums\StatusEnum@active
     *
     * Pass the enum case; the param key prefix "enum_" is added in buildMessage().
     */
    public static function enum(BackedEnum $enum): self
    {
        return new self(get_class($enum).'@'.$enum->name, 'enum');
    }
}
