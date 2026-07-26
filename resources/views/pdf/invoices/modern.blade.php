@php
    $getStorageFilePath = function(?string $path) {
        if (! $path) return null;
        if (file_exists(storage_path('app/public/' . $path))) return storage_path('app/public/' . $path);
        if (file_exists(storage_path('app/private/' . $path))) return storage_path('app/private/' . $path);
        if (file_exists(public_path('storage/' . $path))) return public_path('storage/' . $path);
        return null;
    };
    $appLogoSetting = \App\Models\Setting::getValue('app_logo', 'logo/logo-orbit.png');
    $logoFilePath = $getStorageFilePath($invoice->logo_path) ?? $getStorageFilePath($appLogoSetting);
    $accountLogoFilePath = $invoice->financeAccount ? $getStorageFilePath($invoice->financeAccount->logo_path) : null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: '{{ $invoice->font_family }}', sans-serif;
            color: #1f2937;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
        }
        /* Spacing presets */
        .spacing-compact td, .spacing-compact th { padding: 4px 8px; }
        .spacing-cozy td, .spacing-cozy th { padding: 8px 12px; }
        .spacing-spacious td, .spacing-spacious th { padding: 14px 20px; }

        .spacing-compact .spacer { height: 10px; }
        .spacing-cozy .spacer { height: 20px; }
        .spacing-spacious .spacer { height: 35px; }

        /* Accent bar style */
        .accent-header {
            border-top: 6px solid {{ $invoice->color_accent }};
            padding-top: 15px;
        }

        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: {{ $invoice->color_accent }};
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        table.meta-table, table.items-table {
            border-collapse: collapse;
        }
        
        table.items-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        
        table.items-table td {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .totals-section {
            width: 300px;
            float: right;
            margin-top: 20px;
        }
        
        .totals-table td {
            padding: 5px 0;
        }
        
        .total-row {
            font-size: 16px;
            font-weight: bold;
            color: {{ $invoice->color_accent }};
            border-top: 1px solid #e5e7eb;
        }
        
        .notes-section {
            margin-top: 50px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            font-size: 11px;
            color: #6b7280;
        }

        .header-custom-text {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="spacing-{{ $invoice->spacing }}">
    <div class="container accent-header">
        
        @if ($invoice->header_text)
            <div class="header-custom-text">
                {!! nl2br(e($invoice->header_text)) !!}
            </div>
        @endif

        <!-- Layout Header using classic tables for Dompdf compliance -->
        <table class="w-full">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    @if ($invoice->logo_path && file_exists(public_path('storage/' . $invoice->logo_path)))
                        <img src="{{ public_path('storage/' . $invoice->logo_path) }}" style="max-height: 60px; max-width: 200px;">
                        @if ($invoice->brand_name)
                            <div style="font-size: 13px; font-weight: bold; color: #4b5563; margin-top: 5px;">{{ $invoice->brand_name }}</div>
                        @endif
                    @else
                        <div style="font-size: 20px; font-weight: bold; color: #4b5563;">
                            {{ $invoice->brand_name ?: $invoice->user->name }}
                        </div>
                    @endif
                </td>
                <td style="width: 50%; vertical-align: top;" class="text-right">
                    <div class="invoice-title">INVOICE</div>
                    <div style="color: #6b7280; font-weight: 500; margin-top: 5px;">#{{ $invoice->invoice_number }}</div>
                    <div style="color: #6b7280; font-size: 11px; margin-top: 5px;">
                        <div>Terbit: {{ $invoice->issue_date->format('d M Y') }}</div>
                        <div style="margin-top: 2px;">Tenggat: {{ $invoice->due_date->format('d M Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="spacer"></div>

        <!-- Info Section -->
        <table class="w-full">
            <tr>
                <td style="width: 45%; vertical-align: top;">
                    <div style="font-weight: bold; color: #4b5563; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Ditujukan Kepada</div>
                    <div style="margin-top: 5px; font-weight: 600;">{{ $invoice->client->name }}</div>
                    @if ($invoice->client->company)
                        <div>{{ $invoice->client->company }}</div>
                    @endif
                    <div>{{ $invoice->client->email }}</div>
                    @if ($invoice->client->tax_id)
                        <div style="font-size: 11px; color: #6b7280; margin-top: 2px;">NPWP/Tax ID: {{ $invoice->client->tax_id }}</div>
                    @endif
                    @if ($invoice->client->billing_address)
                        <div style="font-size: 11px; color: #4b5563; margin-top: 4px;">{!! nl2br(e($invoice->client->billing_address)) !!}</div>
                    @endif
                    @if ($invoice->project)
                        <div style="font-size: 11px; color: #4b5563; margin-top: 6px; border-top: 1px dashed #e5e7eb; padding-top: 4px;">
                            <strong>Proyek:</strong> {{ $invoice->project->name }}
                        </div>
                    @endif
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; vertical-align: top;" class="text-right">
                    <div style="font-weight: bold; color: #4b5563; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Penerbit</div>
                    <div style="margin-top: 5px; font-weight: 600;">{{ $invoice->user->name }}</div>
                    @if ($invoice->brand_name)
                        <div style="font-weight: bold; font-size: 12px; color: #4b5563; margin-top: 2px;">{{ $invoice->brand_name }}</div>
                    @endif
                    <div style="color: #4b5563; margin-top: 2px;">{{ $invoice->user->email }}</div>
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
                    <th style="width: 45%;">Rincian Deskripsi</th>
                    <th style="width: 15%; text-align: right;">Jumlah</th>
                    <th style="width: 20%; text-align: right;">Harga Satuan</th>
                    <th style="width: 20%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td style="vertical-align: top;">
                            <div style="font-weight: 600;">{{ $item->description }}</div>
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
                    <td style="width: 50%; color: #4b5563;">Subtotal:</td>
                    <td style="width: 50%;" class="text-right">{{ $symbol }}{{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if ($invoice->tax_rate > 0)
                    <tr>
                        <td style="color: #4b5563;">Pajak ({{ $invoice->tax_rate }}%):</td>
                        <td class="text-right">{{ $symbol }}{{ number_format(($invoice->tax_rate / 100) * $invoice->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if ($invoice->discount_amount > 0)
                    <tr>
                        <td style="color: #4b5563;">
                            Diskon 
                            @if ($invoice->discount_type === 'percentage')
                                ({{ $invoice->discount_amount }}%)
                            @endif:
                        </td>
                        <td class="text-right" style="color: #ef4444;">
                            -{{ $symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->discount_amount / 100) * $invoice->subtotal : $invoice->discount_amount, 2, ',', '.') }}
                        </td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>Total:</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($invoice->total, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <!-- Notes / Footer -->
        <div class="notes-section">
            @if ($invoice->financeAccount)
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; color: #374151; margin-bottom: 5px;">Metode Pembayaran / Rincian Rekening:</div>
                    <div style="padding: 8px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; display: table; width: 320px;">
                        @if ($accountLogoFilePath)
                            <div style="display: table-cell; vertical-align: top; width: 50px; padding-right: 10px;">
                                <img src="{{ $accountLogoFilePath }}" style="max-width: 40px; max-height: 40px; width: auto; height: auto; border-radius: 3px; border: 1px solid #e5e7eb; background-color: #ffffff;" />
                            </div>
                        @endif
                        <div style="display: table-cell; vertical-align: top;">
                            <div style="font-weight: bold; color: #111827; font-size: 11px;">{{ $invoice->financeAccount->name }} <span style="font-size: 8px; color: #6b7280; font-weight: normal; text-transform: uppercase;">({{ $invoice->financeAccount->type }})</span></div>
                            @if ($invoice->financeAccount->account_number)
                                <div style="font-size: 11px; font-weight: bold; color: #111827; margin-top: 2px; font-family: monospace;">{{ $invoice->financeAccount->account_number }}</div>
                            @endif
                            @if ($invoice->financeAccount->account_holder)
                                <div style="font-size: 9px; color: #4b5563; margin-top: 1px;">a.n. {{ $invoice->financeAccount->account_holder }}</div>
                            @endif
                            @if ($invoice->financeAccount->notes)
                                <div style="font-size: 9px; color: #6b7280; margin-top: 4px; border-top: 1px solid #e5e7eb; padding-top: 4px; line-height: 1.3;">{!! nl2br(e($invoice->financeAccount->notes)) !!}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($invoice->notes)
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; color: #374151; margin-bottom: 5px;">Catatan Tambahan:</div>
                    <div>{!! nl2br(e($invoice->notes)) !!}</div>
                </div>
            @endif

            @if ($invoice->footer_text)
                <div style="text-align: center; margin-top: 20px; font-style: italic;">
                    {!! nl2br(e($invoice->footer_text)) !!}
                </div>
            @endif
        </div>

    </div>
</body>
</html>
