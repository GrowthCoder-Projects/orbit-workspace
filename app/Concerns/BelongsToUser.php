<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    /**
     * Boot the BelongsToUser trait.
     */
    protected static function bootBelongsToUser(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && ! $model->user_id) {
                $model->user_id = Auth::id();
            }
        });

        static::addGlobalScope('user_isolation', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }

    /**
     * Get the user that owns the model.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
