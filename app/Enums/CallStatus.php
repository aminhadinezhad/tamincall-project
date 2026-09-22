<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a customer call stands in the happy-call routine.
 */
enum CallStatus: string implements HasColor, HasLabel
{
    /** Referred to sales; the secretary still has to call the customer back. */
    case AwaitingFollowUp = 'awaiting';

    /** The customer was reached and the result is recorded. */
    case Done = 'done';

    /** Several attempts went unanswered; the call is closed without a result. */
    case Unreachable = 'unreachable';

    public function getLabel(): string
    {
        return match ($this) {
            self::AwaitingFollowUp => 'در انتظار پیگیری',
            self::Done => 'پیگیری شد',
            self::Unreachable => 'پاسخ نداد',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AwaitingFollowUp => 'warning',
            self::Done => 'success',
            self::Unreachable => 'gray',
        };
    }
}
