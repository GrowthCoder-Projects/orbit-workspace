<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request): Response
    {
        $query = Invoice::where('user_id', $request->user()->id)
            ->with(['client', 'project'])
            ->latest();

        // Filters
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        $invoices = $query->get();

        // Additional data for forms/filters
        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $accounts = FinanceAccount::orderBy('name')->get();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'clients' => $clients,
            'projects' => $projects,
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'status', 'client_id']),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(): Response
    {
        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $accounts = FinanceAccount::orderBy('name')->get();

        // Generate next automatic number
        $nextNumber = Invoice::generateNextNumber();

        return Inertia::render('Invoices/CreateEdit', [
            'clients' => $clients,
            'projects' => $projects,
            'accounts' => $accounts,
            'nextNumber' => $nextNumber,
        ]);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'brand_prefix' => 'nullable|string',
            'brand_name' => 'nullable|string|max:255',
            'status' => 'required|string|in:draft,sent,overdue,paid',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|max:3',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'required|string|in:fixed,percentage',
            'template_name' => 'required|string|in:modern,classic,minimalist',
            'color_accent' => 'required|string',
            'font_family' => 'required|string',
            'spacing' => 'required|string|in:compact,cozy,spacious',
            'header_text' => 'nullable|string',
            'footer_text' => 'nullable|string',
            'notes' => 'nullable|string',
            'finance_account_id' => 'nullable|exists:finance_accounts,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        // Save uploaded logo if exists
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        DB::transaction(function () use ($validated, $request, $logoPath) {
            $invoiceData = array_merge($validated, [
                'user_id' => $request->user()->id,
                'logo_path' => $logoPath,
            ]);

            unset($invoiceData['items']);

            $invoice = Invoice::create($invoiceData);

            foreach ($validated['items'] as $itemData) {
                $invoice->items()->create($itemData);
            }

            // Recalculate totals (item save booted callbacks trigger this too, but running it guarantees sync)
            $invoice->recalculateTotals();

            // Update next number setting
            $settings = Setting::getValue('invoice_settings', [
                'brand_prefix' => '',
                'invoice_prefix' => 'INV',
                'next_number' => 1,
            ]);

            // Extract the number part from the invoice number if it matches pattern
            if (preg_match('/-(\d+)$/', $validated['invoice_number'], $matches)) {
                $num = intval($matches[1]);
                if ($num >= $settings['next_number']) {
                    $settings['next_number'] = $num + 1;
                    Setting::setValue('invoice_settings', $settings);
                }
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Request $request, Invoice $invoice): Response
    {
        $invoice->load(['client', 'project', 'items', 'financeAccount']);
        $accounts = FinanceAccount::orderBy('name')->get();

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): Response
    {
        $invoice->load('items');
        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $accounts = FinanceAccount::orderBy('name')->get();

        return Inertia::render('Invoices/CreateEdit', [
            'invoice' => $invoice,
            'clients' => $clients,
            'projects' => $projects,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number,'.$invoice->id,
            'brand_prefix' => 'nullable|string',
            'brand_name' => 'nullable|string|max:255',
            'status' => 'required|string|in:draft,sent,overdue,paid',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|max:3',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'required|string|in:fixed,percentage',
            'template_name' => 'required|string|in:modern,classic,minimalist',
            'color_accent' => 'required|string',
            'font_family' => 'required|string',
            'spacing' => 'required|string|in:compact,cozy,spacious',
            'header_text' => 'nullable|string',
            'footer_text' => 'nullable|string',
            'notes' => 'nullable|string',
            'finance_account_id' => 'nullable|exists:finance_accounts,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $invoice->logo_path = $logoPath;
        }

        DB::transaction(function () use ($validated, $invoice) {
            $invoiceData = $validated;
            unset($invoiceData['items']);

            $invoice->update($invoiceData);

            // Recreate items
            $invoice->items()->delete();
            foreach ($validated['items'] as $itemData) {
                $invoice->items()->create($itemData);
            }

            $invoice->recalculateTotals();
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Mark invoice as paid and register transaction in the ledger.
     */
    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'finance_account_id' => 'required|exists:finance_accounts,id',
        ]);

        if ($invoice->status === 'paid') {
            return redirect()->back()->with('warning', 'Invoice is already paid.');
        }

        DB::transaction(function () use ($validated, $invoice) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'finance_account_id' => $validated['finance_account_id'],
            ]);

            // Get or create dynamic Invoice Payment category
            $category = FinanceCategory::firstOrCreate(
                ['name' => 'Invoice Payment', 'type' => 'income'],
                ['icon' => 'Receipt', 'color' => '#10b981']
            );

            // Create ledger entry
            FinanceTransaction::create([
                'account_id' => $validated['finance_account_id'],
                'category_id' => $category->id,
                'client_id' => $invoice->client_id,
                'type' => 'income',
                'amount' => $invoice->total,
                'transaction_date' => now()->toDateString(),
                'description' => 'Pembayaran Invoice: '.$invoice->invoice_number,
                'tags' => ['invoice', 'pembayaran-otomatis'],
            ]);
        });

        return redirect()->back()->with('success', 'Invoice marked as paid and transaction created.');
    }
}
