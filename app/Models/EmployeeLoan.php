<?php

namespace App\Models;

use App\Enum\Payroll\LoanStatus;
use App\Enum\TypeCoin;
use Database\Factories\EmployeeLoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    /** @use HasFactory<EmployeeLoanFactory> */
    use HasFactory;

    protected $fillable = [
        'elo_employee_id',
        'elo_amount',
        'elo_remaining_balance',
        'elo_coin',
        'elo_valorcam',
        'elo_description',
        'elo_status',
    ];

    protected function casts(): array
    {
        return [
            'elo_amount' => 'decimal:2',
            'elo_remaining_balance' => 'decimal:2',
            'elo_valorcam' => 'decimal:4',
            'elo_status' => LoanStatus::class,
            'elo_coin' => TypeCoin::class,
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'elo_employee_id');
    }

    public function deductions()
    {
        return $this->morphMany(PayrollDeduction::class, 'pde_deductible');
    }

    public function scopeActive($query)
    {
        return $query->where('elo_status', LoanStatus::ACTIVE);
    }

    public function isPaid(): bool
    {
        return $this->elo_status === LoanStatus::PAID || $this->elo_remaining_balance <= 0;
    }

    public function reduceBalance(float $amount): void
    {
        $this->elo_remaining_balance -= $amount;
        if ($this->elo_remaining_balance <= 0) {
            $this->elo_remaining_balance = 0;
            $this->elo_status = LoanStatus::PAID;
        }
        $this->save();
    }
}
