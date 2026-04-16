<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionTypeFactory> */
    use HasFactory;

    protected $fillable = [
        "trans_type_code",
        "trans_type_description",
        "trans_type_factor",
    ];
}
