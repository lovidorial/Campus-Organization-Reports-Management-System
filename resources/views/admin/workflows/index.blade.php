<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">GPOA Review</h2>
        <p class="text-sm text-gray-500">Track all organization document submissions and workflow progress</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.workflows.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export CSV
        </a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Total</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['total'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">All submissions</p>
    </div>
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Completed</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['completed'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">Completed submissions</p>
    </div>
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">In Progress</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['pending'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">Currently in progress</p>
    </div>
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Not Started</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['not_started'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">Not yet started</p>
    </div>
    <div class="bg-white p-5 border border-gray-100 shadow-sm">
        <p class="text-[13px] text-[#94a3b8] font-bold uppercase tracking-wide">Overdue (30d+)</p>
        <p class="text-[26px] font-normal text-[#334155] leading-tight">{{ $stats['overdue'] }}</p>
        <p class="text-[11px] text-[#64748b] mt-0.5">Past due submissions</p>
    </div>
</div>

{{-- Pending Reviews hidden to match exact screenshot layout --}}

<!-- Filters -->
<form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap items-center gap-3">
    <div class="relative flex-1 min-w-[240px]">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </span>
        <input type="text" name="search" placeholder="Search organization..." value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400">
    </div>
    
    <div class="relative min-w-[140px]">
        <select name="status" class="w-full appearance-none bg-white border border-gray-200 rounded-xl pl-4 pr-4 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">All Status</option>
            <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>In Progress</option>
            <option value="overdue" {{ request('status')=='overdue'?'selected':'' }}>Overdue</option>
        </select>
    </div>
    
    <div class="relative min-w-[140px]">
        <select name="stage" class="w-full appearance-none bg-white border border-gray-200 rounded-xl pl-4 pr-4 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">All Stages</option>
            @foreach(['gpoa_pending','gpoa_submitted','gpoa_approved','comm_submitted','comm_approved','summary_submitted','summary_approved','completed'] as $s)
            <option value="{{ $s }}" {{ request('stage')==$s?'selected':'' }}>{{ str_replace('_',' ',ucfirst($s)) }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
        </svg>
        Filter
    </button>
    <a href="{{ route('admin.workflows.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition-colors flex items-center justify-center">
        Reset
    </a>
</form>

<!-- Workflows Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-white border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-4 font-semibold text-gray-800">Organization</th>
                    <th class="text-left px-5 py-4 font-semibold text-gray-800">Term / SY</th>
                    <th class="text-left px-5 py-4 font-semibold text-gray-800">Current Stage</th>
                    <th class="text-left px-5 py-4 font-semibold text-gray-800">Progress</th>
                    <th class="text-left px-5 py-4 font-semibold text-gray-800">Status</th>
                    <th class="text-left px-5 py-4 font-semibold text-gray-800">Last Updated</th>
                    <th class="text-center px-5 py-4 font-semibold text-gray-800">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($workflows as $workflow)
                @php
                    $words = explode(' ', $workflow->user->org_name ?? $workflow->user->name);
                    $initials = count($words) >= 2 ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) : strtoupper(substr($words[0] ?? '', 0, 2));

                    $stageText = '';
                    $stageNumber = '';
                    $dotColor = 'text-amber-500';
                    switch($workflow->current_stage) {
                        case 'gpoa_pending':
                        case 'gpoa_submitted':
                            $stageText = $workflow->current_stage === 'gpoa_pending' ? 'Gpoa pending' : 'Gpoa submitted';
                            $stageNumber = 'Stage 1 of 4';
                            $dotColor = 'text-amber-500';
                            break;
                        case 'gpoa_approved':
                            $stageText = 'Gpoa approved';
                            $stageNumber = 'Stage 2 of 4';
                            $dotColor = 'text-green-500';
                            break;
                        case 'comm_submitted':
                            $stageText = 'Comm submitted';
                            $stageNumber = 'Stage 2 of 4';
                            $dotColor = 'text-amber-500';
                            break;
                        case 'comm_approved':
                            $stageText = 'Comm approved';
                            $stageNumber = 'Stage 3 of 4';
                            $dotColor = 'text-green-500';
                            break;
                        case 'summary_submitted':
                            $stageText = 'Summary submitted';
                            $stageNumber = 'Stage 3 of 4';
                            $dotColor = 'text-amber-500';
                            break;
                        case 'summary_approved':
                        case 'completed':
                            $stageText = $workflow->current_stage === 'completed' ? 'Completed' : 'Summary approved';
                            $stageNumber = 'Stage 4 of 4';
                            $dotColor = 'text-green-500';
                            break;
                        default:
                            $stageText = str_replace('_', ' ', ucfirst($workflow->current_stage));
                            $stageNumber = 'Stage 1 of 4';
                            $dotColor = 'text-amber-500';
                    }
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            @if($workflow->user->profile_photo_path)
                                <img src="{{ asset('storage/'.$workflow->user->profile_photo_path) }}" class="w-10 h-10 rounded-lg object-cover">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-gray-900 text-white flex items-center justify-center font-bold text-sm uppercase">
                                    {{ $initials }}
                                </div>
                            @endif
                            <div>
                                <p class="font-bold text-gray-900">{{ $workflow->user->org_name ?? $workflow->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $workflow->user->org_type ?? 'Organization' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-gray-800">{{ $workflow->term }}</p>
                        <p class="text-xs text-gray-500">{{ $workflow->school_year }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-1.5">
                            <span class="{{ $dotColor }} text-[10px]">●</span>
                            <span class="font-semibold text-gray-700">{{ $stageText }}</span>
                        </div>
                        <p class="text-xs text-gray-500 ml-3">{{ $stageNumber }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-gray-800 w-6">{{ $workflow->completion_percentage }}%</span>
                            <div class="w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300" style="width:{{ $workflow->completion_percentage }}%;background:#f5a623;"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        @if($workflow->is_completed)
                        <span class="text-xs px-3 py-1 rounded-md bg-[#f0fdf4] text-[#15803d] font-semibold">Completed</span>
                        @elseif($workflow->is_locked)
                        <span class="text-xs px-3 py-1 rounded-md bg-gray-100 text-gray-700 font-semibold">Locked</span>
                        @else
                        <span class="text-xs px-3 py-1 rounded-md bg-[#fef3c7] text-[#b45309] font-semibold">In Progress</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-sm font-medium text-gray-700">{{ $workflow->updated_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $workflow->updated_at->format('h:i A') }}</p>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-4">
                            <a href="{{ route('admin.workflows.show', $workflow) }}" class="text-blue-600 hover:underline text-xs font-bold tracking-wide">View Details</a>
                            <button class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No workflows found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Custom Pagination -->
    @if($workflows->hasPages() || $workflows->total() > 0)
    <div class="px-6 py-4 flex flex-col md:flex-row items-center justify-between border-t border-gray-100 gap-4">
        <div class="text-sm text-gray-500 font-medium">
            Showing {{ $workflows->firstItem() ?? 0 }} to {{ $workflows->lastItem() ?? 0 }} of {{ $workflows->total() }} entries
        </div>
        @if($workflows->hasPages())
        <div class="flex items-center gap-1.5">
            {{-- Previous Page Link --}}
            @if ($workflows->onFirstPage())
                <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </span>
            @else
                <a href="{{ $workflows->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($workflows->getUrlRange(1, $workflows->lastPage()) as $page => $url)
                @if ($page == $workflows->currentPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#d97706] text-white font-semibold text-sm shadow-sm">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium transition-colors">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($workflows->hasMorePages())
                <a href="{{ $workflows->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
        @endif
    </div>
    @endif
</div>
</x-app-layout>
