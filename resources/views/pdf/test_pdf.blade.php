<!DOCTYPE html>
<html>

<head>
    <title>Test List</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>

<body>
    <h2>Test List</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            @for($i=1; $i <= 10; $i++)
                <tr>
                <td>{{ $i }}</td>
                <td>Test Name {{ $i }}</td>
                <td>test{{$i}}@gmail.com</td>
                <td>12555{{$i}}255</td>
                </tr>
                @endfor
        </tbody>
    </table>
</body>

</html>
