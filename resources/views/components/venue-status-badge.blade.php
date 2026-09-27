@props(['venue'])

@php
    $status = $venue?->availability_status ?? 'Available';
    $classes = match ($status) {
        'Scheduled' => 'bg-blue-100 text-blue-800',
        'Reserved' => 'bg-amber-100 text-amber-800',
        default => 'bg-green-100 text-green-800',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap', $classes]) }}>
    {{ $status }}
</span>
