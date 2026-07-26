<?php

namespace App\Observers;

use App\Models\FinanceAccount;
use App\Models\FinanceBudget;
use App\Models\FinanceTransaction;
use App\Notifications\ActivityNotification;
use App\Services\ActivityLogService;
use Carbon\Carbon;

class FinanceTransactionObserver
{
    /**
     * Handle the FinanceTransaction "created" event.
     */
    public function created(FinanceTransaction $transaction): void
    {
        if ($transaction->type === 'income') {
            $this->adjustBalance($transaction->account_id, $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $this->adjustBalance($transaction->account_id, -$transaction->amount);
        } elseif ($transaction->type === 'transfer') {
            $this->adjustBalance($transaction->account_id, -$transaction->amount);
            $this->adjustBalance(
                $transaction->destination_account_id,
                $transaction->converted_amount ?? $transaction->amount
            );
        }

        // Log activity
        $label = $transaction->description ?: ucfirst($transaction->type).' transaction';
        ActivityLogService::created($transaction, $label);

        // Check budget alert on expense creation
        if ($transaction->type === 'expense' && $transaction->category_id) {
            $this->checkBudgetAlert($transaction);
        }
    }

    /**
     * Handle the FinanceTransaction "updated" event.
     */
    public function updated(FinanceTransaction $transaction): void
    {
        // 1. Reverse the original transaction effect
        $oldType = $transaction->getOriginal('type');
        $oldAccountId = $transaction->getOriginal('account_id');
        $oldDestAccountId = $transaction->getOriginal('destination_account_id');
        $oldAmount = $transaction->getOriginal('amount');
        $oldConvertedAmount = $transaction->getOriginal('converted_amount');

        if ($oldType === 'income') {
            $this->adjustBalance($oldAccountId, -$oldAmount);
        } elseif ($oldType === 'expense') {
            $this->adjustBalance($oldAccountId, $oldAmount);
        } elseif ($oldType === 'transfer') {
            $this->adjustBalance($oldAccountId, $oldAmount);
            if ($oldDestAccountId) {
                $this->adjustBalance($oldDestAccountId, -($oldConvertedAmount ?? $oldAmount));
            }
        }

        // 2. Apply the new transaction effect
        if ($transaction->type === 'income') {
            $this->adjustBalance($transaction->account_id, $transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $this->adjustBalance($transaction->account_id, -$transaction->amount);
        } elseif ($transaction->type === 'transfer') {
            $this->adjustBalance($transaction->account_id, -$transaction->amount);
            $this->adjustBalance(
                $transaction->destination_account_id,
                $transaction->converted_amount ?? $transaction->amount
            );
        }

        // Log activity
        $label = $transaction->description ?: ucfirst($transaction->type).' transaction';
        ActivityLogService::updated($transaction, $label, ['amount', 'type', 'description', 'category_id', 'account_id', 'transaction_date']);
    }

    /**
     * Handle the FinanceTransaction "deleted" event.
     */
    public function deleted(FinanceTransaction $transaction): void
    {
        if ($transaction->type === 'income') {
            $this->adjustBalance($transaction->account_id, -$transaction->amount);
        } elseif ($transaction->type === 'expense') {
            $this->adjustBalance($transaction->account_id, $transaction->amount);
        } elseif ($transaction->type === 'transfer') {
            $this->adjustBalance($transaction->account_id, $transaction->amount);
            $this->adjustBalance(
                $transaction->destination_account_id,
                -($transaction->converted_amount ?? $transaction->amount)
            );
        }

        $label = $transaction->description ?: ucfirst($transaction->type).' transaction';
        ActivityLogService::deleted($transaction, $label);
    }

    /**
     * Check if a new expense causes a budget over-alert.
     */
    private function checkBudgetAlert(FinanceTransaction $transaction): void
    {
        $now = Carbon::now();

        $budget = FinanceBudget::where('category_id', $transaction->category_id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        if (! $budget) {
            return;
        }

        // Calculate total spent this month for this category
        $totalSpent = FinanceTransaction::where('type', 'expense')
            ->where('category_id', $transaction->category_id)
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->sum('amount');

        if ($totalSpent > $budget->amount) {
            $categoryName = $transaction->load('category')->category->name ?? 'Unknown';

            ActivityLogService::budgetAlert($budget, $categoryName, (float) $totalSpent, (float) $budget->amount);

            // Send Telegram notification for budget alert
            $user = auth()->user();
            if ($user) {
                $user->notify(new ActivityNotification(
                    title: '⚠️ Budget Terlampaui',
                    body: "Budget \"{$categoryName}\" bulan ini telah terlampaui: Rp ".number_format((float) $totalSpent, 0, ',', '.').' / Rp '.number_format((float) $budget->amount, 0, ',', '.'),
                ));
            }
        }
    }

    /**
     * Helper to adjust account balance.
     */
    private function adjustBalance(?int $accountId, float $amount): void
    {
        if (! $accountId) {
            return;
        }

        $account = FinanceAccount::find($accountId);
        if ($account) {
            $account->balance += $amount;
            $account->save();
        }
    }
}
