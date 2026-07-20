<x-app-layout>

@php
    $user = auth()->user();
    $orgName = $user->organization->name ?? $user->org_name ?? '—';
    $semester = str_replace('Term', 'Semester', $term);
    $academicYear = str_replace('-', '–', $schoolYear);
    $currentStatus = $workflow->currentStatusLabel();
    $statusColor = $workflow->currentStatusColor();
    $action = $workflow->currentActionInfo();

    $statusDotColors = [
        'green' => 'bg-green-500',
        'orange' => 'bg-orange-500',
        'red' => 'bg-red-500',
        'amber' => 'bg-amber-500',
        'blue' => 'bg-blue-500',
    ];

    $gpoaSub = $workflow->currentSubmission('gpoa');
    $commSub = $workflow->currentSubmission('communication_letter');
    $summarySub = $workflow->currentSubmission('summary_report');

    $documents = [
        [
            'step' => 1,
            'title' => 'GPOA',
            'subtitle' => 'General Plan of Activities',
            'submission' => $gpoaSub,
            'locked' => $workflow->is_locked,
            'lock_reason' => null,
            'can_submit' => !$workflow->is_locked && $workflow->canSubmitGpoa(),
            'submit_url' => route('gpoa.create'),
            'submit_label' => $gpoaSub?->status === 'rejected' ? 'Resubmit GPOA' : 'Submit GPOA',
            'edit_url' => ($gpoa && $workflow->canEditGpoa()) ? route('gpoa.edit', $gpoa) : null,
            'view_url' => $gpoa ? route('gpoa.index') : null,
            'awaiting' => null,
        ],
        [
            'step' => 2,
            'title' => 'Communication Letter',
            'subtitle' => 'Official correspondence document',
            'submission' => $commSub,
            'locked' => !$workflow->isGpoaApproved() || $workflow->is_locked,
            'lock_reason' => 'Awaiting GPOA approval',
            'can_submit' => $workflow->canSubmitCommunicationLetter(),
            'submit_url' => route('workflow.communication-letter'),
            'submit_label' => $commSub?->status === 'rejected' ? 'Resubmit Letter' : 'Upload Letter',
            'edit_url' => null,
            'view_url' => $commSub ? route('workflow.communication-letter') : null,
            'awaiting' => !$workflow->isGpoaApproved() ? 'Awaiting GPOA approval' : null,
        ],
        [
            'step' => 3,
            'title' => 'Summary Report',
            'subtitle' => 'End-of-term activity summary',
            'submission' => $summarySub,
            'locked' => !$workflow->canSubmitSummaryReport() && !($summarySub) || ($workflow->is_locked && !$workflow->is_completed),
            'lock_reason' => 'Awaiting Communication Letter approval',
            'can_submit' => $workflow->canSubmitSummaryReport(),
            'submit_url' => route('workflow.summary-report'),
            'submit_label' => $summarySub?->status === 'rejected' ? 'Resubmit Report' : 'Submit Report',
            'edit_url' => null,
            'view_url' => $summarySub ? route('workflow.summary-report') : null,
            'awaiting' => !($commSub?->status === 'approved') ? 'Awaiting Communication Letter approval' : null,
        ],
    ];

    $actionCardStyles = [
        'action_required' => 'border-l-4 border-l-amber-500 bg-gradient-to-r from-amber-50/80 to-white',
        'waiting' => 'border-l-4 border-l-green-500 bg-gradient-to-r from-green-50/60 to-white',
        'completed' => 'border-l-4 border-l-green-500 bg-gradient-to-r from-green-50/80 to-white',
        'rejected' => 'border-l-4 border-l-red-500 bg-gradient-to-r from-red-50/60 to-white',
    ];
@endphp

