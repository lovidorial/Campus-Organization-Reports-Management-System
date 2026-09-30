<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Activity Overview Report</title>
    <style>
        @page { size: landscape; margin: 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1f2937; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        h2 { margin: 15px 0 6px; font-size: 12px; }
        .meta { color: #6b7280; margin-bottom: 12px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .number { text-align: right; white-space: nowrap; }
        .pill { display: inline-block; padding: 3px 7px; border-radius: 10px; font-weight: bold; white-space: nowrap; }
        .pending { background: #fef3c7; color: #92400e; }
        .ongoing { background: #e0f2fe; color: #075985; }
        .completed { background: #d1fae5; color: #065f46; }
        .late { background: #ffe4e6; color: #9f1239; }
        .document-pending { background: #fef3c7; color: #92400e; }
        .document-submitted { background: #d1fae5; color: #065f46; }
        .revision { background: #ffe4e6; color: #9f1239; }
        a { color: #0369a1; text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Campus Organization Activity Overview Report</h1>
    <div class="meta">
        Organization: {{ $organizationLabel }} | Category: {{ $filters['category'] ?: 'All Categories' }} | College: {{ $filters['college'] ?: 'All Colleges' }}<br>
        Term: {{ $filters['term'] ?: 'All Terms' }} | School year: {{ $filters['school_year'] ?: 'All School Years' }} | Status: {{ $filters['status'] ?: 'All Statuses' }}<br>
        Date range: {{ $filters['date_from'] ?: 'Any' }} to {{ $filters['date_to'] ?: 'Any' }} | Generated: {{ now()->format('M d, Y h:i A') }}
    </div>

    @if($includeSummaries)
        <h2>Organization Summary</h2>
        <table>
            <thead><tr><th>Organization</th><th class="number">Total Activities</th><th class="number">Completed</th><th class="number">Ongoing</th><th class="number">Pending</th><th class="number">Progress</th></tr></thead>
            <tbody>
                @forelse($organizationSummary as $summary)
                    <tr><td>{{ $summary['organization'] }}</td><td class="number">{{ $summary['activity_count'] }}</td><td class="number">{{ $summary['completed'] }}</td><td class="number">{{ $summary['ongoing'] }}</td><td class="number">{{ $summary['pending'] }}</td><td class="number">{{ $summary['progress'] }}%</td></tr>
                @empty
                    <tr><td colspan="6">No organization data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Category Summary</h2>
        <table>
            <thead><tr><th>Category</th><th class="number">Activity Count</th><th class="number">Completed</th></tr></thead>
            <tbody>
                @forelse($categorySummary as $summary)
                    <tr><td>{{ $summary['category'] }}</td><td class="number">{{ $summary['activity_count'] }}</td><td class="number">{{ $summary['completed'] }}</td></tr>
                @empty
                    <tr><td colspan="3">No category data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Monitoring Status Summary</h2>
        <table>
            <thead><tr><th>Status</th><th class="number">Activity Count</th></tr></thead>
            <tbody>
                @foreach($statusSummary as $summary)
                    <tr><td><span class="pill {{ strtolower($summary['status']) }}">{{ $summary['status'] }}</span></td><td class="number">{{ $summary['activity_count'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Planned Activities</h2>
    <table>
        <thead><tr><th>Organization</th><th>Term / School Year</th><th>Activity # / Title</th><th>Category</th><th>Date</th><th>Venue</th><th>Status</th><th>Communication Letter</th><th>Narrative Report</th><th class="number">Estimated Budget</th></tr></thead>
        <tbody>
            @forelse($activities as $activity)
                @php
                    $activityRequest = $activity->activityRequest;
                    $report = $activityRequest?->report;
                    $letterSubmitted = filled($activityRequest?->communication_letter);
                    $narrativeStatus = $report?->status === 'needs_revision'
                        ? 'Needs Revision'
                        : ((filled($report?->narrative_report) || filled($report?->narrative_content)) ? 'Submitted' : 'Pending');
                    $statusClass = strtolower($activity->monitoring_status);
                    $narrativeClass = $narrativeStatus === 'Needs Revision'
                        ? 'revision'
                        : ($narrativeStatus === 'Submitted' ? 'document-submitted' : 'document-pending');
                @endphp
                <tr>
                    <td>{{ $activity->gpoa?->user?->org_name ?? $activity->gpoa?->user?->name ?? '—' }}</td>
                    <td>{{ $activity->gpoa?->term ?? '—' }} / {{ $activity->gpoa?->school_year ?? '—' }}</td>
                    <td>Activity #{{ $activity->activity_number ?? '—' }}: {{ $activity->title }}</td>
                    <td>{{ $activity->category ?: '—' }}</td>
                    <td>{{ $activity->date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $activity->venue ?: '—' }}</td>
                    <td><span class="pill {{ $statusClass }}">{{ $activity->monitoring_status }}</span>@if($activity->monitoring_late) <span class="pill late">Late</span>@endif</td>
                    <td><span class="pill {{ $letterSubmitted ? 'document-submitted' : 'document-pending' }}">{{ $letterSubmitted ? 'Uploaded' : 'Pending' }}</span>@if($letterSubmitted) <a href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}">View</a>@endif</td>
                    <td><span class="pill {{ $narrativeClass }}">{{ $narrativeStatus }}</span>@if(filled($report?->narrative_report)) <a href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}">View</a>@endif</td>
                    <td class="number">PHP {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">No planned activities match the selected filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="9"><strong>Total Estimated Budget</strong></td><td class="number"><strong>PHP {{ number_format((float) $totalBudget, 2) }}</strong></td></tr></tfoot>
    </table>
</body>
</html>