<?php

namespace App\Models;

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
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to || $amount === 0) {
            return $amount;
        }

        $usd = (float) ($rate->rate_usd ?: 1);
        $cop = (float) ($rate->rate_cop ?: 1);
        $eur = (float) ($rate->rate_eur ?: 1);
        $copBs = (float) ($rate->rate_cop_bs ?: 0);

        return match (true) {
            // USD →
            $from === 'USD' && $to === 'COP' => $amount * $cop,
            $from === 'USD' && in_array($to, ['BS', 'VES']) => $amount * $usd,
            $from === 'USD' && $to === 'EUR' => $amount * $eur,
            // COP →
            $from === 'COP' && $to === 'USD' => $amount / $cop,
            $from === 'COP' && in_array($to, ['BS', 'VES']) => $copBs > 0 ? $amount / $copBs : $amount * $usd / $cop,
            $from === 'COP' && $to === 'EUR' => $amount / $cop * $eur,
            // BS/VES →
            in_array($from, ['BS', 'VES']) && $to === 'USD' => $amount / $usd,
            in_array($from, ['BS', 'VES']) && $to === 'COP' => $copBs > 0 ? $amount * $copBs : $amount * $cop / $usd,
            in_array($from, ['BS', 'VES']) && $to === 'EUR' => $amount / $usd * $eur,
            // EUR →
            $from === 'EUR' && $to === 'USD' => $amount / $eur,
            $from === 'EUR' && $to === 'COP' => $amount / $eur * $cop,
            $from === 'EUR' && in_array($to, ['BS', 'VES']) => $amount / $eur * $usd,
            // Fallback
            default => $amount,
        };
    }
}
