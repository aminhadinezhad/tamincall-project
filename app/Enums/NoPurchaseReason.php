<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Why a referred customer did not buy, as told on the follow-up call.
 */
enum NoPurchaseReason: string implements HasLabel
{
    case Price = 'price';
    case OutOfStock = 'out_of_stock';
    case DeliveryTime = 'delivery_time';
    case BoughtElsewhere = 'bought_elsewhere';
    case Undecided = 'undecided';
    case NotContacted = 'not_contacted';
    case Quality = 'quality';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Price => 'قیمت بالا',
            self::OutOfStock => 'موجود نبودن کالا',
            self::DeliveryTime => 'زمان تحویل طولانی',
            self::BoughtElsewhere => 'از جای دیگری خرید کرد',
            self::Undecided => 'هنوز تصمیم نگرفته',
            self::NotContacted => 'کارشناس فروش پیگیری نکرد',
            self::Quality => 'کیفیت یا مشخصات کالا',
            self::Other => 'سایر',
        };
    }
}
