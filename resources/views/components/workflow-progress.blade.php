@php
    $gpoa = $workflow->currentSubmission('gpoa');
    $communication = $workflow->currentSubmission('communication_letter');
    $summary = $workflow->currentSubmission('summary_report');
    $steps = [
        ['label' => 'GPOA Submitted', 'status' => $gpoa && in_array($gpoa->status, ['approved', 'submitted', 'under_review', 'stored']) ? 'completed' : 'pending'],
        ['label' => 'Communication Letter', 'status' => $communication && in_array($communication->status, ['approved', 'submitted', 'under_review']) ? 'completed' : 'pending'],
        ['label' => 'Summary Report', 'status' => $summary && in_array($summary->status, ['approved', 'submitted', 'under_review']) ? 'completed' : 'pending'],
        ['label' => 'Completed', 'status' => $workflow->is_completed ? 'completed' : 'pending'],
    ];
    $badgeClasses = [
        'pending' => 'bg-slate-100 text-slate-600 border-slate-200',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
@endphp

<div class="bg-white rounded-[28px] shadow-sm border border-slate-200 p-6 mb-5">
    <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-2xl font-semibold text-slate-900">Workflow Progress</h3>
            <p class="text-sm text-slate-500 mt-1">GPOA → Communication Letter → Summary Report → Completed</p>
        </div>
        <div class="flex items-center gap-6 w-full xl:w-auto">
            <div class="text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Overall Completion</p>
                <p class="text-2xl font-bold text-orange-600">{{ $workflow->completion_percentage }}%</p>
            </div>
            <div class="w-full xl:w-48 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 ease-out"
                     style="width: {{ $workflow->completion_percentage }}%; background: linear-gradient(90deg, #f5a623, #e89600);"></div>
            </div>
        </div>
    </div>

    <div class="hidden md:block relative px-4 py-6">
        <div class="absolute left-8 right-8 top-10 h-px bg-slate-200"></div>
        <div class="grid grid-cols-4 gap-6 relative">
            @foreach($steps as $index => $step)
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full border-2 flex items-center justify-center text-lg font-bold mb-4 transition-all duration-300 {{ $step['status'] === 'completed' ? 'border-emerald-500 text-emerald-700 bg-white shadow-sm' : 'border-slate-300 text-slate-500 bg-white' }}">
                        {{ $index + 1 }}
                    </div>
                    <p class="text-sm font-semibold text-slate-900 leading-snug">{{ $step['label'] }}</p>
                    <span class="mt-3 inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses[$step['status']] }}">
                        {{ ucfirst($step['status']) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="md:hidden space-y-5">
        @foreach($steps as $index => $step)
            <div class="flex items-start gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full border-2 flex items-center justify-center text-base font-bold {{ $step['status'] === 'completed' ? 'border-emerald-500 text-emerald-700 bg-white shadow-sm' : 'border-slate-300 text-slate-500 bg-white' }}">
                        {{ $index + 1 }}
                    </div>
                    @if(!$loop->last)
                        <div class="w-px h-8 bg-slate-200 mt-2"></div>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-slate-900">{{ $step['label'] }}</p>
                    <span class="mt-2 inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses[$step['status']] }}">
                        {{ ucfirst($step['status']) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>
