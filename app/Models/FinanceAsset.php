<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'purchase_value',
        'current_value',
        'purchase_date',
        'depreciation_rate',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'purchase_date' => 'date',
            'depreciation_rate' => 'decimal:2',
        ];
    }
}
