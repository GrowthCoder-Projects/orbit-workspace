<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, BelongsToUser;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'client_id',
        'project_id',
        'invoice_number',
        'brand_prefix',
        'brand_name',
        'status',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'tax_rate',
        'discount_amount',
        'discount_type',
        'total',
        'template_name',
        'color_accent',
        'logo_path',
        'font_family',
        'spacing',
        'header_text',
        'footer_text',
        'notes',
        'paid_at',
        'finance_account_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'subtotal' => 'float',
            'tax_rate' => 'float',
            'discount_amount' => 'float',
            'total' => 'float',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the client that this invoice belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the project associated with the invoice.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the finance account associated with the payment.
     */
    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    /**
     * Get the items for the invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Generate the next sequential invoice number based on settings.
     */
    public static function generateNextNumber(?string $brandPrefix = null): string
    {
        // Try to get invoice settings from Setting table
        $settings = Setting::getValue('invoice_settings', [
            'brand_prefix' => '',
            'invoice_prefix' => 'INV',
            'next_number' => 1,
        ]);

        $brand = $brandPrefix ?? ($settings['brand_prefix'] ?? '');
        $prefix = $settings['invoice_prefix'] ?? 'INV';
        $nextNum = $settings['next_number'] ?? 1;

        $year = date('Y');

        // Look for the last invoice to prevent duplication
        do {
            $formattedNum = sprintf('%04d', $nextNum);
            $invoiceNumber = strtoupper($prefix);
            
            if (!empty($brand)) {
                $invoiceNumber .= '-' . strtoupper($brand);
            }
            
            $invoiceNumber .= '-' . $year . '-' . $formattedNum;
            $nextNum++;
            
            $exists = static::where('invoice_number', $invoiceNumber)->exists();
        } while ($exists);

        return $invoiceNumber;
    }

    /**
     * Recalculate invoice totals based on items.
     */
    public function recalculateTotals(): void
    {
        $this->loadMissing('items');
        
        $subtotal = 0.00;
        foreach ($this->items as $item) {
            $subtotal += $item->total;
        }

        $this->subtotal = $subtotal;

        // Calculate tax
        $tax = ($this->tax_rate / 100) * $subtotal;

        // Calculate discount
        $discount = 0.00;
        if ($this->discount_type === 'percentage') {
            $discount = ($this->discount_amount / 100) * $subtotal;
        } else {
            $discount = $this->discount_amount;
        }

        $this->total = max(0.00, $subtotal + $tax - $discount);
        $this->saveQuietly();
    }
}
