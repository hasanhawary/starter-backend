<?php

namespace App\Enum\Subscription;

enum PlanCycleEnum: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public static function resolve(string $value): string
    {
        return match ($value) {
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            default => $value,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }

    public function daysInCycle(): int
    {
        return match ($this) {
            self::Monthly => 30,
            self::Yearly => 365,
        };
    }
}
