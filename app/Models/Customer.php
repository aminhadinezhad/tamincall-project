<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone', 'type', 'company', 'notes'])]
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * A deleted customer takes their calls out of the lists and the reports, and brings them back
     * when the customer is restored.
     */
    protected static function booted(): void
    {
        static::deleted(function (Customer $customer): void {
            if (! $customer->isForceDeleting()) {
                $customer->calls()->delete();
            }
        });

        static::restored(fn (Customer $customer) => $customer->calls()->onlyTrashed()->restore());
    }

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
        ];
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Mobile numbers are kept as 09xxxxxxxxx whatever way they are typed: Persian or Arabic digits,
     * spaces and dashes, or a +98 / 0098 prefix.
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => self::normalizePhone($value));
    }

    public static function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $value = preg_replace('/\D/', '', $value);

        if (str_starts_with($value, '0098')) {
            $value = '0'.substr($value, 4);
        } elseif (str_starts_with($value, '98') && strlen($value) === 12) {
            $value = '0'.substr($value, 2);
        } elseif (str_starts_with($value, '9') && strlen($value) === 10) {
            $value = '0'.$value;
        }

        return $value;
    }
}
