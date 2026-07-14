<x-app-layout>

<div class="rounded-xl p-6 mb-6 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-4" style="background: linear-gradient(135deg, #f5a623 0%, #e89600 100%)">
    <div class="flex items-center gap-4">
        @if(auth()->user()->profile_photo_path)
            <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}"
                 class="w-16 h-16 rounded-full object-cover border-3 border-white shadow"/>
        @else
            <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-2xl font-bold shadow" style="color: #f5a623;">
                {{ substr(auth()->user()->name,0,1) }}
            </div>
        @endif
        <div>
            <h2 class="text-xl font-bold">Welcome, {{ auth()->user()->name }}!</h2>
            @if(auth()->user()->org_name || auth()->user()->organization)
            <p class="text-white text-opacity-90 text-sm">
                {{ auth()->user()->position ?? 'Member' }}
                — {{ auth()->user()->organization->name ?? auth()->user()->org_name }}
            </p>
            @endif
        </div>
    </div>
    <div class="flex flex-wrap gap-2 text-sm items-center">
        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full">{{ $term }}</span>
        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full">SY {{ $schoolYear }}</span>
        @if($unreadCount > 0)
        <a href="{{ route('notifications.index') }}" class="bg-red-500 px-3 py-1 rounded-full font-semibold hover:bg-red-600">
            {{ $unreadCount }} new notification{{ $unreadCount > 1 ? 's' : '' }}
        </a>
        @endif
    </div>
</div>

@include('components.workflow-progress', ['workflow' => $workflow, 'progressStages' => $progressStages])

