@props(['number', 'showLabel' => true])

@php($sdg = config('sdg.' . $number))

<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold whitespace-nowrap" style="background-color: {{ $sdg['color'] ?? '#6B7280' }}; color: {{ $sdg['text'] ?? '#FFFFFF' }};">{{ $showLabel ? 'SDG ' . $number : $number }}</span>