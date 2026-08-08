<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: int implements HasLabel
{
    case User = 0;
    case Admin = 1;

    public function getLabel(): string
    {
        return $this->name;
    }
}