{{-- Welcome Header --}}
<div class="rounded-2xl p-4 md:p-5 mb-4 text-white shadow-lg transition-shadow duration-300 hover:shadow-xl"
     style="background: linear-gradient(135deg, #f5a623 0%, #e89600 100%);">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}"
                     alt="{{ $user->name }}"
                     class="w-14 h-14 md:w-16 md:h-16 rounded-2xl object-cover border-2 border-white/40 shadow-md shrink-0"/>
            @else
                <div class="w-16 h-16 md:w-[4.5rem] md:h-[4.5rem] rounded-2xl bg-white/95 flex items-center justify-center text-2xl font-bold shadow-md shrink-0" style="color: #e89600;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <p class="text-white/80 text-xs font-medium">Welcome,</p>
                <h1 class="text-lg md:text-xl font-bold tracking-tight">{{ $user->name }}</h1>
                @if($user->position)
                <p class="text-white/75 text-xs mt-0.5">{{ $user->position }}</p>
                @endif
            </div>
        </div>

        @if($unreadCount > 0)
        <a href="{{ route('notifications.index') }}"
           class="inline-flex items-center gap-2 self-start lg:self-center bg-white/15 hover:bg-white/25 backdrop-blur-sm px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 border border-white/20">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
            </span>
            {{ $unreadCount }} new notification{{ $unreadCount > 1 ? 's' : '' }}
        </a>
        @endif
    </div>

    <div class="mt-4 pt-4 border-t border-white/20 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
            <p class="text-white/70 text-[10px] font-semibold uppercase tracking-wider mb-1">Organization</p>
            <p class="font-semibold text-sm">{{ $orgName }}</p>
        </div>
        <div>
            <p class="text-white/70 text-[10px] font-semibold uppercase tracking-wider mb-1">Current Semester</p>
            <p class="font-semibold text-sm">{{ $semester }}</p>
        </div>
        <div>
            <p class="text-white/70 text-[10px] font-semibold uppercase tracking-wider mb-1">Academic Year</p>
            <p class="font-semibold text-sm">{{ $academicYear }}</p>
        </div>
        <div>
            <p class="text-white/70 text-[10px] font-semibold uppercase tracking-wider mb-1">Current Status</p>
            <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm px-2.5 py-1 rounded-xl border border-white/20">
                <span class="w-2.5 h-2.5 rounded-full {{ $statusDotColors[$statusColor] ?? 'bg-gray-400' }} shrink-0"></span>
                <span class="font-semibold text-xs">{{ $currentStatus }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Current Action Card --}}
