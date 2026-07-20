<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #000000;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
        }
        /* Spacing presets */
        .spacing-compact td, .spacing-compact th { padding: 4px 0; }
        .spacing-cozy td, .spacing-cozy th { padding: 7px 0; }
        .spacing-spacious td, .spacing-spacious th { padding: 12px 0; }

        .spacing-compact .spacer { height: 8px; }
        .spacing-cozy .spacer { height: 16px; }
        .spacing-spacious .spacer { height: 28px; }

        .w-full { width: 100%; }
        .text-right { text-align: right; }
        
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 0;
        }
        
        table.items-table {
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table.items-table th {
            border-bottom: 2px solid #000000;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            font-size: 10px;
        }
        
        table.items-table td {
            border-bottom: 1px solid #e5e5e5;
        }
        
        .totals-section {
            width: 250px;
            float: right;
            margin-top: 15px;
        }
        
        .totals-table td {
            padding: 4px 0;
        }
        
        .total-row {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000000;
            border-bottom: 2px solid #000000;
            padding: 6px 0;
        }
        
        .notes-section {
            margin-top: 40px;
            font-size: 10px;
            color: #555555;
        }
    </style>
</head>
<body class="spacing-{{ $invoice->spacing }}">
    <div class="container">

        <!-- Header -->
        <table class="w-full">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div style="font-size: 16px; font-weight: bold;">
                        {{ $invoice->user->name }}
                    </div>
                    @if ($invoice->brand_name)
                        <div style="font-size: 12px; font-weight: bold; color: #444444; margin-top: 2px;">{{ $invoice->brand_name }}</div>
                    @endif
                    <div style="color: #555555;">{{ $invoice->user->email }}</div>
                </td>
                <td style="width: 50%; vertical-align: top;" class="text-right">
                    <h1 class="invoice-title">INVOICE</h1>
                    <div style="margin-top: 5px;">#{{ $invoice->invoice_number }}</div>
                    <div style="color: #555555; font-size: 11px; margin-top: 5px;">
                        <div>Terbit: {{ $invoice->issue_date->format('d M Y') }}</div>
                        <div style="margin-top: 2px;">Tenggat: {{ $invoice->due_date->format('d M Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="spacer"></div>

        <!-- Bill To -->
        <table class="w-full">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div style="color: #777777; font-size: 10px; text-transform: uppercase; font-weight: bold;">Ditujukan Kepada:</div>
                    <div style="font-weight: bold; font-size: 13px; margin-top: 3px;">{{ $invoice->client->name }}</div>
                    @if ($invoice->client->company)
                        <div>{{ $invoice->client->company }}</div>
                    @endif
                    <div>{{ $invoice->client->email }}</div>
                    @if ($invoice->client->tax_id)
                        <div>NPWP: {{ $invoice->client->tax_id }}</div>
                    @endif
                    @if ($invoice->client->billing_address)
                        <div style="margin-top: 5px; color: #333333; font-size: 11px;">{!! nl2br(e($invoice->client->billing_address)) !!}</div>
                    @endif
                    @if ($invoice->project)
                        <div style="margin-top: 8px; font-size: 11px; border-top: 1px dashed #777777; padding-top: 5px; color: #555555;">
                            <strong>Proyek:</strong> {{ $invoice->project->name }}
                        </div>
                    @endif
                </td>
                <td style="width: 50%; vertical-align: top;" class="text-right">
                    <!-- Empty right side for spacing -->
                </td>
            </tr>
        </table>

        <div class="spacer"></div>

@php
    $currencySymbols = [
        'IDR' => 'Rp ',
        'USD' => '$',
        'EUR' => '€',
        'SGD' => 'S$',
    ];
    $symbol = $currencySymbols[$invoice->currency] ?? $invoice->currency . ' ';
@endphp
        <!-- Items Table -->
        <table class="w-full items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Rincian Deskripsi</th>
                    <th style="width: 15%; text-align: right;">QTY</th>
                    <th style="width: 15%; text-align: right;">Harga</th>
                    <th style="width: 15%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td style="vertical-align: top;">
                            <div>{{ $item->description }}</div>
                        </td>
                        <td style="vertical-align: top;" class="text-right">
                            {{ number_format($item->quantity, 2, ',', '.') }}
                        </td>
                        <td style="vertical-align: top;" class="text-right">
                            {{ $symbol }}{{ number_format($item->unit_price, 2, ',', '.') }}
                        </td>
                        <td style="vertical-align: top;" class="text-right">
                            {{ $symbol }}{{ number_format($item->total, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Table -->
        <div class="totals-section">
            <table class="w-full totals-table">
                <tr>
                    <td style="color: #555555;">Subtotal:</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if ($invoice->tax_rate > 0)
                    <tr>
                        <td style="color: #555555;">Pajak ({{ $invoice->tax_rate }}%):</td>
                        <td class="text-right">{{ $symbol }}{{ number_format(($invoice->tax_rate / 100) * $invoice->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if ($invoice->discount_amount > 0)
                    <tr>
                        <td style="color: #555555;">
                            Diskon 
                            @if ($invoice->discount_type === 'percentage')
                                ({{ $invoice->discount_amount }}%)
                            @endif:
                        </td>
                        <td class="text-right" style="color: #555555;">
                            -{{ $symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->discount_amount / 100) * $invoice->subtotal : $invoice->discount_amount, 2, ',', '.') }}
                        </td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($invoice->total, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <!-- Notes / Footer -->
        <div class="notes-section">
            @if ($invoice->financeAccount)
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; margin-bottom: 3px;">Metode Pembayaran:</div>
                    <div style="padding: 6px; border: 1px solid #e5e5e5; display: table; width: 320px; border-radius: 2px;">
                        @if ($invoice->financeAccount->logo_path && file_exists(public_path('storage/' . $invoice->financeAccount->logo_path)))
                            <div style="display: table-cell; vertical-align: top; width: 50px; padding-right: 10px;">
                                <img src="{{ public_path('storage/' . $invoice->financeAccount->logo_path) }}" style="max-width: 40px; max-height: 40px; width: auto; height: auto; border-radius: 2px; border: 1px solid #e5e5e5; background-color: #ffffff;" />
                            </div>
                        @endif
                        <div style="display: table-cell; vertical-align: top;">
                            <div style="font-weight: bold; color: #111827; font-size: 11px;">{{ $invoice->financeAccount->name }} <span style="font-size: 8px; color: #777777; font-weight: normal; text-transform: uppercase;">({{ $invoice->financeAccount->type }})</span></div>
                            @if ($invoice->financeAccount->account_number)
                                <div style="font-size: 11px; font-weight: bold; color: #111827; margin-top: 2px; font-family: monospace;">{{ $invoice->financeAccount->account_number }}</div>
                            @endif
                            @if ($invoice->financeAccount->account_holder)
                                <div style="font-size: 9px; color: #555555; margin-top: 1px;">a.n. {{ $invoice->financeAccount->account_holder }}</div>
                            @endif
                            @if ($invoice->financeAccount->notes)
                                <div style="font-size: 9px; color: #777777; margin-top: 4px; border-top: 1px solid #e5e5e5; padding-top: 4px; line-height: 1.3;">{!! nl2br(e($invoice->financeAccount->notes)) !!}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($invoice->notes)
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; margin-bottom: 3px;">Catatan Tambahan:</div>
                    <div>{!! nl2br(e($invoice->notes)) !!}</div>
                </div>
            @endif

            @if ($invoice->footer_text)
                <div style="text-align: center; margin-top: 20px; font-style: italic; border-top: 1px solid #e5e5e5; padding-top: 8px;">
                    {!! nl2br(e($invoice->footer_text)) !!}
                </div>
            @endif
        </div>

    </div>
</body>
</html>
