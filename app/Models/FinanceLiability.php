<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceLiability extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'total_amount',
        'remaining_amount',
        'interest_rate',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'due_date' => 'date',
        ];
    }
}
