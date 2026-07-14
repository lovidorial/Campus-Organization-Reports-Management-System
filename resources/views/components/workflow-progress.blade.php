@php
    $statusClasses = [
        'pending' => 'bg-gray-100 text-gray-600 border-gray-200',
        'submitted' => 'bg-blue-50 text-blue-700 border-blue-200',
        'under_review' => 'bg-orange-50 text-orange-700 border-orange-200',
        'approved' => 'bg-green-50 text-green-700 border-green-200',
        'rejected' => 'bg-red-50 text-red-700 border-red-200',
    ];
    $statusLabels = [
        'pending' => 'Pending',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];
    $completedStages = collect($progressStages)->filter(fn ($s) => ($s['status'] ?? '') === 'approved')->count();
    $progressWidth = count($progressStages) > 1 ? (($completedStages / (count($progressStages) - 1)) * 100) : 0;
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6 mb-5 transition-shadow duration-300 hover:shadow-md">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-5">
        <div>
            <h3 class="text-lg font-bold text-gray-900 tracking-tight">Workflow Progress</h3>
            <p class="text-sm text-gray-500 mt-0.5">GPOA → Communication Letter → Summary Report → Completed</p>
        </div>
        <div class="flex items-center gap-4 w-full lg:w-auto">
            <div class="flex-1 lg:flex-none">
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Overall Completion</span>
                    <span class="text-lg font-bold" style="color: #e89600;">{{ $workflow->completion_percentage }}%</span>
                </div>
                <div class="w-full lg:w-48 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 ease-out"
                         style="width: {{ $workflow->completion_percentage }}%; background: linear-gradient(90deg, #f5a623, #e89600);"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Desktop stepper --}}
    <div class="hidden md:block relative pt-2 pb-1">
        <div class="absolute top-[1.65rem] left-[2.5%] right-[2.5%] h-1 bg-gray-100 rounded-full z-0"></div>
        <div class="absolute top-[1.65rem] left-[2.5%] h-1 rounded-full z-0 transition-all duration-700"
             style="width: calc({{ min($progressWidth, 100) }}% - 2.5%); background: linear-gradient(90deg, #f5a623, #e89600);"></div>
        <div class="grid grid-cols-7 gap-2 relative z-10">
            @foreach($progressStages as $index => $stage)
            @php
                $status = $stage['status'];
                $isLocked = $stage['locked'] ?? false;
                $submission = $stage['submission'] ?? null;
                $classes = $statusClasses[$status] ?? $statusClasses['pending'];
                $isApproved = $status === 'approved';
                $isRejected = $status === 'rejected';
            @endphp
            <div class="flex flex-col items-center text-center group {{ $isLocked ? 'opacity-45' : '' }}">
                <div class="w-11 h-11 rounded-full border-2 flex items-center justify-center text-sm font-bold mb-2 transition-transform duration-300 group-hover:scale-105 {{ $classes }}">
                    @if($isApproved)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @elseif($isRejected)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    @elseif($isLocked)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>
                <p class="text-[11px] font-semibold text-gray-700 leading-tight mb-1 px-1">{{ $stage['label'] }}</p>
                <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold {{ $classes }}">
                    {{ $isLocked ? 'Locked' : ($statusLabels[$status] ?? ucfirst($status)) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Mobile timeline --}}
    <div class="md:hidden space-y-0">
        @foreach($progressStages as $index => $stage)
        @php
            $status = $stage['status'];
            $isLocked = $stage['locked'] ?? false;
            $submission = $stage['submission'] ?? null;
            $classes = $statusClasses[$status] ?? $statusClasses['pending'];
            $isLast = $index === count($progressStages) - 1;
        @endphp
        <div class="flex gap-3 {{ $isLocked ? 'opacity-45' : '' }}">
            <div class="flex flex-col items-center">
                <div class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-xs font-bold shrink-0 {{ $classes }}">
                    @if($status === 'approved') ✓
                    @elseif($status === 'rejected') ✕
                    @elseif($isLocked) 🔒
                    @else {{ $index + 1 }}
                    @endif
                </div>
                @if(!$isLast)
                <div class="w-0.5 flex-1 min-h-[1.5rem] my-1 {{ $status === 'approved' ? 'bg-orange-300' : 'bg-gray-200' }}"></div>
                @endif
            </div>
            <div class="pb-4 {{ $isLast ? 'pb-0' : '' }} flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800">{{ $stage['label'] }}</p>
                <span class="inline-block text-[10px] px-2 py-0.5 rounded-full border font-semibold mt-1 {{ $classes }}">
                    {{ $isLocked ? 'Locked' : ($statusLabels[$status] ?? ucfirst($status)) }}
                </span>
                @if($submission?->reject_reason)
                <p class="text-xs text-red-600 mt-1.5 bg-red-50 rounded-lg px-2 py-1.5">{{ Str::limit($submission->reject_reason, 80) }}</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
