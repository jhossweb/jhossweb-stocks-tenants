<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Company extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'company';

    protected $table = 'company';

    protected $fillable = [
        'cmp_name',
        'cmp_rif',
        'cmp_address',
        'cmp_phone',
        'cmp_email',
        'cmp_website',
        'cmp_logo',
    ];

    public static function current(): ?self
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), fn () => static::first());
    }
}
