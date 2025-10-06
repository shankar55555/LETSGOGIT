<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 10px;
        }

        .items-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .totals-table {
            width: 50%;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .footer {
            margin-top: 50px;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="company-name">Your Company Name</div>
        <div class="document-title">QUOTATION</div>
    </div>

    <!-- Client/Lead Info -->
    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>Quotation #:</strong> {{ $quotation->quotation_number }}<br>
                <strong>Date:</strong> {{ $quotation->created_at->format('d M Y') }}
            </td>
            <td width="50%">
                @if($quotation->clientDetail)
                <strong>Client:</strong> {{ $quotation->clientDetail->name }}<br>
                <strong>Contact:</strong> {{ $quotation->clientDetail->contact_person ?? 'N/A' }}
                @elseif($quotation->leadDetail)
                <strong>Lead:</strong> {{ $quotation->leadDetail->name }}<br>
                <strong>Contact:</strong> {{ $quotation->leadDetail->contact_person ?? 'N/A' }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    @if(!empty($quotation->items))
    <table class="items-table">
        <!-- Table header and body as shown above -->
    </table>
    @endif

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td>{{ number_format($quotation->sub_total, 2) }}</td>
        </tr>
        <!-- Other totals -->
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['description'] ?? 'N/A' }}</td>
                <td>{{ $item['quantity'] ?? 0 }}</td>
                <td>{{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                <td>{{ number_format($item['total'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td>{{ number_format($quotation->sub_total, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Discount:</strong></td>
            <td>{{ number_format($quotation->discount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Tax:</strong></td>
            <td>{{ number_format($quotation->tax, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td>{{ number_format($quotation->total, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Amount Due:</strong></td>
            <td>{{ number_format($quotation->amount_due, 2) }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    @if($quotation->terms_conditions)
    <div style="margin-top: 30px;">
        <h4>Terms & Conditions</h4>
        <p>{!! nl2br(e($quotation->terms_conditions)) !!}</p>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>{{ config('app.name') }} | {{ config('app.url') }}</p>
    </div>
</body>

</html>
