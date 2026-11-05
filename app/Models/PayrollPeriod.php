<?php

namespace App\Models;

use App\Concerns\HasDocumentLifecycle;
use App\Concerns\HasVoidability;
use App\Enum\Payroll\PayrollPeriodStatus;
use Database\Factories\PayrollPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    /** @use HasFactory<PayrollPeriodFactory> */
    use HasDocumentLifecycle, HasFactory, HasVoidability;

    protected $appends = ['can_void', 'void_blocked_reason'];

    protected $fillable = [
        'is_active',
        'voided_at',
        'voided_by',
        'void_reason',
        'pp_start_date',
        'pp_end_date',
        'pp_payment_date',
        'pp_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'voided_at' => 'datetime',
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
