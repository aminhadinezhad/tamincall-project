<?php

namespace App\Support;

use DateTimeInterface;
use Morilog\Jalali\Jalalian;

/**
 * Jalali dates and Persian digits for display. Everything is stored in Gregorian.
 */
class Persian
{
    public static function digits(string|int|float|null $value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /** 1405/06/31 */
    public static function date(?DateTimeInterface $date): string
    {
        return $date ? self::digits(Jalalian::fromDateTime($date)->format('Y/m/d')) : '';
    }

    /** 1405/06/31 - 14:05 */
    public static function dateTime(?DateTimeInterface $date): string
    {
        return $date ? self::digits(Jalalian::fromDateTime($date)->format('Y/m/d - H:i')) : '';
    }

    /** دوشنبه ۳۱ شهریور */
    public static function dayName(?DateTimeInterface $date): string
    {
        return $date ? self::digits(Jalalian::fromDateTime($date)->format('l j F')) : '';
    }
}
