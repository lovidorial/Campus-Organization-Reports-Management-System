<x-app-layout>
    @php
        $request = $activityRequest;
        $fieldClass = 'min-w-0 rounded border border-slate-200 bg-white px-2 py-1.5';
        $labelClass = 'block text-[10px] font-semibold uppercase text-slate-500';
        $valueClass = 'mt-0.5 break-words whitespace-pre-wrap text-xs text-slate-800';
        $monitoring = $request->gpoaActivity?->monitoringStatus();
    @endphp

    <main class="mx-auto max-w-6xl space-y-3 p-0 sm:p-4">
        <header class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[11px] text-slate-500">Activity Request #{{ $request->id }}</p>
                <h1 class="truncate text-lg font-bold text-slate-900">{{ $request->title }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('activity-requests.pdf', $request) }}" class="rounded bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800">Download Activity Details PDF</a>
                <a href="{{ route('activity-requests.index') }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Back to requests</a>
            </div>
        </header>

        <section class="rounded border border-slate-200 bg-slate-50 p-2.5">
            <div class="mb-2 flex flex-wrap items-center gap-1.5">
                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold bg-sky-100 text-sky-800">Monitoring: {{ $monitoring['status'] ?? 'Not Started' }}</span>
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
                <div class="{{ $fieldClass }}"><dt class="{{ $labelClass }}">Created / Updated</dt><dd class="{{ $valueClass }}">{{ $request->created_at?->format('M d, Y H:i') ?? '—' }} / {{ $request->updated_at?->format('M d, Y H:i') ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="grid gap-3 md:grid-cols-2">
            <div class="rounded border border-slate-200 bg-white p-3">
                <h2 class="mb-2 text-sm font-bold text-slate-800">Communication Letter</h2>
                @if($request->communication_letter)
                    <a href="{{ route('activity-requests.documents.show', [$request, 'communication-letter']) }}" class="text-xs font-semibold text-sky-700 underline">{{ basename($request->communication_letter) }}</a>
                @else
                    <p class="mb-2 text-xs text-slate-500">No letter uploaded.</p>
                @endif
                <form method="POST" action="{{ route('activity-requests.communication-letter.store', $request) }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                    @csrf
                    <input type="file" name="communication_letter" accept=".pdf,application/pdf" required class="block w-full text-xs">
                    @error('communication_letter')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <label class="flex items-start gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="signed_confirmation" value="1" required class="mt-0.5 rounded border-slate-300">
                        <span>I confirm this letter is signed by the required signatories.</span>
                    </label>
                    @error('signed_confirmation')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button type="submit" class="rounded bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800">Upload Letter</button>
                </form>
            </div>

            <div class="rounded border border-slate-200 bg-white p-3">
                <h2 class="mb-2 text-sm font-bold text-slate-800">Narrative Report</h2>
                @if($request->report)
                    <p class="mb-2 text-xs text-slate-600">{{ ucfirst($request->report->narrative_source ?? 'uploaded') }} report saved.</p>
                    @if($request->report->narrative_report)
                        <a href="{{ route('activity-requests.documents.show', [$request, 'narrative-report']) }}" class="text-xs font-semibold text-sky-700 underline">View report PDF</a>
                    @endif
                    @if(data_get($request->report->narrative_content, 'body'))
                        <div class="mt-2 max-h-40 overflow-y-auto whitespace-pre-wrap rounded bg-slate-50 p-2 text-xs text-slate-700">{{ data_get($request->report->narrative_content, 'body') }}</div>
                    @endif
                @else
                    <p class="mb-2 text-xs text-slate-500">No narrative report saved.</p>
                @endif
                <a href="{{ route('activity-reports.create', $request) }}" class="mt-3 inline-flex rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">{{ $request->report ? 'Update Narrative Report' : 'Add Narrative Report' }}</a>
            </div>
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
