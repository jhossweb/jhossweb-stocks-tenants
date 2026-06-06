<?php

namespace App\Models;

use App\Enum\Payroll\PayrollPeriodStatus;
use Database\Factories\PayrollPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    /** @use HasFactory<PayrollPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'pp_start_date',
        'pp_end_date',
        'pp_payment_date',
        'pp_status',
    ];

    protected function casts(): array
    {
        return [
            'pp_start_date' => 'date',
            'pp_end_date' => 'date',
            'pp_payment_date' => 'date',
            'pp_status' => PayrollPeriodStatus::class,
        ];
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'pay_period_id');
    }

    public function scopePending($query)
    {
        return $query->where('pp_status', PayrollPeriodStatus::PENDING);
    }

    public function isPaid(): bool
    {
        return $this->pp_status === PayrollPeriodStatus::PAID;
    }
}
