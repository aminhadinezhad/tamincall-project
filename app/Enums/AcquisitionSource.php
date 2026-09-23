<?php

namespace App\Enums;

use App\Filament\Widgets\Concerns\ChartStyle;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * How the customer came to know Tamin Falat, asked when the call is recorded.
 *
 * Each source owns one colour: the same one in the report's donut and on the badge in the table,
 * so a colour always means the same source.
 */
enum AcquisitionSource: string implements HasColor, HasLabel
{
    case Website = 'website';
    case OnlineAds = 'online_ads';
    case BaleBot = 'bale_bot';
    case Outdoor = 'outdoor';
    case AgentMarketing = 'agent_marketing';
    case Referral = 'referral';

    public function getLabel(): string
    {
        return match ($this) {
            self::Website => 'سایت',
            self::OnlineAds => 'تبلیغات اینترنتی',
            self::BaleBot => 'ربات بله',
            self::Outdoor => 'محیطی',
            self::AgentMarketing => 'بازاریابی کارشناس',
            self::Referral => 'معرف',
        };
    }

    /** The slice colour in the donut, and the source of the badge colour below. */
    public function chartColor(): string
    {
        return ChartStyle::CATEGORICAL[array_search($this, self::cases(), true)];
    }

    /** @return array<int, string> */
    public function getColor(): array
    {
        return Color::hex($this->chartColor());
    }
}
