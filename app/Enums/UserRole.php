<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Secretaries record calls and follow-ups; managers also see the reports and manage people.
 */
enum UserRole: string implements HasLabel
{
    case Manager = 'manager';
    case Secretary = 'secretary';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manager => 'مدیر',
            self::Secretary => 'منشی',
        };
    }
}
