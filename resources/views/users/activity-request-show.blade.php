<x-app-layout>
    @php
        $request = $activityRequest;
        $statusClasses = [
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-green-100 text-green-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'awaiting_report' => 'bg-orange-100 text-orange-800',
            'report_submitted' => 'bg-indigo-100 text-indigo-800',
            'closed' => 'bg-slate-100 text-slate-700',
            'rejected' => 'bg-red-100 text-red-800',
        ];
        $fieldClass = 'min-w-0 rounded border border-slate-200 bg-white px-2 py-1.5';
        $labelClass = 'block text-[10px] font-semibold uppercase text-slate-500';
        $valueClass = 'mt-0.5 break-words whitespace-pre-wrap text-xs text-slate-800';
        $fileLink = fn (?string $path) => $path
            ? '<a href="' . e(asset('storage/' . $path)) . '" target="_blank" rel="noopener" class="text-sky-700 underline">Open file</a>'
            : '—';
        $canDownloadPdf = in_array($request->status, [
            \App\Models\ActivityRequest::STATUS_APPROVED,
            \App\Models\ActivityRequest::STATUS_IN_PROGRESS,
            \App\Models\ActivityRequest::STATUS_AWAITING_REPORT,
            \App\Models\ActivityRequest::STATUS_REPORT_SUBMITTED,
            \App\Models\ActivityRequest::STATUS_CLOSED,
        ], true);
    @endphp

    <main class="mx-auto max-w-6xl space-y-3 p-3 sm:p-4">
        <header class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[11px] text-slate-500">Activity Request #{{ $request->id }}</p>
                <h1 class="truncate text-lg font-bold text-slate-900">{{ $request->title }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @if($canDownloadPdf)
                    <a href="{{ route('activity-requests.pdf', $request) }}" class="rounded bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800">Download PDF</a>
                @endif
                <a href="{{ route('activity-requests.index') }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Back to requests</a>
            </div>
        </header>

        <section class="rounded border border-slate-200 bg-slate-50 p-2.5">
            <div class="mb-2 flex flex-wrap items-center gap-1.5">
                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClasses[$request->status] ?? 'bg-slate-100 text-slate-700' }}">{{ str_replace('_', ' ', ucfirst($request->status)) }}</span>
                @if($request->is_urgent)
                    <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-800">Urgent</span>
                @endif
            </div>

            <dl class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Title</dt><dd class="{{ $valueClass }}">{{ $request->title }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Category</dt><dd class="{{ $valueClass }}">{{ $request->category ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Venue</dt><dd class="{{ $valueClass }}">{{ $request->venue ?? '—' }} <x-venue-status-badge :venue="$request->venueRecord" /></dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Request / User / GPOA / Planned Activity / Venue IDs</dt><dd class="{{ $valueClass }}">{{ $request->id }} / {{ $request->user_id }} / {{ $request->gpoa_id ?? '—' }} / {{ $request->gpoa_activity_id ?? '—' }} / {{ $request->venue_id ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Date / End Date</dt><dd class="{{ $valueClass }}">{{ $request->date?->format('M d, Y') ?? '—' }}{{ $request->end_date ? ' – ' . $request->end_date->format('M d, Y') : '' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Start / End Time</dt><dd class="{{ $valueClass }}">{{ $request->start_time ? substr((string) $request->start_time, 0, 5) : '—' }} – {{ $request->end_time ? substr((string) $request->end_time, 0, 5) : '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Activity Level</dt><dd class="{{ $valueClass }}">{{ $request->activity_level ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">SDGs</dt><dd class="{{ $valueClass }}">{{ $request->sdgs ? implode(', ', $request->sdgs) : '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Description</dt><dd class="{{ $valueClass }}">{{ $request->description ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Objectives</dt><dd class="{{ $valueClass }}">{{ $request->objectives ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Expected Outcome</dt><dd class="{{ $valueClass }}">{{ $request->expected_outcome ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Plan / Key Strategy</dt><dd class="{{ $valueClass }}">{{ $request->plan_key_strategy ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Target Participants</dt><dd class="{{ $valueClass }}">{{ $request->target_participants ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Participants Count</dt><dd class="{{ $valueClass }}">{{ $request->participants_count ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Person in Charge</dt><dd class="{{ $valueClass }}">{{ $request->person_in_charge ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Facilities / Materials</dt><dd class="{{ $valueClass }}">{{ $request->facilities_materials ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Estimated Budget</dt><dd class="{{ $valueClass }}">{{ $request->estimated_budget !== null ? number_format((float) $request->estimated_budget, 2) : '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Source of Funds</dt><dd class="{{ $valueClass }}">{{ $request->source_of_funds ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Preceding Activity</dt><dd class="{{ $valueClass }}">{{ $request->preceding_activity ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Remarks</dt><dd class="{{ $valueClass }}">{{ $request->remarks ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Rejection Reason</dt><dd class="{{ $valueClass }}">{{ $request->reject_reason ?? '—' }}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Urgent</dt><dd class="{{ $valueClass }}">{{ $request->is_urgent ? 'Yes' : 'No' }}</dd></div>
                @if($request->is_urgent && $request->urgent_reason)
                    <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Urgent Reason</dt><dd class="{{ $valueClass }}">{{ $request->urgent_reason }}</dd></div>
                @endif
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Communication Letter</dt><dd class="{{ $valueClass }}">{!! $fileLink($request->communication_letter) !!}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Reservation Slip</dt><dd class="{{ $valueClass }}">{!! $fileLink($request->reservation_slip) !!}</dd></div>
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Created / Updated</dt><dd class="{{ $valueClass }}">{{ $request->created_at?->format('M d, Y H:i') ?? '—' }} / {{ $request->updated_at?->format('M d, Y H:i') ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded border border-slate-200 bg-white p-2.5">
            <h2 class="mb-1.5 text-xs font-bold text-slate-800">Program Flow</h2>
            @if($request->programFlows->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-100 text-left text-[10px] uppercase text-slate-500">
                            <tr><th class="px-2 py-1">Time</th><th class="px-2 py-1">Flow</th><th class="px-2 py-1">Person in Charge</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($request->programFlows as $flow)
                                <tr><td class="px-2 py-1">{{ $flow->time }}</td><td class="px-2 py-1">{{ $flow->flow }}</td><td class="px-2 py-1">{{ $flow->person_in_charge }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-slate-500">No program flow added.</p>
            @endif
        </section>
    </main>
</x-app-layout>