<!-- Workflow Steps -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <!-- Step 1: GPOA -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm {{ $workflow->is_locked ? 'opacity-60' : '' }}">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold" style="background:#e89600;">1</span>
            <h4 class="font-bold text-gray-800">GPOA Submission</h4>
        </div>
        @php $gpoaSub = $workflow->currentSubmission('gpoa'); @endphp
        @if($gpoaSub)
            <span class="text-xs px-2 py-1 rounded-full border font-semibold {{ $gpoaSub->statusClasses() }}">{{ ucfirst(str_replace('_',' ',$gpoaSub->status)) }}</span>
            @if($gpoaSub->submitted_at)<p class="text-xs text-gray-500 mt-2">Submitted: {{ $gpoaSub->submitted_at->format('M d, Y h:i A') }}</p>@endif
            @if($gpoaSub->approved_at)<p class="text-xs text-green-600 mt-1">Approved: {{ $gpoaSub->approved_at->format('M d, Y') }}</p>@endif
            @if($gpoaSub->reviewer)<p class="text-xs text-gray-500 mt-1">Reviewer: {{ $gpoaSub->reviewer->name }}</p>@endif
            @if($gpoaSub->reject_reason)<p class="text-xs text-red-600 mt-2 bg-red-50 p-2 rounded">{{ $gpoaSub->reject_reason }}</p>@endif
            <div class="mt-3 flex gap-2 flex-wrap">
                @if(in_array($gpoaSub->status, ['submitted','under_review']) && $gpoa)
                <a href="{{ route('gpoa.edit', $gpoa) }}" class="text-xs px-3 py-1 bg-gray-100 rounded hover:bg-gray-200 font-semibold">Edit GPOA</a>
                @endif
                @if($gpoaSub->status === 'rejected')
                <a href="{{ route('gpoa.create') }}" class="text-xs px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 font-semibold">Resubmit GPOA</a>
                @endif
            </div>
        @else
            <span class="text-xs px-2 py-1 rounded-full border font-semibold bg-gray-100 text-gray-600">Pending</span>
            @if(!$workflow->is_locked)
            <a href="{{ route('gpoa.create') }}" class="mt-3 inline-block text-xs px-4 py-2 text-white rounded-lg font-semibold" style="background:#e89600;">Submit GPOA</a>
            @endif
        @endif
    </div>

    <!-- Step 2: Communication Letter -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm {{ !$workflow->isGpoaApproved() || $workflow->is_locked ? 'opacity-60' : '' }}">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold" style="background:#e89600;">2</span>
            <h4 class="font-bold text-gray-800">Communication Letter</h4>
            @if(!$workflow->isGpoaApproved())<span class="text-xs text-gray-400">🔒 Locked</span>@endif
        </div>
        @php $commSub = $workflow->currentSubmission('communication_letter'); @endphp
        @if($commSub)
            <span class="text-xs px-2 py-1 rounded-full border font-semibold {{ $commSub->statusClasses() }}">{{ ucfirst(str_replace('_',' ',$commSub->status)) }}</span>
            @if($commSub->submitted_at)<p class="text-xs text-gray-500 mt-2">Submitted: {{ $commSub->submitted_at->format('M d, Y h:i A') }}</p>@endif
            @if($commSub->approved_at)<p class="text-xs text-green-600 mt-1">Approved: {{ $commSub->approved_at->format('M d, Y') }}</p>@endif
            @if($commSub->reviewer)<p class="text-xs text-gray-500 mt-1">Reviewer: {{ $commSub->reviewer->name }}</p>@endif
            @if($commSub->approval_remarks)<p class="text-xs text-green-700 mt-1">Remarks: {{ $commSub->approval_remarks }}</p>@endif
            @if($commSub->reject_reason)<p class="text-xs text-red-600 mt-2 bg-red-50 p-2 rounded">{{ $commSub->reject_reason }}</p>@endif
            @if($commSub->status === 'rejected' && !$workflow->is_locked)
            <a href="{{ route('workflow.communication-letter') }}" class="mt-3 inline-block text-xs px-4 py-2 bg-red-100 text-red-700 rounded-lg font-semibold">Resubmit</a>
            @endif
        @elseif($workflow->canSubmitCommunicationLetter())
            <span class="text-xs px-2 py-1 rounded-full border font-semibold bg-gray-100 text-gray-600">Pending</span>
            <a href="{{ route('workflow.communication-letter') }}" class="mt-3 inline-block text-xs px-4 py-2 text-white rounded-lg font-semibold" style="background:#e89600;">Submit Letter</a>
        @else
            <span class="text-xs px-2 py-1 rounded-full border font-semibold bg-gray-100 text-gray-600">Pending</span>
            <p class="text-xs text-gray-400 mt-2">Awaiting GPOA approval</p>
        @endif
    </div>

    <!-- Step 3: Summary Report -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm {{ !$workflow->canSubmitSummaryReport() && !($workflow->currentSubmission('summary_report')) || $workflow->is_locked && !$workflow->is_completed ? 'opacity-60' : '' }}">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold" style="background:#e89600;">3</span>
            <h4 class="font-bold text-gray-800">Summary Report</h4>
            @php $commApproved = $workflow->currentSubmission('communication_letter')?->status === 'approved'; @endphp
            @if(!$commApproved)<span class="text-xs text-gray-400">🔒 Locked</span>@endif
        </div>
        @php $summarySub = $workflow->currentSubmission('summary_report'); @endphp
        @if($summarySub)
            <span class="text-xs px-2 py-1 rounded-full border font-semibold {{ $summarySub->statusClasses() }}">{{ ucfirst(str_replace('_',' ',$summarySub->status)) }}</span>
            @if($summarySub->submitted_at)<p class="text-xs text-gray-500 mt-2">Submitted: {{ $summarySub->submitted_at->format('M d, Y h:i A') }}</p>@endif
            @if($summarySub->approved_at)<p class="text-xs text-green-600 mt-1">Approved: {{ $summarySub->approved_at->format('M d, Y') }}</p>@endif
            @if($summarySub->reviewer)<p class="text-xs text-gray-500 mt-1">Reviewer: {{ $summarySub->reviewer->name }}</p>@endif
            @if($summarySub->reject_reason)<p class="text-xs text-red-600 mt-2 bg-red-50 p-2 rounded">{{ $summarySub->reject_reason }}</p>@endif
            @if($summarySub->status === 'rejected' && !$workflow->is_locked)
            <a href="{{ route('workflow.summary-report') }}" class="mt-3 inline-block text-xs px-4 py-2 bg-red-100 text-red-700 rounded-lg font-semibold">Resubmit</a>
            @endif
        @elseif($workflow->canSubmitSummaryReport())
            <span class="text-xs px-2 py-1 rounded-full border font-semibold bg-gray-100 text-gray-600">Pending</span>
            <a href="{{ route('workflow.summary-report') }}" class="mt-3 inline-block text-xs px-4 py-2 text-white rounded-lg font-semibold" style="background:#e89600;">Submit Report</a>
        @else
            <span class="text-xs px-2 py-1 rounded-full border font-semibold bg-gray-100 text-gray-600">Pending</span>
            <p class="text-xs text-gray-400 mt-2">Awaiting Communication Letter approval</p>
        @endif
    </div>
