<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfController extends Controller
{
    /**
     * Render the invoice as a PDF stream.
     */
    public function download(Invoice $invoice)
    {
        $invoice->load(['client', 'project', 'items', 'user', 'financeAccount']);

        $template = $invoice->template_name;
        if (!in_array($template, ['modern', 'classic', 'minimalist'])) {
            $template = 'modern';
        }

        // Set paper size & orientation
        $pdf = Pdf::loadView("pdf.invoices.{$template}", [
            'invoice' => $invoice,
        ]);

        $pdf->setPaper('a4', 'portrait');

        // Stream PDF inline
        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }
}
