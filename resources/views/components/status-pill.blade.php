@props(['status'])

@php
    $classes = match ($status) {
        'Pending' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'Ongoing' => 'bg-sky-100 text-sky-800 ring-sky-200',
        'Completed', 'Uploaded' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'Late', 'Needs Revision' => 'bg-rose-100 text-rose-800 ring-rose-200',
        'Submitted' => 'bg-sky-100 text-sky-800 ring-sky-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', $classes]) }}>{{ $status }}</span>