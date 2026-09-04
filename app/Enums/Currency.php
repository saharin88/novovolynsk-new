<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Currency: string implements HasLabel
{
    case UAH = 'UAH';
    case USD = 'USD';
    case EUR = 'EUR';
    case PLN = 'PLN';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getLocale(): string
    {
        return match ($this) {
            self::UAH => 'uk_UA',
            self::EUR,
            self::USD => 'en_US',
            self::PLN => 'pl_PL',
        };
    }
}
