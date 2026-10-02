@props(['status'])

@php
    $displayStatus = match ($status) {
        'Pending', 'Not Started' => 'Not Started',
        default => $status,
    };

    $classes = match ($status) {
        'Pending' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'Ongoing' => 'bg-sky-100 text-sky-800 ring-sky-200',
        'Completed', 'Uploaded', 'Approved' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'Archived' => 'bg-slate-200 text-slate-700 ring-slate-300',
        'Late', 'Needs Revision', 'Rejected' => 'bg-rose-100 text-rose-800 ring-rose-200',
        'For Review' => 'bg-sky-100 text-sky-800 ring-sky-200',
        'Submitted' => 'bg-sky-100 text-sky-800 ring-sky-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', $classes]) }}>
    @if(in_array($status, ['Uploaded', 'Approved'], true))
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.26a1 1 0 0 1-1.42 0l-3.8-3.83a1 1 0 1 1 1.42-1.41l3.09 3.11 6.49-6.54a1 1 0 0 1 1.414-.004Z" clip-rule="evenodd" /></svg>
    @endif
    {{ $displayStatus }}
</span>