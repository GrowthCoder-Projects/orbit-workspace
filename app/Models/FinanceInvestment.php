<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'type',
        'shares_quantity',
        'average_buy_price',
        'current_price',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'shares_quantity' => 'decimal:6',
            'average_buy_price' => 'decimal:2',
            'current_price' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }
}
