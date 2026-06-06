<?php

namespace App\Models;

use App\Enum\Expense\ExpenseType;
use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'exc_name',
        'exc_type',
        'exc_description',
    ];

    protected function casts(): array
    {
        return [
            'exc_type' => ExpenseType::class,
        ];
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'exp_category_id');
    }
}
