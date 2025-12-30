<!DOCTYPE html>
<html>

<head>
    <title>Trips Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>
    <h2>Trips Report</h2>
    <p>Generated on: {{ date('Y-m-d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Package</th>
                <th>Capacity</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trips as $trip)
                <tr>
                    <td>{{ $trip->trip_id }}</td>
                    <td>{{ $trip->trip_name }}</td>
                    <td>{{ $trip->status }}</td>
                    <td>{{ $trip->start_date }}</td>
                    <td>{{ $trip->end_date }}</td>
                    <td>{{ $trip->package->package_name ?? 'N/A' }}</td>
                    <td>{{ $trip->capacity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>