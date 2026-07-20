<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'destination_account_id',
        'category_id',
        'client_id',
        'type',
        'amount',
        'converted_amount',
        'exchange_rate',
        'transaction_date',
        'description',
        'attachment_path',
        'tags',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'converted_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'transaction_date' => 'date',
            'tags' => 'array',
        ];
    }

    /**
     * Get the account that owns the transaction.
     *
     * @return BelongsTo<FinanceAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    /**
     * Get the destination account for transfers.
     *
     * @return BelongsTo<FinanceAccount, $this>
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'destination_account_id');
    }

    /**
     * Get the category that owns the transaction.
     *
     * @return BelongsTo<FinanceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    /**
     * Get the client associated with the transaction.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
