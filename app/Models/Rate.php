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
}
