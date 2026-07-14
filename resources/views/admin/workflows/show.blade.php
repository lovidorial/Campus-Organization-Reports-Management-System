<x-app-layout>
<div class="mb-6">
    <a href="{{ route('admin.workflows.index') }}" class="text-sm text-sky-600 hover:underline">← Back to Workflows</a>
</div>

<div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ $workflow->user->org_name ?? $workflow->user->name }}</h2>
        <p class="text-sm text-gray-500">{{ $workflow->term }} / SY {{ $workflow->school_year }} — {{ str_replace('_', ' ', ucfirst($workflow->current_stage)) }}</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="text-right">
            <p class="text-xs text-gray-500">Completion</p>
            <p class="text-xl font-bold" style="color:#e89600;">{{ $workflow->completion_percentage }}%</p>
        </div>
        @if($workflow->is_locked)
        <form action="{{ route('admin.workflows.reopen', $workflow) }}" method="POST" onsubmit="return confirm('Reopen this workflow for revisions?')">
            @csrf
            <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm font-semibold hover:bg-yellow-700">Reopen Workflow</button>
        </form>
        @endif
    </div>
</div>

@include('components.workflow-progress', ['workflow' => $workflow, 'progressStages' => $workflow->progressStages()])

<!-- Current Submissions for Review -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    @foreach(['gpoa' => 'GPOA', 'communication_letter' => 'Communication Letter', 'summary_report' => 'Summary Report'] as $type => $label)
    @php $sub = $workflow->currentSubmission($type); @endphp
    <div class="bg-white rounded-xl border p-5 shadow-sm">
        <h4 class="font-bold text-gray-800 mb-3">{{ $label }}</h4>
        @if($sub)
            <span class="text-xs px-2 py-1 rounded-full border font-semibold {{ $sub->statusClasses() }}">{{ ucfirst(str_replace('_',' ',$sub->status)) }}</span>
            <p class="text-xs text-gray-500 mt-2">Version {{ $sub->version }}</p>
            @if($sub->submitted_at)<p class="text-xs text-gray-500">Submitted: {{ $sub->submitted_at->format('M d, Y h:i A') }}</p>@endif
            @if($sub->approved_at)<p class="text-xs text-green-600">Approved: {{ $sub->approved_at->format('M d, Y') }} by {{ $sub->reviewer?->name }}</p>@endif
            @if($sub->approval_remarks)<p class="text-xs text-green-700 mt-1">Remarks: {{ $sub->approval_remarks }}</p>@endif
            @if($sub->reject_reason)<p class="text-xs text-red-600 mt-1 bg-red-50 p-2 rounded">{{ $sub->reject_reason }}</p>@endif

            <div class="mt-3 flex flex-wrap gap-2">
                @if($sub->file_path || ($sub->gpoa && $sub->gpoa->document_path))
                <a href="{{ route('admin.workflows.submissions.document', $sub) }}" target="_blank" class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded font-semibold">View Document</a>
                @endif
                @if($sub->gpoa)
                <a href="{{ route('admin.gpoa.show', $sub->gpoa) }}" class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded font-semibold">View GPOA Details</a>
                @endif
            </div>

            @if($sub->status === 'under_review')
            <div class="mt-4 space-y-3 border-t pt-4">
                <form action="{{ route('admin.workflows.submissions.approve', $sub) }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="text" name="approval_remarks" placeholder="Approval remarks (optional)" class="w-full border rounded px-2 py-1 text-xs">
                    <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white rounded text-xs font-semibold hover:bg-green-700">Approve</button>
                </form>
                <form action="{{ route('admin.workflows.submissions.reject', $sub) }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="text" name="reject_reason" placeholder="Rejection reason (required)" required class="w-full border rounded px-2 py-1 text-xs">
                    <button type="submit" class="w-full px-3 py-2 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">Reject</button>
                </form>
            </div>
            @endif
        @else
            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">Not submitted</span>
        @endif
    </div>
    @endforeach
</div>

<!-- Submission History -->
<div class="bg-white rounded-xl border p-6 mb-6">
    <h3 class="font-bold text-gray-800 mb-4">Submission History</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-3 py-2 text-gray-500">Document</th>
                    <th class="text-left px-3 py-2 text-gray-500">Version</th>
                    <th class="text-left px-3 py-2 text-gray-500">Status</th>
                    <th class="text-left px-3 py-2 text-gray-500">Submitted</th>
                    <th class="text-left px-3 py-2 text-gray-500">Approved</th>
                    <th class="text-left px-3 py-2 text-gray-500">Reviewer</th>
                    <th class="text-left px-3 py-2 text-gray-500">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($workflow->submissions->sortByDesc('created_at') as $sub)
                <tr class="hover:bg-gray-50 {{ $sub->is_current ? 'bg-blue-50' : '' }}">
                    <td class="px-3 py-3 font-medium">{{ $sub->documentLabel() }} @if($sub->is_current)<span class="text-[10px] text-blue-600">(current)</span>@endif</td>
                    <td class="px-3 py-3">v{{ $sub->version }}</td>
                    <td class="px-3 py-3"><span class="text-xs px-2 py-1 rounded-full border font-semibold {{ $sub->statusClasses() }}">{{ ucfirst(str_replace('_',' ',$sub->status)) }}</span></td>
                    <td class="px-3 py-3 text-xs">{{ $sub->submitted_at?->format('M d, Y H:i') ?? '—' }}</td>
                    <td class="px-3 py-3 text-xs">{{ $sub->approved_at?->format('M d, Y H:i') ?? '—' }}</td>
                    <td class="px-3 py-3 text-xs">{{ $sub->reviewer?->name ?? '—' }}</td>
                    <td class="px-3 py-3 text-xs max-w-[200px] truncate">{{ $sub->reject_reason ?? $sub->approval_remarks ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Activity Log -->
<div class="bg-white rounded-xl border p-6">
    <h3 class="font-bold text-gray-800 mb-4">Activity Log</h3>
    <div class="space-y-2 max-h-96 overflow-y-auto">
        @forelse($workflow->events as $event)
        <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 text-sm">
            <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background:#e89600;"></div>
            <div class="flex-1">
                <p class="text-gray-800">{{ $event->description }}</p>
                <p class="text-xs text-gray-400">{{ $event->created_at->format('M d, Y h:i A') }} — {{ $event->user?->name ?? 'System' }}</p>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm">No activity logged yet.</p>
        @endforelse
    </div>
</div>
</x-app-layout>