<div class="rounded-2xl border border-gray-100 shadow-sm p-5 md:p-6 mb-5 transition-all duration-300 hover:shadow-md {{ $actionCardStyles[$action['type']] ?? $actionCardStyles['waiting'] }}">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
                @if($action['type'] === 'action_required')
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-amber-100 text-amber-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                @elseif($action['type'] === 'completed')
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-green-100 text-green-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                @else
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-green-100 text-green-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
                @endif
                <h2 class="text-lg font-bold text-gray-900">{{ $action['title'] }}</h2>
            </div>
            <p class="text-gray-800 font-medium leading-relaxed">{{ $action['message'] }}</p>
            @if($action['submessage'])
            <p class="text-sm mt-2 leading-relaxed {{ str_contains($action['message'], 'rejected') ? 'bg-red-50 border border-red-100 rounded-xl px-3 py-2 text-red-700' : 'text-gray-600' }}">
                {{ $action['submessage'] }}
            </p>
            @endif

            <div class="flex flex-wrap gap-4 mt-4">
                @if($action['estimated_review'])
                <div class="flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-gray-500">Estimated Review Time:</span>
                    <span class="font-semibold text-gray-800">{{ $action['estimated_review'] }}</span>
                </div>
                @endif
                @if($action['deadline'])
                <div class="flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-gray-500">Deadline:</span>
                    <span class="font-semibold text-amber-700">{{ $action['deadline']->format('F j, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($action['action_url'] && $action['action_label'])
        <a href="{{ $action['action_url'] }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 shrink-0"
           style="background: linear-gradient(135deg, #f5a623, #e89600);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            {{ $action['action_label'] }}
        </a>
        @endif
    </div>
</div>

@include('components.workflow-progress', ['workflow' => $workflow, 'progressStages' => $progressStages])

{{-- Document Cards --}}
<div class="mb-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 tracking-tight">Your Documents</h2>
            <p class="text-sm text-gray-500">Track submission status for each required document</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($documents as $doc)
        @php
            $sub = $doc['submission'];
            $isLocked = $doc['locked'] && !$sub;
        @endphp
        <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col h-full transition-all duration-300 hover:shadow-md hover:border-orange-200 hover:-translate-y-0.5 {{ $isLocked ? 'opacity-70' : '' }}">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-sm transition-transform duration-300 group-hover:scale-105"
                          style="background: linear-gradient(135deg, #f5a623, #e89600);">
                        {{ $doc['step'] }}
                    </span>
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $doc['title'] }}</h3>
                        <p class="text-xs text-gray-500">{{ $doc['subtitle'] }}</p>
                    </div>
                </div>
                @if($isLocked)
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-gray-400 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Locked
                </span>
                @endif
            </div>

            <div class="flex-1 space-y-3">
                @if($sub)
                <div>
                    <span class="inline-flex text-xs px-2.5 py-1 rounded-full border font-semibold {{ $sub->statusClasses() }}">
                        {{ ucfirst(str_replace('_', ' ', $sub->status)) }}
                    </span>
                </div>

                <div class="space-y-2 text-sm">
                    @if($sub->submitted_at)
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
                        <span class="text-gray-500 text-xs">Submitted</span>
                        <span class="text-gray-800 text-xs font-medium">{{ $sub->submitted_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
                        <span class="text-gray-500 text-xs">Version</span>
                        <span class="text-gray-800 text-xs font-medium">v{{ $sub->version }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
                        <span class="text-gray-500 text-xs">Last Updated</span>
                        <span class="text-gray-800 text-xs font-medium">{{ $sub->updated_at->format('M d, Y') }}</span>
                    </div>
                    @if($sub->approved_at)
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-50">
                        <span class="text-gray-500 text-xs">Approved</span>
                        <span class="text-green-700 text-xs font-medium">{{ $sub->approved_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                    @if($sub->reviewer)
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-gray-500 text-xs">Reviewer</span>
                        <span class="text-gray-800 text-xs font-medium truncate max-w-[120px]" title="{{ $sub->reviewer->name }}">{{ $sub->reviewer->name }}</span>
                    </div>
                    @endif
                </div>

                @if($sub->reject_reason)
                <div class="bg-red-50 border border-red-100 rounded-xl px-3 py-2">
                    <p class="text-[10px] font-semibold text-red-600 uppercase tracking-wide mb-0.5">OSDW Feedback</p>
                    <p class="text-xs text-red-700 leading-relaxed">{{ $sub->reject_reason }}</p>
                </div>
                @endif

                @if($sub->approval_remarks)
                <div class="bg-green-50 border border-green-100 rounded-xl px-3 py-2">
                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-0.5">Remarks</p>
                    <p class="text-xs text-green-700 leading-relaxed">{{ $sub->approval_remarks }}</p>
                </div>
                @endif
                @else
                <div>
                    <span class="inline-flex text-xs px-2.5 py-1 rounded-full border font-semibold bg-gray-50 text-gray-500 border-gray-200">Not Submitted</span>
                </div>

                @endif
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
                @if($doc['can_submit'] && (!$sub || $sub->status === 'rejected' || ($doc['step'] === 1 && !$sub)))
                <a href="{{ $doc['submit_url'] }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white text-xs font-semibold transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 {{ $sub?->status === 'rejected' ? 'bg-red-500 hover:bg-red-600' : '' }}"
                   @if($sub?->status !== 'rejected') style="background: linear-gradient(135deg, #f5a623, #e89600);" @endif>
                    {{ $doc['submit_label'] }}
                </a>
                @endif
                @if($doc['edit_url'])
                <a href="{{ $doc['edit_url'] }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 transition-all duration-300">
                    Edit GPOA
                </a>
                @endif
                @if($doc['view_url'] && $sub)
                <a href="{{ $doc['view_url'] }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-gray-200 text-gray-600 text-xs font-semibold hover:border-orange-300 hover:text-orange-700 transition-all duration-300">
                    View
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Notifications --}}
@if($notifications->count())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6 mb-5 transition-all duration-300 hover:shadow-md">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="font-bold text-gray-900">Recent Notifications</h3>
            <p class="text-xs text-gray-500 mt-0.5">Updates from OSDW on your submissions</p>
        </div>
        <a href="{{ route('notifications.index') }}" class="text-sm font-semibold hover:underline transition-colors" style="color:#e89600;">View all →</a>
    </div>
    <div class="space-y-2">
        @foreach($notifications as $notification)
        <div class="flex items-start gap-3 p-3.5 rounded-xl transition-colors duration-200 {{ $notification->read_at ? 'bg-gray-50 hover:bg-gray-100' : 'bg-orange-50/50 border border-orange-100 hover:bg-orange-50' }}">
            <div class="w-2 h-2 rounded-full mt-2 shrink-0 {{ $notification->read_at ? 'bg-gray-300' : 'bg-orange-500' }}"></div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800">{{ $notification->title }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ $notification->message }}</p>
                <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Submission History --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6 mb-5 transition-all duration-300 hover:shadow-md">
    <div class="mb-4">
        <h3 class="font-bold text-gray-900">Submission History</h3>
        <p class="text-xs text-gray-500 mt-0.5">Complete record of all document versions</p>
    </div>
    @if($submissionHistory->count())
    <div class="overflow-x-auto -mx-1">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Document</th>
                    <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Version</th>
                    <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Submitted</th>
                    <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Approved</th>
                    <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Reviewer</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($submissionHistory as $sub)
                <tr class="hover:bg-orange-50/30 transition-colors duration-200">
                    <td class="px-3 py-3 font-medium text-gray-800">{{ $sub->documentLabel() }}</td>
                    <td class="px-3 py-3 text-gray-600">v{{ $sub->version }}</td>
                    <td class="px-3 py-3">
                        <span class="text-xs px-2.5 py-1 rounded-full border font-semibold {{ $sub->statusClasses() }}">
                            {{ ucfirst(str_replace('_', ' ', $sub->status)) }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-xs text-gray-500">{{ $sub->submitted_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-3 py-3 text-xs text-gray-500">{{ $sub->approved_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-3 py-3 text-xs text-gray-600">{{ $sub->reviewer?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-8">
        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-gray-500 text-sm">No submissions yet.</p>
        <p class="text-gray-400 text-xs mt-1">Start by submitting your GPOA.</p>
        @if(!$workflow->is_locked)
        <a href="{{ route('gpoa.create') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 text-white rounded-xl text-sm font-semibold transition-all duration-300 hover:shadow-md"
           style="background: linear-gradient(135deg, #f5a623, #e89600);">
            Submit GPOA
        </a>
        @endif
    </div>
    @endif
</div>

{{-- Activity Requests (secondary) --}}
@if($hasApprovedGpoa)
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-5">
    @foreach([
        ['label' => 'Requests', 'value' => $stats['total'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
        ['label' => 'Pending', 'value' => $stats['pending'], 'color' => 'text-amber-600', 'bg' => 'bg-amber-50'],
        ['label' => 'Active', 'value' => $stats['approved'], 'color' => 'text-green-600', 'bg' => 'bg-green-50'],
        ['label' => 'Rejected', 'value' => $stats['rejected'], 'color' => 'text-red-600', 'bg' => 'bg-red-50'],
    ] as $stat)
    <div class="bg-white p-4 md:p-5 rounded-2xl shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl {{ $stat['bg'] }} flex items-center justify-center">
                <span class="text-lg font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</span>
            </div>
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">{{ $stat['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6 transition-all duration-300 hover:shadow-md">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Recent Activity Requests</h3>
            <p class="text-xs text-gray-500 mt-0.5">Activities approved through your GPOA</p>
        </div>
        <a href="{{ route('activity-requests.index') }}" class="text-sm font-semibold hover:underline transition-colors" style="color:#e89600;">View all →</a>
    </div>
    @if($activities->count() > 0)
    <div class="overflow-x-auto -mx-1">
        <table class="w-full text-sm min-w-[500px]">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Title</th>
                    <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                    <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Venue</th>
                    <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($activities as $activity)
                <tr class="hover:bg-orange-50/30 transition-colors duration-200">
                    <td class="py-3 px-3 font-medium text-gray-800">{{ $activity->title }}</td>
                    <td class="py-3 px-3 text-gray-600">{{ $activity->date->format('M d, Y') }}</td>
                    <td class="py-3 px-3 text-gray-600">{{ $activity->venue }}</td>
                    <td class="py-3 px-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                            {{ str_replace('_', ' ', ucfirst($activity->status)) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-8">
        <p class="text-gray-400 text-sm mb-3">No activity requests yet.</p>
        <a href="{{ route('activity-requests.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-sm font-semibold transition-all duration-300 hover:shadow-md"
           style="background: linear-gradient(135deg, #f5a623, #e89600);">
            Request Activity
        </a>
    </div>
    @endif
</div>
@endif

</x-app-layout>
