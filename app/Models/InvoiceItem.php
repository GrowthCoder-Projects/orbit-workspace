<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_amount',
        'total',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'float',
            'tax_rate' => 'float',
            'discount_amount' => 'float',
            'total' => 'float',
        ];
    }

    /**
     * Boot the model callbacks.
     */
    protected static function booted(): void
    {
        static::saving(static function (InvoiceItem $item) {
            $subtotal = $item->quantity * $item->unit_price;
            $tax = ($item->tax_rate / 100) * $subtotal;
            $discount = $item->discount_amount;
            $item->total = max(0.00, $subtotal + $tax - $discount);
        });

        static::saved(static function (InvoiceItem $item) {
            if ($item->invoice) {
                $item->invoice->recalculateTotals();
            }
        });

        static::deleted(static function (InvoiceItem $item) {
            if ($item->invoice) {
                $item->invoice->recalculateTotals();
            }
        });
    }

    /**
     * Get the invoice that owns this item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
