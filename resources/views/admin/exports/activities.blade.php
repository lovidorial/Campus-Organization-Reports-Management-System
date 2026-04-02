<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Activities Report</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Organization</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $a)
            <tr>
                <td>{{ $a->title }}</td>
                <td>{{ $a->date->format('Y-m-d') }}</td>
                <td>{{ $a->venue }}</td>
                <td>{{ $a->user->name }}</td>
                <td>{{ ucfirst($a->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>