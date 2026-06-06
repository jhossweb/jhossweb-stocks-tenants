<?php

namespace App\Models;

use Database\Factories\PayrollDeductionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends Model
{
    /** @use HasFactory<PayrollDeductionFactory> */
    use HasFactory;

    protected $fillable = [
        'pde_payroll_id',
        'pde_deductible_type',
        'pde_deductible_id',
        'pde_percentage',
        'pde_amount',
    ];

    protected function casts(): array
    {
        return [
            'pde_percentage' => 'decimal:2',
            'pde_amount' => 'decimal:2',
        ];
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'pde_payroll_id');
    }

    public function deductible()
    {
        return $this->morphTo('pde_deductible');
    }
}
