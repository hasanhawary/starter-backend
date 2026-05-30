<?php

namespace AiChat\Support;

class TenantResolver
{
    protected static ?string $tenantId = null;

    protected static ?string $resolverClass = null;

    public static function setResolver(string $class): void
    {
        static::$resolverClass = $class;
    }

    public static function resolve(): ?string
    {
        if (static::$tenantId !== null) {
            return static::$tenantId;
        }

        if (static::$resolverClass === null) {
            return null;
        }

        $resolver = app(static::$resolverClass);

        if (method_exists($resolver, 'resolve')) {
            static::$tenantId = $resolver->resolve();
        }

        return static::$tenantId;
    }

    public static function setTenantId(string $tenantId): void
    {
        static::$tenantId = $tenantId;
    }

    public static function clear(): void
    {
        static::$tenantId = null;
    }
}
