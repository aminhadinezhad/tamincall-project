<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * A person buying for themselves, or a company / organisation.
 */
enum CustomerType: string implements HasLabel
{
    case Individual = 'individual';
    case Legal = 'legal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Individual => 'حقیقی',
            self::Legal => 'حقوقی',
        };
    }
}
