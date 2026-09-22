<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How the customer came to know Tamin Falat, asked when the call is recorded.
 */
enum AcquisitionSource: string implements HasLabel
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
}
