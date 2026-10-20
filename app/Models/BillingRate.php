<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BillingRateFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tasa de cambio del billing central (schema `public`).
 *
 * Es independiente de la tabla `rates` por-tenant: el panel administrativo
 * necesita una fuente de tasas válida para todos los clientes y un histórico
 * que permita congelar la tasa al registrar un cobro.
 *
 * @method static BillingRateFactory factory()
 */
class BillingRate extends Model
{
    /** @use HasFactory<BillingRateFactory> */
    use HasFactory;

    protected $fillable = [
        'rate_date',
        'rate_usd',
        'rate_cop',
        'rate_eur',
        'rate_cop_bs',
    ];

    protected function casts(): array
    {
        return [
            'rate_date' => 'date',
            'rate_usd' => 'decimal:4',
            'rate_cop' => 'decimal:4',
            'rate_eur' => 'decimal:4',
            'rate_cop_bs' => 'decimal:4',
        ];
    }

    /**
     * Persiste la fecha sin hora para que la restricción UNIQUE por día
     * funcione igual en PostgreSQL y SQLite (esta última no trunca el tipo
     * `date` y guardaría un datetime completo).
     */
    protected function rateDate(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => $value === null
                ? null
                : Carbon::parse($value)->toDateString(),
        );
    }

    public static function latestRate(): ?self
    {
        return self::latest('rate_date')->first();
    }

    public static function forDate(\DateTimeInterface $date): ?self
    {
        return self::whereDate('rate_date', $date)->first();
    }
}
