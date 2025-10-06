<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Challan</title>
    <style>
        /* Enhanced DomPDF-friendly styles */
        @page {
            margin: 8mm;
            padding: 0;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            color: #000;
            background-color: #ffffff;
        }

        .container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 0 1px #e0e0e0;
            padding: 3mm;
        }

        .header {
            text-align: center;
            margin-bottom: 4mm;
            border-bottom: 2px solid #000;
            padding: 4mm 3mm 3mm;
            position: relative;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 4px;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 60mm;
            height: 1px;
            background-color: #000;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000;
            background-color: #ffffff;
            padding: 2mm 4mm;
            border-radius: 3px;
            display: inline-block;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .company-address {
            font-size: 8pt;
            margin-bottom: 1.5mm;
            line-height: 1.4;
            color: #000;
            background-color: #ffffff;
            padding: 1mm 2mm;
            border-radius: 2px;
            display: inline-block;
        }

        .contact-info {
            font-size: 8pt;
            font-weight: 500;
            color: #000;
            background-color: #ffffff;
            padding: 1mm 2mm;
            border-radius: 2px;
            display: inline-block;
        }

        .document-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 4mm 0;
            text-decoration: underline;
            text-decoration-thickness: 2px;
            text-underline-offset: 2px;
            letter-spacing: 1px;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            padding: 3mm 6mm;
            border-radius: 4px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
            page-break-inside: avoid;
            font-size: 8pt;
            table-layout: fixed;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        table th,
        table td {
            word-break: break-word;
            overflow-wrap: break-word;
        }

        table.details-table {
            border: 1px solid #000;
            border-radius: 2px;
        }

        table.details-table td {
            padding: 2mm;
            border: 1px solid #000;
            vertical-align: top;
            background-color: #fafafa;
        }

        table.details-table td:nth-child(even) {
            background-color: #f5f5f5;
        }

        .label {
            font-weight: bold;
            width: 25mm;
            background-color: #f0f0f0 !important;
        }

        table.items-table {
            border: 2px solid #000;
            border-radius: 3px;
            overflow: hidden;
        }

        table.items-table th,
        table.items-table td {
            border: 1px solid #000;
            padding: 1.5mm;
            text-align: center;
            vertical-align: middle;
        }

        table.items-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #000;
        }

        table.items-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        table.items-table tbody tr:hover {
            background-color: #f0f8ff;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 12mm;
            padding: 3mm 0;
        }

        .signature-box {
            width: 60mm;
            text-align: center;
            font-size: 8pt;
            padding: 2mm;
            border-radius: 3px;
            background-color: #fafafa;
        }

        .signature-line {
            margin: 8mm auto 2mm;
            width: 40mm;
            position: relative;
        }

        .signature-line::after {
            content: '';
            position: absolute;
            top: -1px;
            left: 0;
            width: 100%;
            height: 1px;
            background-color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 4mm;
            font-size: 7pt;
            font-style: italic;
            padding: 3mm 4mm;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            border-radius: 4px;
            color: #000;
        }

        .amount-words {
            margin: 3mm 0;
            font-size: 8pt;
            padding: 3mm 4mm;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 4px;
            border-left: 4px solid orangered;
            color: #000;
            font-weight: 500;
        }

        .gst-info {
            text-align: right;
            margin-bottom: 3mm;
            font-size: 8pt;
            padding: 2mm 3mm;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            border-radius: 4px;
            display: inline-block;
            float: right;
            color: #000;
            font-weight: 500;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .avoid-break {
            page-break-inside: avoid;
        }

        .total-row {
            font-weight: bold;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%) !important;
        }

        .total-row td {
            border-top: 2px solid #000 !important;
        }

        /* Enhanced column widths */
        .col-sr {
            width: 8mm;
        }

        .col-desc {
            width: 45mm;
        }

        .col-hsn {
            width: 12mm;
        }

        .col-qty {
            width: 8mm;
        }

        .col-unit {
            width: 12mm;
        }

        .col-rate {
            width: 15mm;
        }

        .col-percent {
            width: 8mm;
        }

        .col-tax {
            width: 15mm;
        }

        .col-amount {
            width: 20mm;
        }

        .gst-rate-summary {
            font-size: 7pt;
            text-align: left;
            line-height: 1.3;
            padding: 3mm 4mm;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin: 1mm 0;
            color: #000;
        }

        .gst-rate-summary div {
            margin: 0.5mm 0;
            padding: 0;
        }

        /* Additional enhancements */
        .from-to-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 3mm 4mm;
            margin-bottom: 3mm;
            color: #000;
        }

        .from-to-section strong {
            color: #000;
            font-weight: bold;
            background-color: #ffffff;
            padding: 1mm 2mm;
            border-radius: 2px;
            display: inline-block;
        }

        .challan-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 3mm;
            color: #000;
        }

        .challan-details .label {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%) !important;
            font-weight: bold;
            color: #000;
        }

        /* Responsive improvements */
        @media print {
            .container {
                box-shadow: none;
                padding: 0;
            }

            table.items-table {
                border: 1px solid #000;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <!-- <img src="{{ $logo_base64 ?? '' }}" alt="{{ $settings['company_name'] ?? '' }}" style="height:40px; margin-bottom:2mm;"> -->
            <div class="company-name">{{ $settings['company_name'] }}</div>
            <div class="company-address">{{ $settings['address'] }}</div>
            <div class="contact-info">Phone: {{ $settings['phone'] }}</div>
        </div>

        <!-- FROM SECTION -->
        <table style="width:100%; margin-bottom:2mm; font-size:8pt;">
            <tr>
                <td style="width:50%; vertical-align:top; padding-right: 10px;">
                    <strong>From:</strong><br>
                    <strong> {{ $settings['company_name'] ?? '' }}</strong><br>
                    {{ $settings['address'] }}<br>
                    <strong>GSTIN:</strong> {{ $challan['gstNumber'] ?? '' }}<br>
                    <strong>Phone:</strong> {{ $settings['phone'] ?? '' }}<br>
                    <!-- Email: info@balajienterprises.com -->
                </td>
                <td style="width:50%; vertical-align:top; padding-left: 10px;">
                    <strong>To:</strong><br>

                    @if ($quotation->clientDetail)
                        <div><strong>{{ $quotation->clientDetail->name }}</strong></div>
                        <div>{{ $quotation->clientDetail->address }}</div>
                        <div><strong>Phone:</strong> {{ $quotation->clientDetail->phone ?? 'N/A' }}</div>
                        <div><strong>Email:</strong> {{ $quotation->clientDetail->email }}</div>
                    @elseif($quotation->leadDetail)
                        <div><strong>{{ $quotation->leadDetail->name }}</strong></div>
                        <div>Contact: {{ $quotation->leadDetail->contact_person }}</div>
                        <div><strong>Phone:</strong> {{ $quotation->leadDetail->phone ?? 'N/A' }}</div>
                        <div><strong>Email:</strong> {{ $quotation->leadDetail->email }}</div>
                    @else
                        <div>No billing information available.</div>
                    @endif

                </td>
            </tr>
        </table>

        <div class="document-title">E-Way CHALLAN</div>

        <table class="details-table">
            <tr class="avoid-break">
                <td class="label">Challan No.</td>
                <td>{{ $challan['challan_number'] ?? '' }}</td>
                <td class="label">Date</td>
                <td>{{ $challan['challan_date'] ?? '' }}</td>
            </tr>
        </table>



        <table class="items-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-sr">Sr.</th>
                    <th rowspan="2" class="col-desc">Description</th>
                    <th rowspan="2" class="col-qty">Qty</th>
                    <th rowspan="2" class="col-unit">Unit</th>
                    <th rowspan="2" class="col-rate">Rate</th>
                    @if (!empty($challan['items']) && $challan['items'][0]['state_type'] === 'intra')
                        <th colspan="2">CGST</th>
                        <th colspan="2">SGST</th>
                    @else
                        <th colspan="2">IGST</th>
                    @endif
                    <th rowspan="2" class="col-amount">Amount</th>
                </tr>
                <tr>
                    @if (!empty($challan['items']) && $challan['items'][0]['state_type'] === 'intra')
                        <th class="col-percent">%</th>
                        <th class="col-tax">Amt</th>
                        <th class="col-percent">%</th>
                        <th class="col-tax">Amt</th>
                    @else
                        <th class="col-percent">%</th>
                        <th class="col-tax">Amt</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($challan['items'] as $index => $item)
                    <tr class="avoid-break">
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $item['name'] }}<br>{{ $item['description'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>PCS</td> <!-- Static unit as per original -->
                        <td class="text-right">{{ number_format($item['unit_price'], 2) }}</td>
                        @if ($item['state_type'] === 'intra')
                            <td>{{ number_format($item['tax_rate'] / 2, 1) }}</td>
                            <td class="text-right">{{ number_format($item['cgst'], 2) }}</td>

                            <td>{{ number_format($item['tax_rate'] / 2, 1) }}</td>
                            <td class="text-right">{{ number_format($item['sgst'], 2) }}</td>
                        @else
                            <td>{{ number_format($item['tax_rate'], 1) }}</td>
                            <td class="text-right">{{ number_format($item['igst'], 2) }}</td>
                        @endif
                        <td class="text-right">{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row avoid-break">
                    @if (!empty($challan['items']) && $challan['items'][0]['state_type'] === 'intra')

                        <td colspan="4" rowspan="3">
                            <div class="gst-rate-summary">
                                @php
                                    $groupedByRate = [];
                                    foreach ($challan['items'] as $item) {
                                        if ($item['state_type'] === 'intra') {
                                            $rate = $item['tax_rate'];
                                            if (!isset($groupedByRate[$rate])) {
                                                $groupedByRate[$rate] = ['cgst' => 0, 'sgst' => 0];
                                            }
                                            $groupedByRate[$rate]['cgst'] += $item['cgst'];
                                            $groupedByRate[$rate]['sgst'] += $item['sgst'];
                                        }
                                    }
                                    ksort($groupedByRate);
                                @endphp

                                @foreach ($groupedByRate as $rate => $values)
                                    <div>CGST @ {{ $rate }}% (-{{ number_format($rate / 2, 1) }}%) =>
                                        ₹{{ number_format($values['cgst'], 2) }}</div>
                                    <div>SGST @ {{ $rate }}% (-{{ number_format($rate / 2, 1) }}%) =>
                                        ₹{{ number_format($values['sgst'], 2) }}</div>
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach

                            </div>
                        </td>
                    @else
                        <td colspan="4" rowspan="3">
                            <div class="gst-rate-summary">
                                @php
                                    $groupedIGST = [];

                                    foreach ($challan['items'] as $item) {
                                        if ($item['state_type'] === 'inter') {
                                            $rate = $item['tax_rate'];
                                            if (!isset($groupedIGST[$rate])) {
                                                $groupedIGST[$rate] = 0;
                                            }
                                            $groupedIGST[$rate] += $item['igst'];
                                        }
                                    }

                                    ksort($groupedIGST);
                                @endphp

                                @foreach ($groupedIGST as $rate => $amount)
                                    <div>IGST @ {{ $rate }}% => ₹{{ number_format($amount, 2) }}</div>
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    @endif

                    <td class="text-right">Sub Total</td>
                    <td colspan="@if (!empty($challan['items']) && $challan['items'][0]['state_type'] === 'intra') 4 @else 2 @endif"></td>
                    <td class="text-right">{{ number_format($challan['grand_subtotal'], 2) }}</td>
                </tr>
                <tr class="total-row avoid-break">
                    <td class="text-right">GST Total</td>
                    <td colspan="@if (!empty($challan['items']) && $challan['items'][0]['state_type'] === 'intra') 4 @else 2 @endif"></td>
                    <td class="text-right">
                        {{ number_format(array_sum(array_column($challan['items'], 'total_tax')), 2) }}</td>
                </tr>
                <tr class="total-row avoid-break">
                    <td class="text-right">Grand Total</td>
                    <td colspan="@if (!empty($challan['items']) && $challan['items'][0]['state_type'] === 'intra') 4 @else 2 @endif"></td>
                    <td class="text-right">{{ number_format($challan['grand_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="amount-words">
            <strong>Amount in Words:</strong> {{ $challan['amount_in_words'] ?? '' }} Rupees Only
        </div>

        <!-- <div class="signature-section avoid-break" style="display: flex; justify-content: space-between; align-items: flex-end; width: 100%; margin-top: 10mm;">
            <div class="signature-box" style="text-align: left; min-width: 40mm;">
                <div class="signature-line" style="margin-left: 0;"></div>
                <div>Customer Signature</div>
            </div>
            <div class="signature-box" style="text-align: right; min-width: 40mm;">
                <div class="signature-line" style="margin-right: 0;"></div>
                <div>Authorized Signatory</div>
                <div>For {{ $settings['company_name'] ?? '' }}</div>
            </div>
        </div> -->

        <div class="footer">
            This is a computer generated challan and does not require signature | Subject to Ahmedabad Jurisdiction
        </div>
    </div>
</body>

</html>
