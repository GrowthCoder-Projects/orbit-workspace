<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Notifications\ActivityNotification;
use App\Services\ActivityLogService;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        ActivityLogService::created($invoice, $invoice->invoice_number);
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty('status')) {
            $from = $invoice->getOriginal('status');
            $to = $invoice->status;

            ActivityLogService::statusChanged($invoice, $invoice->invoice_number, $from, $to);

            // Send Telegram notification for invoice status changes
            $user = auth()->user();
            if ($user) {
                $user->notify(new ActivityNotification(
                    title: '🧾 Invoice Status Berubah',
                    body: "Invoice *{$invoice->invoice_number}* berubah dari *{$from}* ke *{$to}*",
                ));
            }

            return;
        }

        ActivityLogService::updated($invoice, $invoice->invoice_number, ['status', 'due_date', 'total', 'client_id', 'project_id']);
    }

    public function deleted(Invoice $invoice): void
    {
        ActivityLogService::deleted($invoice, $invoice->invoice_number);
    }
}
