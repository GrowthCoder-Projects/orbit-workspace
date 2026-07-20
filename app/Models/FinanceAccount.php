<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'account_number',
        'account_holder',
        'logo_path',
        'balance',
        'currency',
        'color',
        'notes',
    ];

    /**
     * Get the transactions where this account is the source.
     *
     * @return HasMany<FinanceTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'account_id');
    }

    /**
     * Get the transactions where this account is the destination (transfers).
     *
     * @return HasMany<FinanceTransaction, $this>
     */
    public function destinationTransactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'destination_account_id');
    }
}
