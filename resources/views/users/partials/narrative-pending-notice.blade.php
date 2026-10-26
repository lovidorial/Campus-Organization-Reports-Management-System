@if($narrativePendingRequests->isNotEmpty())
    <section class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-amber-950" role="alert">
        <p class="font-semibold">You can't request a new activity yet. Please submit the Narrative report for:</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
            @foreach($narrativePendingRequests as $pendingRequest)
                <li>
                    <a href="{{ route('activity-reports.create', $pendingRequest) }}" class="font-semibold underline">
                        {{ $pendingRequest->title }} ({{ $pendingRequest->date?->format('M d, Y') ?? 'date not set' }})
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif