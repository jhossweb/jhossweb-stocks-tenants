<?php

namespace App\Models;

use App\Enum\Payroll\DamageStatus;
use App\Enum\TypeCoin;
use Database\Factories\EmployeeDamageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDamage extends Model
{
    /** @use HasFactory<EmployeeDamageFactory> */
    use HasFactory;

    protected $fillable = [
        'eda_employee_id',
        'eda_amount',
        'eda_remaining_balance',
        'eda_coin',
        'eda_valorcam',
        'eda_description',
        'eda_status',
    ];

    protected function casts(): array
    {
        return [
            'eda_amount' => 'decimal:2',
            'eda_remaining_balance' => 'decimal:2',
            'eda_valorcam' => 'decimal:4',
            'eda_status' => DamageStatus::class,
            'eda_coin' => TypeCoin::class,
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'eda_employee_id');
    }

    public function deductions()
    {
        return $this->morphMany(PayrollDeduction::class, 'pde_deductible');
    }

    public function scopeActive($query)
    {
        return $query->where('eda_status', DamageStatus::ACTIVE);
    }

    public function isPaid(): bool
    {
        return $this->eda_status === DamageStatus::PAID || $this->eda_remaining_balance <= 0;
    }

    public function reduceBalance(float $amount): void
    {
        $this->eda_remaining_balance -= $amount;
        if ($this->eda_remaining_balance <= 0) {
            $this->eda_remaining_balance = 0;
            $this->eda_status = DamageStatus::PAID;
        }
        $this->save();
    }
}
