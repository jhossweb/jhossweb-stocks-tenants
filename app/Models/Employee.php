<?php

namespace App\Models;

use App\Enum\Employees\EmployeeStatus;
use App\Enum\TypeCoin;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'emp_name',
        'emp_document',
        'emp_phone',
        'emp_email',
        'emp_address',
        'emp_position',
        'emp_hire_date',
        'emp_base_salary',
        'emp_salary_coin',
        'emp_status',
    ];

    protected function casts(): array
    {
        return [
            'emp_hire_date' => 'date',
            'emp_base_salary' => 'decimal:2',
            'emp_status' => EmployeeStatus::class,
            'emp_salary_coin' => TypeCoin::class,
        ];
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'pay_employee_id');
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class, 'elo_employee_id');
    }

    public function damages()
    {
        return $this->hasMany(EmployeeDamage::class, 'eda_employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where('emp_status', EmployeeStatus::ACTIVE);
    }

    public function getFullNameAttribute(): string
    {
        return $this->emp_name;
    }
}
