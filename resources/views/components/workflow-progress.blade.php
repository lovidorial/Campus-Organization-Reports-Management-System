@php
    $gpoa = $workflow->currentSubmission('gpoa');
    $summary = $workflow->currentSubmission('summary_report');
    
    // Determine Activity Requests step status based on actual activity requests
    $activityRequestsStatus = 'pending';
    if ($gpoa && in_array($gpoa->status, ['approved', 'stored'])) {
        // GPOA is approved, check if there are activity requests
        $gpoaModel = \App\Models\Gpoa::find($gpoa->id);
        $activityRequestCount = $gpoaModel ? $gpoaModel->activityRequests()->count() : 0;
        
        if ($activityRequestCount === 0) {
            // No activity requests yet
            $activityRequestsStatus = 'in_progress';
        } else {
            // Check if all activity requests are closed
            $allClosed = $gpoaModel->activityRequests()
                ->where('status', '!=', 'closed')
                ->doesntExist();
            $activityRequestsStatus = $allClosed ? 'completed' : 'in_progress';
        }
    }
    
    $steps = [
        ['label' => 'GPOA Submitted', 'status' => $gpoa && in_array($gpoa->status, ['approved', 'submitted', 'under_review', 'stored']) ? 'completed' : 'pending'],
        ['label' => 'Activity Requests', 'status' => $activityRequestsStatus],
        ['label' => 'Summary Report', 'status' => $summary && in_array($summary->status, ['approved', 'submitted', 'under_review']) ? 'completed' : 'pending'],
        ['label' => 'Completed', 'status' => $workflow->is_completed ? 'completed' : 'pending'],
    ];
    $badgeClasses = [
        'pending' => 'bg-slate-100 text-slate-600 border-slate-200',
        'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-4">
    <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-3 mb-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Workflow Progress</h3>
            <p class="text-xs text-slate-500 mt-0.5">GPOA → Activity Requests → Summary Report → Completed</p>
        </div>
        <div class="flex items-center gap-3 w-full xl:w-auto">
            <div class="text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Completion</p>
                <p class="text-lg font-bold text-orange-600">{{ $workflow->completion_percentage }}%</p>
            </div>
            <div class="w-full xl:w-40 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 ease-out"
                     style="width: {{ $workflow->completion_percentage }}%; background: linear-gradient(90deg, #f5a623, #e89600);"></div>
            </div>
        </div>
    </div>

    <div class="hidden md:block relative px-2 py-4">
        <div class="absolute left-6 right-6 top-7 h-px bg-slate-200"></div>
        <div class="grid grid-cols-4 gap-4 relative">
            @foreach($steps as $index => $step)
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-full border-2 flex items-center justify-center text-sm font-bold mb-2 transition-all duration-300 {{ $step['status'] === 'completed' ? 'border-emerald-500 text-emerald-700 bg-white shadow-sm' : ($step['status'] === 'in_progress' ? 'border-amber-500 text-amber-700 bg-white shadow-sm' : 'border-slate-300 text-slate-500 bg-white') }}">
                        {{ $index + 1 }}
                    </div>
                    <p class="text-xs font-semibold text-slate-900 leading-tight">{{ $step['label'] }}</p>
                    <span class="mt-2 inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $badgeClasses[$step['status']] }}">
                        {{ ucfirst($step['status']) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="md:hidden space-y-3">
        @foreach($steps as $index => $step)
            <div class="flex items-start gap-3">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center text-xs font-bold {{ $step['status'] === 'completed' ? 'border-emerald-500 text-emerald-700 bg-white shadow-sm' : ($step['status'] === 'in_progress' ? 'border-amber-500 text-amber-700 bg-white shadow-sm' : 'border-slate-300 text-slate-500 bg-white') }}">
                        {{ $index + 1 }}
                    </div>
                    @if(!$loop->last)
                        <div class="w-px h-6 bg-slate-200 mt-1"></div>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-slate-900">{{ $step['label'] }}</p>
                    <span class="mt-1 inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $badgeClasses[$step['status']] }}">
                        {{ ucfirst($step['status']) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>
