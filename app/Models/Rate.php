<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    /** @use HasFactory<\Database\Factories\RateFactory> */
    use HasFactory;


    protected $fillable = [
        'rate_date',
        'rate_usd',
        'rate_cop',
        'rate_eur'
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
}
