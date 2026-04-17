<?php

namespace App\Enum;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Entrata',
            self::EXPENSE => 'Uscita',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $type) => $type->value, self::cases());
    }
}
