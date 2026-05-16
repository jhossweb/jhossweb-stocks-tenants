<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsReceivable extends Model
{
    /** @use HasFactory<\Database\Factories\AccountsReceivableFactory> */
    use HasFactory;

    protected $fillable = [
        'acr_sls_number',
        'acr_total',
        'acr_balance',
        'acr_status',
    ];

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'acr_sls_number', 'sls_number');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('acr_status', $status);
    }

    public function isPaid(): bool
    {
        return $this->acr_status === 'paid' || $this->acr_balance <= 0;
    }
}
