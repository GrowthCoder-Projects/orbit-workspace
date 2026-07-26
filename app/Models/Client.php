<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'tax_id',
        'billing_address',
        'notes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'paid_amount',
        'unpaid_amount',
        'overdue_amount',
        'lifetime_value',
    ];

    /**
     * Get the projects for the client.
     *
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the invoices for the client.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Accessor for paid invoices amount.
     */
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->invoices()->where('status', 'paid')->sum('total');
    }

    /**
     * Accessor for unpaid invoices amount.
     */
    public function getUnpaidAmountAttribute(): float
    {
        return (float) $this->invoices()->whereIn('status', ['sent', 'overdue'])->sum('total');
    }

    /**
     * Accessor for overdue invoices amount.
     */
    public function getOverdueAmountAttribute(): float
    {
        return (float) $this->invoices()->where('status', 'overdue')->sum('total');
    }

    /**
     * Accessor for lifetime value.
     */
    public function getLifetimeValueAttribute(): float
    {
        return (float) $this->invoices()->where('status', 'paid')->sum('total');
    }
}
