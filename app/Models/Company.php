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

    /**
     * La caché es central (drivers file/database en este proyecto) y no está
     * separada por tenant: la clave lleva el tenant_id para no filtrar datos
     * entre tenants.
     */
    public static function cacheKey(): string
    {
        return self::CACHE_KEY.'.'.(tenancy()->initialized ? tenant('id') : 'central');
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::cacheKey());
    }

    /**
     * Devuelve la empresa del tenant actual (o null en el portal central).
     *
     * Se cachea el ARRAY de atributos, no el modelo: cachear un Eloquent model en
     * stores base de datos/pgsql es frágil (serialización base64 vs ":" -> el
     * modelo se devuelve como __PHP_Incomplete_Class y el tipo ?self revienta).
     */
    public static function current(): ?self
    {
        $data = Cache::remember(self::cacheKey(), now()->addDay(), fn () => static::query()->first()?->getAttributes());

        if (! is_array($data) || $data === []) {
            return null;
        }

        $company = new static;
        $company->forceFill($data);

        return $company;
    }
}
