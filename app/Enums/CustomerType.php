<?php

namespace App\Enums;

use App\Filament\Widgets\Concerns\ChartStyle;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * A person buying for themselves, or a company / organisation. Each type owns one colour: the same
 * one in the report's donut and on the badge in the table.
 */
enum CustomerType: string implements HasColor, HasLabel
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

    /** The slice colour in the donut, and the source of the badge colour below. */
    public function chartColor(): string
    {
        return match ($this) {
            self::Individual => ChartStyle::ORANGE,
            self::Legal => ChartStyle::BLUE,
        };
    }

    /** @return array<int, string> */
    public function getColor(): array
    {
        return Color::hex($this->chartColor());
    }
}
