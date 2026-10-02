@props(['status'])

@php
    $displayStatus = match ($status) {
        'Pending', 'Not Started' => 'Not Started',
        default => $status,
    };

    $classes = match ($status) {
        'Pending' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'Ongoing' => 'bg-sky-50 text-sky-700 border border-sky-200',
        'Completed', 'Uploaded', 'Approved' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'Archived' => 'bg-slate-50 text-slate-600 border border-slate-200',
        'Late', 'Needs Revision', 'Rejected' => 'bg-rose-50 text-rose-700 border border-rose-200',
        'For Review' => 'bg-sky-50 text-sky-700 border border-sky-200',
        'Submitted' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        default => 'bg-slate-50 text-slate-600 border border-slate-200',
    };
@endphp

<span {{ $attributes->class(['inline-flex h-5 items-center gap-1 whitespace-nowrap rounded-full px-2 text-[11px] font-medium', $classes]) }}>
    @if(in_array($status, ['Uploaded', 'Approved'], true))
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.26a1 1 0 0 1-1.42 0l-3.8-3.83a1 1 0 1 1 1.42-1.41l3.09 3.11 6.49-6.54a1 1 0 0 1 1.414-.004Z" clip-rule="evenodd" /></svg>
    @endif
    {{ $displayStatus }}
</span>