</div>

@if($workflow->is_completed)
<div class="mb-6 bg-green-50 border-2 border-green-300 text-green-800 px-6 py-5 rounded-xl text-center">
    <p class="text-2xl font-bold mb-2">🎉 Workflow Complete!</p>
    <p class="text-lg">Congratulations! Your organization has successfully completed all required document submissions.</p>
    <p class="text-sm text-green-600 mt-2">All submissions are locked. Contact OSDW if revisions are needed.</p>
</div>
@endif

<!-- Notifications -->
@if($notifications->count())
<div class="bg-white rounded-xl shadow-sm border p-5 mb-6">
    <div class="flex justify-between items-center mb-3">
        <h3 class="font-bold text-gray-800">Recent Notifications</h3>
        <a href="{{ route('notifications.index') }}" class="text-sm" style="color:#e89600;">View all →</a>
    </div>
    <div class="space-y-2">
        @foreach($notifications as $notification)
        <div class="flex items-start gap-3 p-3 rounded-lg {{ $notification->read_at ? 'bg-gray-50' : 'bg-blue-50 border border-blue-100' }}">
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-800">{{ $notification->title }}</p>
                <p class="text-xs text-gray-600">{{ $notification->message }}</p>
                <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Submission History -->
<div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Submission History</h3>
    @if($submissionHistory->count())
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-3 py-2 text-gray-500">Document</th>
                    <th class="text-left px-3 py-2 text-gray-500">Version</th>
                    <th class="text-left px-3 py-2 text-gray-500">Status</th>
                    <th class="text-left px-3 py-2 text-gray-500">Submitted</th>
                    <th class="text-left px-3 py-2 text-gray-500">Approved</th>
                    <th class="text-left px-3 py-2 text-gray-500">Reviewer</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($submissionHistory as $sub)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-3 font-medium">{{ $sub->documentLabel() }}</td>
                    <td class="px-3 py-3">v{{ $sub->version }}</td>
                    <td class="px-3 py-3"><span class="text-xs px-2 py-1 rounded-full border font-semibold {{ $sub->statusClasses() }}">{{ ucfirst(str_replace('_',' ',$sub->status)) }}</span></td>
                    <td class="px-3 py-3 text-xs text-gray-500">{{ $sub->submitted_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-3 py-3 text-xs text-gray-500">{{ $sub->approved_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-3 py-3 text-xs">{{ $sub->reviewer?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-gray-400 text-sm">No submissions yet. Start by submitting your GPOA.</p>
    @endif
</div>

<!-- Activity Requests (secondary) -->
@if($hasApprovedGpoa)
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Requests</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Pending</p>
        <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Active</p>
        <p class="text-3xl font-bold text-green-500 mt-1">{{ $stats['approved'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Rejected</p>
        <p class="text-3xl font-bold text-red-500 mt-1">{{ $stats['rejected'] }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Recent Activity Requests</h3>
        <a href="{{ route('activity-requests.index') }}" class="text-sm font-medium" style="color: #f5a623;">View all →</a>
    </div>
    @if($activities->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[500px]">
            <thead><tr class="border-b">
                <th class="text-left py-2 text-gray-500 font-semibold">Title</th>
                <th class="text-left py-2 text-gray-500 font-semibold">Date</th>
                <th class="text-left py-2 text-gray-500 font-semibold">Venue</th>
                <th class="text-left py-2 text-gray-500 font-semibold">Status</th>
            </tr></thead>
            <tbody>
                @foreach($activities as $activity)
                <tr class="border-b last:border-0 hover:bg-gray-50">
                    <td class="py-3 font-medium">{{ $activity->title }}</td>
                    <td class="py-3">{{ $activity->date->format('M d, Y') }}</td>
                    <td class="py-3">{{ $activity->venue }}</td>
                    <td class="py-3"><span class="px-2 py-1 rounded-full text-xs font-bold bg-gray-100">{{ str_replace('_', ' ', ucfirst($activity->status)) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="py-6 text-center">
        <p class="text-gray-400 mb-3">No activity requests yet.</p>
        <a href="{{ route('activity-requests.create') }}" class="px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background-color: #f5a623;">Request Activity</a>
    </div>
    @endif
</div>
@endif

</x-app-layout>
