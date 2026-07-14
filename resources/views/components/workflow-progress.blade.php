@php
    $statusClasses = [
        'pending' => 'bg-gray-100 text-gray-600 border-gray-200',
        'submitted' => 'bg-blue-100 text-blue-700 border-blue-200',
        'under_review' => 'bg-orange-100 text-orange-700 border-orange-200',
        'approved' => 'bg-green-100 text-green-700 border-green-200',
        'rejected' => 'bg-red-100 text-red-700 border-red-200',
    ];
    $statusLabels = [
        'pending' => 'Pending',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Document Submission Progress</h3>
            <p class="text-sm text-gray-500">Track your organization's workflow from GPOA to completion</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase font-semibold">Completion</p>
                <p class="text-2xl font-bold" style="color: #e89600;">{{ $workflow->completion_percentage }}%</p>
            </div>
            <div class="w-32 h-3 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500"
                     style="width: {{ $workflow->completion_percentage }}%; background: linear-gradient(90deg, #f5a623, #e89600);"></div>
            </div>
        </div>
    </div>

    @if($workflow->is_completed)
    <div class="mb-6 bg-green-50 border border-green-300 text-green-800 px-5 py-4 rounded-lg">
        <p class="font-bold text-lg">Congratulations!</p>
        <p>Your organization has successfully completed all required document submissions.</p>
    </div>
    @endif

    <div class="relative">
        <div class="hidden md:block absolute top-5 left-0 right-0 h-0.5 bg-gray-200 z-0"></div>
        <div class="grid grid-cols-1 md:grid-cols-7 gap-4 relative z-10">
            @foreach($progressStages as $index => $stage)
            @php
                $status = $stage['status'];
                $isLocked = $stage['locked'] ?? false;
                $submission = $stage['submission'] ?? null;
                $classes = $statusClasses[$status] ?? $statusClasses['pending'];
            @endphp
            <div class="flex flex-col items-center text-center {{ $isLocked ? 'opacity-50' : '' }}">
                <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center text-sm font-bold mb-2 {{ $classes }}">
                    @if($status === 'approved')
                        ✓
                    @elseif($status === 'rejected')
                        ✕
                    @elseif($isLocked)
                        🔒
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>
                <p class="text-xs font-semibold text-gray-700 leading-tight mb-1">{{ $stage['label'] }}</p>
                <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold {{ $classes }}">
                    {{ $isLocked ? 'Locked' : ($statusLabels[$status] ?? ucfirst($status)) }}
                </span>
                @if($submission && $submission->submitted_at)
                <p class="text-[10px] text-gray-400 mt-1">Submitted {{ $submission->submitted_at->format('M d, Y') }}</p>
                @endif
                @if($submission && $submission->approved_at)
                <p class="text-[10px] text-green-600 mt-0.5">Approved {{ $submission->approved_at->format('M d, Y') }}</p>
                @endif
                @if($submission && $submission->reject_reason)
                <p class="text-[10px] text-red-500 mt-1 max-w-[120px] truncate" title="{{ $submission->reject_reason }}">{{ Str::limit($submission->reject_reason, 40) }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
