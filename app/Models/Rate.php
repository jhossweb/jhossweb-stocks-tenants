<?php

namespace App\Models;

use App\Services\Finance\CurrencyConverterService;
use Database\Factories\RateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    /** @use HasFactory<RateFactory> */
    use HasFactory;

    protected $fillable = [
        'rate_date',
        'rate_usd',
        'rate_cop',
        'rate_eur',
        'rate_cop_bs',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_date' => 'date',
        ];
    }

    public static function convert(float $amount, string $from, string $to, self $rate): float
    {
        return app(CurrencyConverterService::class)
            ->convert($amount, $from, $to, $rate);
    }
}
