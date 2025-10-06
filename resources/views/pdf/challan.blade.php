<!-- resources/views/challan.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <title>Site Visit Challan</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .footer {
            margin-top: 30px;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>{{$company}}</h2>
        <h3>SITE VISIT CHALLAN</h3>
        <p>Visit Date: {{ date('d/m/Y g:i A', strtotime($siteVisit->visit_time)) }}</p>
    </div>

    <!-- Visit Details -->
    <table>
        <tr>
            <th colspan="4">Visit Information</th>
        </tr>
        <tr>
            <td width="25%"><strong>Visit Type:</strong></td>
            <td width="25%">{{$siteVisit->visit_type}} Visit</td>
            <td width="25%"><strong>Assigned To:</strong></td>
            <td width="25%">{{$siteVisit->assignee?->name ?? '-'}}</td>
        </tr>
        <tr>
            <td><strong>Site Location:</strong></td>
            <td>{{$siteVisit->address}}</td>
            <td><strong>Contact Person:</strong></td>
            <td>{{$siteVisit->contact_person}} {{($siteVisit->phone)}}</td>
        </tr>
    </table>

    @foreach($products as $product)
    <!-- Checklists -->
    <table>
        <thead>
            <tr>
                <th colspan="4">{{$product->name}} (Checklist)</th>
            </tr>
            <tr>
                <th width="5%">#</th>
                <th width="65%">Checklist Item</th>
                <th width="15%">Status</th>
                <th width="15%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($product->checklists as $checklist)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $checklist->title }}</td>
                <td></td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
    <!-- Tools Checklist -->
    <table>
        <thead>
            <tr>
                <th colspan="4">Tools (Checklist)</th>
            </tr>
            <tr>
                <th width="5%">#</th>
                <th width="65%">Tool</th>
                <th width="15%">Status</th>
                <th width="15%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tools as $tool)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $tool->title }}</td>
                <td></td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on: {{ date('d/m/Y H:i:s') }}</p>
        <p>This is a computer generated document and does not require signature.</p>
    </div>
</body>

</html>
