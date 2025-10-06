<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 15px 10px;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #2c3e50;
            line-height: 1.3;
            font-size: 13px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
        }

        .page-container {
            background: white;
            min-height: 100vh;
            padding: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin: 10px;
            position: relative;
        }

        .header-box {
            margin-bottom: 15px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }

        .logo {
            max-width: 100px;
            max-height: 50px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            margin-bottom: 5px;
            margin-left: 10px;
        }

        .company-info {
            flex: 1;
            padding-left: 15px;
        }

        .logo-section {
            padding-right: 20px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .company-details {
            font-size: 11px;
            color: #ecf0f1;
            line-height: 1.2;
        }

        .document-info {
            text-align: right;
            padding-left: 20px;
        }

        .document-title {
            font-size: 26px;
            font-weight: 800;
            color: #e74c3c;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .document-number {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 6px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .document-date {
            font-size: 12px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: black;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            box-shadow: 0 3px 6px rgba(231, 76, 60, 0.3);
            position: relative;
            overflow: hidden;
        }

        .document-date::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .document-date:hover::before {
            left: 100%;
        }



        .billing-table {
            width: 100%;
            border-collapse: collapse;
        }

        .billing-table thead th {
            background-color: #f8f9fa !important;
            color: #2c3e50 !important;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .billing-table td {
            padding: 12px;
            font-size: 12px;
            vertical-align: top;
        }

        .billing-table tr:last-child td {
            border-bottom: none;
        }

        .billing-table thead {
            display: table-header-group;
        }

        .billing-table thead th {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .items-box {
            margin-bottom: 20px;
            margin-top: 20px;
            padding: 15px;

            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .items-box-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .items-table {
            width: 100%;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .items-table th {
            background-color: #3498db !important;
            color: white !important;
            padding: 12px 8px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }



        .items-table td {
            padding: 7px;
            font-size: 12px;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .items-table tr:hover {
            background-color: #fdf2f2;
        }

        .totals-box {
            margin-bottom: 20px;
        }

        .totals-section {
            width: 300px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .totals-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 12px;
        }

        .totals-table tr:last-child {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            font-weight: 700;
            font-size: 14px;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
        }

        .total-highlight {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #28a745;
            border-radius: 4px;
            padding: 8px;
            margin-top: 8px;
            text-align: center;
        }

        .total-highlight strong {
            font-size: 15px;
            color: #155724;
        }

        .terms-section {
            margin-top: 15px;
            padding: 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 6px;
            border-left: 3px solid #e74c3c;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .terms-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 6px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .terms-content {
            font-size: 12px;
            line-height: 1.3;
            color: #34495e;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 50px;
            color: rgba(231, 76, 60, 0.03);
            font-weight: 900;
            z-index: -1;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <div class="watermark">INVOICE</div>
    <div class="page-container">
        <div class="content-wrapper">
            <!-- Header Box -->
            <div class="header-box">
                <div class="logo-section" style="float: left; width: 70%;">
                    <img src="{{ public_path('images/logo/logo.png') }}" alt="Company Logo" class="logo">
                    <div class="company-info">
                        <div class="company-name">{{ $settings['company_name'] ?? 'Your Company Name' }}</div>

                    </div>
                </div>
                <div class="document-info" style="float: right; width: 30%;">
                    <div class="document-title">INVOICE</div>
                    <div class="document-number">No. {{ $invoice->invoice_number }}</div>
                    <div class="document-date">Date: {{ $invoice->created_at->format('F j, Y') }}</div>
                </div>
                <div style="clear: both;"></div>
            </div>

            <!-- Billing Box -->
            <div class="billing-box">
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Invoice To</th>
                            <th style="width: 60%;">Invoice From</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width: 40%;">
                                @if ($invoice->client)
                                    <div><strong>{{ $invoice->client->name }}</strong></div>
                                    <div><b>Address:</b> {{ $invoice->client->address ?? 'N/A' }}</div>
                                    <div><b>Phone:</b> {{ $invoice->client->phone ?? 'N/A' }}</div>
                                    <div><b>Email:</b> {{ $invoice->client->email ?? 'N/A' }}</div>
                                @elseif($invoice->quotation && $invoice->quotation->clientDetail)
                                    <div><strong>{{ $invoice->quotation->clientDetail->name }}</strong></div>
                                    <div><b>Address:</b> {{ $invoice->quotation->clientDetail->address ?? 'N/A' }}
                                    </div>
                                    <div><b>Phone:</b> {{ $invoice->quotation->clientDetail->phone ?? 'N/A' }}</div>
                                    <div><b>Email:</b> {{ $invoice->quotation->clientDetail->email ?? 'N/A' }}</div>
                                @elseif($invoice->quotation && $invoice->quotation->leadDetail)
                                    <div><strong>{{ $invoice->quotation->leadDetail->name }}</strong></div>
                                    <div><b>Contact:</b> {{ $invoice->quotation->leadDetail->contact_person ?? 'N/A' }}
                                    </div>
                                    <div><b>Phone:</b> {{ $invoice->quotation->leadDetail->phone ?? 'N/A' }}</div>
                                    <div><b>Email:</b> {{ $invoice->quotation->leadDetail->email ?? 'N/A' }}</div>
                                @else
                                    <div>No billing information available.</div>
                                @endif
                            </td>
                            <td style="width: 60%;">
                                <div><strong>{{ $settings['company_name'] ?? 'Your Company Name' }}</strong></div>
                                <div><b>Address:</b> {{ $settings['address'] ?? 'Company Address' }}</div>
                                <div><b>Phone:</b> {{ $settings['phone'] ?? 'N/A' }}</div>
                                <div><b>Email:</b> {{ $settings['email'] ?? 'info@company.com' }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Items Box -->
            <div class="items-box">
                <div class="items-box-title">Items & Services</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 45%;">Name</th>
                            <th style="width: 10%; text-align: right;">QTY</th>
                            <th style="width: 20%; text-align: right;">UNIT PRICE</th>
                            <th style="width: 20%; text-align: right;">GST</th>
                            <th style="width: 20%; text-align: right;">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['name'] }}</td>
                                <td style="text-align: right;">{{ $item['quantity'] }}</td>
                                <td style="text-align: right;">{{ number_format($item['unit_price'], 2) }}</td>
                                <td style="text-align: right;">18%</td>
                                <td style="text-align: right;">{{ number_format($item['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center;">No item is selected.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Totals Box -->
            <div class="totals-box">
                <div class="totals-section" style="float: right;">
                    <table class="totals-table">
                        <tr>
                            <td>Subtotal:</td>
                            <td style="text-align: right;">{{ number_format($invoice->sub_total, 2) }}</td>
                        </tr>
                        @if ($invoice->discount > 0)
                            <tr>
                                <td>Discount:</td>
                                <td style="text-align: right;">-{{ number_format($invoice->discount, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Tax:</td>
                            <td style="text-align: right;">{{ number_format($invoice->tax, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>TOTAL:</strong></td>
                            <td style="text-align: right;"><strong>{{ number_format($invoice->total, 2) }}</strong>
                            </td>
                        </tr>
                    </table>

                    <div class="total-highlight">
                        <strong>Total Amount: {{ number_format($invoice->total, 2) }}</strong>
                    </div>

                </div>

            </div>

            @if ($invoice->payment_terms)
                <div class="terms-section">
                    <div class="terms-title">Payment Terms</div>
                    <div class="terms-content">{!! nl2br(e($invoice->payment_terms)) !!}</div>
                </div>
            @endif

            @if ($invoice->terms_conditions)
                <div class="terms-section">
                    <div class="terms-title">Terms & Conditions</div>
                    <div class="terms-content">{!! nl2br(e($invoice->terms_conditions)) !!}</div>
                </div>
            @endif

        </div>


    </div>
</body>

</html>
