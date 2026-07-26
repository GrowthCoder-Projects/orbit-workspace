@php
    $getStorageFilePath = function(?string $path) {
        if (! $path) return null;
        if (file_exists(storage_path('app/public/' . $path))) return storage_path('app/public/' . $path);
        if (file_exists(storage_path('app/private/' . $path))) return storage_path('app/private/' . $path);
        if (file_exists(public_path('storage/' . $path))) return public_path('storage/' . $path);
        return null;
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
            font-family: 'Times-Roman', Georgia, serif;
            color: #111111;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 25px;
        }
        /* Spacing presets */
        .spacing-compact td, .spacing-compact th { padding: 4px 8px; }
        .spacing-cozy td, .spacing-cozy th { padding: 8px 12px; }
        .spacing-spacious td, .spacing-spacious th { padding: 14px 20px; }

        .spacing-compact .spacer { height: 12px; }
        .spacing-cozy .spacer { height: 24px; }
        .spacing-spacious .spacer { height: 40px; }

        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .classic-header {
            border-bottom: 2px double #333333;
            padding-bottom: 15px;
            text-align: center;
        }
        
        .invoice-title {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 6px;
            margin: 0 0 10px 0;
            text-align: center;
        }
        
        table.info-table {
            margin-top: 20px;
        }
        
        table.items-table {
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table.items-table th {
            border-top: 1px solid #333333;
            border-bottom: 1px solid #333333;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }
        
        table.items-table td {
            border-bottom: 1px dashed #cccccc;
        }
        
        .totals-section {
            width: 280px;
            float: right;
            margin-top: 25px;
        }
        
        .totals-table td {
            padding: 6px 0;
        }
        
        .total-row {
            font-size: 15px;
            font-weight: bold;
            border-top: 1px solid #333333;
            border-bottom: 2px double #333333;
        }
        
        .notes-section {
            margin-top: 60px;
            border-top: 1px solid #333333;
            padding-top: 20px;
            font-size: 11px;
        }

        .header-custom-text {
            font-size: 11px;
            color: #444444;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="spacing-{{ $invoice->spacing }}">
    <div class="container">
        
        @if ($invoice->header_text)
            <div class="header-custom-text">
                {!! nl2br(e($invoice->header_text)) !!}
            </div>
        @endif

        <div class="classic-header">
            <div class="invoice-title">INVOICE</div>
            <div style="font-size: 14px;">Nomor: <strong>#{{ $invoice->invoice_number }}</strong></div>
            <div style="font-size: 11px; color: #444444; margin-top: 5px; font-style: italic;">
                <span>Terbit: {{ $invoice->issue_date->format('d M Y') }}</span>
                <span style="margin: 0 10px;">|</span>
                <span>Tenggat: {{ $invoice->due_date->format('d M Y') }}</span>
            </div>
        </div>

        <div class="spacer"></div>

        <!-- Vendor & Client Information -->
        <table class="w-full info-table">
            <tr>
                <td style="width: 48%; vertical-align: top;">
                    <div style="font-weight: bold; font-style: italic; border-bottom: 1px solid #333333; padding-bottom: 3px; font-size: 11px; text-transform: uppercase;">Ditujukan Kepada</div>
                    <div style="margin-top: 8px; font-weight: bold; font-size: 14px;">{{ $invoice->client->name }}</div>
                    @if ($invoice->client->company)
                        <div>Perusahaan: {{ $invoice->client->company }}</div>
                    @endif
                    <div>Email: {{ $invoice->client->email }}</div>
                    @if ($invoice->client->tax_id)
                        <div>NPWP: {{ $invoice->client->tax_id }}</div>
                    @endif
                    @if ($invoice->client->billing_address)
                        <div style="margin-top: 5px; font-size: 11px;">Alamat:<br>{!! nl2br(e($invoice->client->billing_address)) !!}</div>
                    @endif
                    @if ($invoice->project)
                        <div style="margin-top: 8px; font-size: 11px; border-top: 1px dashed #333333; padding-top: 5px;">
                            <strong>Proyek:</strong> {{ $invoice->project->name }}
                        </div>
                    @endif
                </td>
                <td style="width: 4%;"></td>
                <td style="width: 48%; vertical-align: top;" class="text-right">
                    <div style="font-weight: bold; font-style: italic; border-bottom: 1px solid #333333; padding-bottom: 3px; font-size: 11px; text-transform: uppercase;">Penerbit</div>
                    <div style="margin-top: 8px; font-weight: bold; font-size: 14px;">{{ $invoice->user->name }}</div>
                    @if ($invoice->brand_name)
                        <div style="font-weight: bold; font-size: 12px; color: #333333; margin-top: 2px;">{{ $invoice->brand_name }}</div>
                    @endif
                    <div style="margin-top: 2px;">Email: {{ $invoice->user->email }}</div>
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
                    <th style="width: 50%;">Deskripsi Rincian</th>
                    <th style="width: 15%; text-align: right;">Jumlah</th>
                    <th style="width: 15%; text-align: right;">Harga Satuan</th>
                    <th style="width: 20%; text-align: right;">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td style="vertical-align: top; padding: 8px 4px;">
                            <strong>{{ $item->description }}</strong>
                        </td>
                        <td style="vertical-align: top; padding: 8px 4px;" class="text-right">
                            {{ number_format($item->quantity, 2, ',', '.') }}
                        </td>
                        <td style="vertical-align: top; padding: 8px 4px;" class="text-right">
                            {{ $symbol }}{{ number_format($item->unit_price, 2, ',', '.') }}
                        </td>
                        <td style="vertical-align: top; padding: 8px 4px;" class="text-right">
                            {{ $symbol }}{{ number_format($item->total, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="w-full totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if ($invoice->tax_rate > 0)
                    <tr>
                        <td>Pajak ({{ $invoice->tax_rate }}%):</td>
                        <td class="text-right">{{ $symbol }}{{ number_format(($invoice->tax_rate / 100) * $invoice->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if ($invoice->discount_amount > 0)
                    <tr>
                        <td>
                            Diskon 
                            @if ($invoice->discount_type === 'percentage')
                                ({{ $invoice->discount_amount }}%)
                            @endif:
                        </td>
                        <td class="text-right" style="color: #666666;">
                            -{{ $symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->discount_amount / 100) * $invoice->subtotal : $invoice->discount_amount, 2, ',', '.') }}
                        </td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td><strong>Total Akhir:</strong></td>
                    <td class="text-right"><strong>{{ $symbol }}{{ number_format($invoice->total, 2, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <!-- Notes / Footer -->
        <div class="notes-section">
            @if ($invoice->financeAccount)
                <div style="margin-bottom: 20px;">
                    <div style="font-weight: bold; margin-bottom: 5px; font-style: italic;">Metode Pembayaran / Rincian Rekening:</div>
                    <div style="padding: 8px; border: 1px solid #333333; display: table; width: 320px;">
                        @if ($accountLogoFilePath)
                            <div style="display: table-cell; vertical-align: top; width: 50px; padding-right: 10px;">
                                <img src="{{ $accountLogoFilePath }}" style="max-width: 40px; max-height: 40px; width: auto; height: auto; border-radius: 3px; border: 1px solid #cccccc; background-color: #ffffff;" />
                            </div>
                        @endif
                        <div style="display: table-cell; vertical-align: top;">
                            <div style="font-weight: bold; color: #111827; font-size: 11px;">{{ $invoice->financeAccount->name }} <span style="font-size: 8px; color: #6b7280; font-weight: normal; font-style: italic; text-transform: uppercase;">({{ $invoice->financeAccount->type }})</span></div>
                            @if ($invoice->financeAccount->account_number)
                                <div style="font-size: 11px; font-weight: bold; color: #111827; margin-top: 2px; font-family: monospace;">{{ $invoice->financeAccount->account_number }}</div>
                            @endif
                            @if ($invoice->financeAccount->account_holder)
                                <div style="font-size: 9px; color: #4b5563; margin-top: 1px; font-style: italic;">a.n. {{ $invoice->financeAccount->account_holder }}</div>
                            @endif
                            @if ($invoice->financeAccount->notes)
                                <div style="font-size: 9px; color: #6b7280; margin-top: 4px; border-top: 1px dashed #cccccc; padding-top: 4px; line-height: 1.3; font-style: italic;">{!! nl2br(e($invoice->financeAccount->notes)) !!}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($invoice->notes)
                <div style="margin-bottom: 20px;">
                    <div style="font-weight: bold; margin-bottom: 5px; font-style: italic;">Catatan Tambahan:</div>
                    <div>{!! nl2br(e($invoice->notes)) !!}</div>
                </div>
            @endif

            @if ($invoice->footer_text)
                <div style="text-align: center; margin-top: 30px; font-style: italic; border-top: 1px dashed #cccccc; padding-top: 10px;">
                    {!! nl2br(e($invoice->footer_text)) !!}
                </div>
            @endif
        </div>

    </div>
</body>
</html>
