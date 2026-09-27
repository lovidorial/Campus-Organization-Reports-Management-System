<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Activity Overview Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .number { text-align: right; }
    </style>
</head>
<body>
    <h1>Campus Organization Activity Overview Report</h1>
    <div class="meta">
        Term: {{ $term ?: 'All Terms' }} | Organization: {{ $organization ?: 'All Organizations' }} | Category: {{ $category ?: 'All Categories' }} | Date range: {{ $dateFrom ?: 'Any' }} to {{ $dateTo ?: 'Any' }}<br>
        Generated: {{ now()->format('M d, Y h:i A') }}
    </div>
    @if($includeSummaries)
        <h2>Organization Summary</h2>
        <table style="margin-bottom: 18px;">
            <thead><tr><th>Organization</th><th class="number">Activities</th><th class="number">Participants</th><th class="number">Estimated Budget</th></tr></thead>
            <tbody>
                @forelse($organizationSummary as $summary)
                    <tr><td>{{ $summary['organization'] }}</td><td class="number">{{ $summary['activity_count'] }}</td><td class="number">{{ number_format($summary['participants']) }}</td><td class="number">PHP {{ number_format($summary['budget'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4">No organization data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Category Summary</h2>
        <table style="margin-bottom: 18px;">
            <thead><tr><th>Category</th><th class="number">Activities</th><th class="number">Participants</th><th class="number">Estimated Budget</th></tr></thead>
            <tbody>
                @forelse($categorySummary as $summary)
                    <tr><td>{{ $summary['category'] }}</td><td class="number">{{ $summary['activity_count'] }}</td><td class="number">{{ number_format($summary['participants']) }}</td><td class="number">PHP {{ number_format($summary['budget'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4">No category data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Status Summary</h2>
        <table style="margin-bottom: 18px;">
            <thead><tr><th>Status</th><th class="number">Activities</th><th class="number">Participants</th><th class="number">Estimated Budget</th></tr></thead>
            <tbody>
                @forelse($statusSummary as $summary)
                    <tr><td>{{ $summary['status'] }}</td><td class="number">{{ $summary['activity_count'] }}</td><td class="number">{{ number_format($summary['participants']) }}</td><td class="number">PHP {{ number_format($summary['budget'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4">No status data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
    <table>
        <thead><tr><th>Organization</th><th>Term / SY</th><th>Title</th><th>Category</th><th>Date</th><th>Venue</th><th>Status</th><th class="number">Budget</th></tr></thead>
        <tbody>
            @forelse($activityRequests as $req)
                @php $gpoa = $req->gpoaActivity?->gpoa ?? $req->gpoa; @endphp
                <tr>
                    <td>{{ $req->user->org_name ?? $req->user->name ?? '—' }}</td>
                    <td>{{ $gpoa?->term ?? '—' }} / {{ $gpoa?->school_year ?? '—' }}</td>
                    <td>{{ $req->title }}</td>
                    <td>{{ $req->category ?? '—' }}</td>
                    <td>{{ $req->date ? $req->date_range_label : '—' }}</td>
                    @php
                        $venueStatus = $req->venueRecord?->availability_status ?? 'Available';
                        $venueColors = match ($venueStatus) {
                            'Scheduled' => ['#dbeafe', '#1e40af'],
                            'Reserved' => ['#fef3c7', '#92400e'],
                            default => ['#dcfce7', '#166534'],
                        };
                    @endphp
                    <td>{{ $req->venue ?? '—' }} <span style="background-color:{{ $venueColors[0] }};color:{{ $venueColors[1] }};padding:2px 6px;border-radius:8px;font-size:8px;font-weight:bold;">{{ $venueStatus }}</span></td>
                    <td>{{ str_replace('_', ' ', ucfirst($req->status)) }}</td>
                    <td class="number">PHP {{ number_format((float) ($req->estimated_budget ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No activities match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="7"><strong>Total Budget</strong></td><td class="number"><strong>PHP {{ number_format((float) $totalBudget, 2) }}</strong></td></tr></tfoot>
    </table>
</body>
</html>
