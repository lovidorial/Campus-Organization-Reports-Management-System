<x-app-layout>
    @php
        $activityRequest = $activityRequest;
        $report = $activityRequest->report;
        $plannedActivity = $activityRequest->gpoaActivity;
        $reportStatus = match ($report?->status) {
            'approved' => 'Approved',
            'needs_revision' => 'Needs revision',
            'rejected' => 'Rejected',
            default => 'Pending',
        };
        $assessmentLabels = ['aligned' => 'Aligned', 'partial' => 'Partially aligned', 'not_aligned' => 'Not aligned'];
    @endphp

    <main class="mx-auto max-w-6xl space-y-4 px-4 py-5 sm:px-6 lg:px-8">
        @if($errors->any())
            <div role="alert" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs text-slate-500">Activity Request #{{ $activityRequest->id }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="break-words text-xl font-bold text-slate-900">{{ $activityRequest->title }}</h1>
                    <x-status-pill :status="$plannedActivity?->monitoringStatus()['status'] ?? 'Pending'" />
                </div>
                <p class="mt-1 text-sm text-slate-600">{{ $activityRequest->user?->org_name ?? $activityRequest->user?->name ?? 'Organization' }} · {{ $activityRequest->gpoa?->college ?? '—' }}</p>
                @if($report)
                    <div class="mt-2 flex flex-wrap gap-2">
                        <x-status-pill :status="$reportStatus" />
                    </div>
                @endif
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('activity-requests.pdf', $activityRequest) }}" data-download-loading class="rounded-md bg-sky-700 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-800">Download PDF</a>
                <a href="{{ $backUrl }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Activity Monitoring</a>
            </div>
        </header>

        <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <h2 class="text-sm font-bold uppercase tracking-[0.12em] text-slate-900">Activity details</h2>

            @php
                $startTime = $activityRequest->start_time ? substr((string) $activityRequest->start_time, 0, 5) : null;
                $endTime = $activityRequest->end_time ? substr((string) $activityRequest->end_time, 0, 5) : null;
                $timeRange = 'Not set';
                if ($startTime && $endTime) {
                    $timeRange = $startTime . ' - ' . $endTime;
                } elseif ($startTime) {
                    $timeRange = $startTime;
                } elseif ($endTime) {
                    $timeRange = $endTime;
                }

                $detailSections = [
                    'General Information' => [
                        'Organization' => $activityRequest->user?->org_name ?? $activityRequest->user?->name ?? '—',
                        'College' => $activityRequest->gpoa?->college ?? '—',
                        'Category' => $activityRequest->category ?? '—',
                    ],
                    'Schedule and Venue' => [
                        'Venue' => $activityRequest->venue ?? '—',
                        'Date' => $activityRequest->date?->format('M j, Y') ?? '—',
                        'End date' => $activityRequest->end_date?->format('M j, Y') ?? '—',
                        'Start / end time' => $timeRange,
                        'Term / School year' => ($activityRequest->gpoa?->term ?? '—') . ' / ' . ($activityRequest->gpoa?->school_year ?? '—'),
                    ],
                    'Objectives and Outcomes' => [
                        'Objectives' => $activityRequest->objectives ?? '—',
                        'Expected outcome' => $activityRequest->expected_outcome ?? '—',
                    ],
                    'Participants and Person in Charge' => [
                        'Target participants' => $activityRequest->target_participants ?? '—',
                        'Person in charge' => $activityRequest->person_in_charge ?? '—',
                    ],
                    'Budget' => [
                        'Estimated budget' => $activityRequest->estimated_budget !== null ? number_format((float) $activityRequest->estimated_budget, 2) : '—',
                        'Source of funds' => $activityRequest->source_of_funds ?? '—',
                    ],
                ];
            @endphp

            <div class="mt-4 space-y-5">
                @foreach($detailSections as $heading => $items)
                    <div>
                        <h3 class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $heading }}</h3>
                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($items as $label => $value)
                                <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-600">{{ $label }}</dt>
                                    <dd class="mt-1 break-words whitespace-pre-wrap text-sm text-slate-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-bold text-slate-900">Program flow</h2>
            @if($activityRequest->programFlows->isNotEmpty())
                <ol class="mt-2 divide-y divide-slate-100">
                    @foreach($activityRequest->programFlows as $flow)
                        <li class="grid gap-1 py-2 text-sm sm:grid-cols-[120px_1fr_200px]"><span class="font-medium text-slate-700">{{ $flow->time ?: 'TBA' }}</span><span class="text-slate-800">{{ $flow->flow }}</span><span class="text-slate-500">{{ $flow->person_in_charge ?: '—' }}</span></li>
                    @endforeach
                </ol>
            @else
                <p class="mt-2 text-sm text-slate-500">No program flow added.</p>
            @endif
        </section>

        <section class="grid gap-3 md:grid-cols-2">
            @php
                $documentEntries = [
                    [
                        'label' => 'Communication Letter (PDF)',
                        'uploaded' => filled($activityRequest->communication_letter),
                        'statusText' => filled($activityRequest->communication_letter) ? 'Uploaded' : 'Not uploaded yet',
                        'statusClass' => filled($activityRequest->communication_letter) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'viewUrl' => filled($activityRequest->communication_letter) ? route('admin.file.view', [$activityRequest->id, 'communication']) : null,
                        'title' => 'Communication Letter – ' . $activityRequest->title,
                    ],
                    [
                        'label' => 'Narrative Report (PDF)',
                        'uploaded' => $report && filled($report->narrative_report),
                        'statusText' => $report && filled($report->narrative_report) ? 'Uploaded' : 'Not uploaded yet',
                        'statusClass' => $report && filled($report->narrative_report) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'viewUrl' => $report && filled($report->narrative_report) ? route('admin.file.view', [$activityRequest->id, 'narrative']) : null,
                        'title' => 'Narrative Report – ' . $activityRequest->title,
                    ],
                    [
                        'label' => 'Attendance Sheet (PDF)',
                        'uploaded' => filled($report?->attendance_sheet_path),
                        'statusText' => filled($report?->attendance_sheet_path) ? 'Uploaded' : 'Not uploaded yet',
                        'statusClass' => filled($report?->attendance_sheet_path) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'viewUrl' => filled($report?->attendance_sheet_path) ? route('admin.reports.evidence', [$report, 'attendance']) : null,
                        'title' => 'Attendance Sheet – ' . $activityRequest->title,
                    ],
                    [
                        'label' => 'Photos',
                        'uploaded' => $report?->photos->isNotEmpty(),
                        'statusText' => $report?->photos->isNotEmpty() ? 'Uploaded' : 'Not uploaded yet',
                        'statusClass' => $report?->photos->isNotEmpty() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'viewUrl' => $report?->photos->isNotEmpty() ? route('admin.reports.evidence', [$report, 'photo-' . $report->photos->first()->id]) : null,
                        'title' => 'Activity Photo – ' . $activityRequest->title,
                    ],
                ];
            @endphp

            @foreach($documentEntries as $document)
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l4.25 4.25v12.25H7A2.25 2.25 0 0 1 4.75 18V6A2.25 2.25 0 0 1 7 3.75Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 4v4h4M8.5 12h5M8.5 15.5h7" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-bold text-slate-900">{{ $document['label'] }}</h2>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-[10px] font-semibold {{ $document['statusClass'] }}">{{ $document['statusText'] }}</span>
                                </div>
                            </div>
                        </div>
                        @if($document['viewUrl'])
                            <a href="{{ $document['viewUrl'] }}" data-file-viewer data-title="{{ $document['title'] }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        @if($report)
            <section class="rounded-lg border border-slate-200 bg-white p-4" x-data="{ revisionOpen: {{ $errors->has('feedback') ? 'true' : 'false' }}, feedbackLength: {{ strlen(old('feedback', '')) }} }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Report review</h2>
                        @php
                            $reviewBadgeClass = match ($report->reviewStatusLabel()) {
                                'For Review' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                'Needs Revision' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                'Approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                default => 'bg-slate-100 text-slate-700 ring-slate-200',
                            };
                        @endphp
                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $reviewBadgeClass }}">{{ $report->reviewStatusLabel() }}</span>
                        @if($report->reviewed_at)
                            <p class="mt-1 text-xs text-slate-500">Reviewed by {{ $report->reviewer?->name ?? 'Admin' }} on {{ $report->reviewed_at->format('M j, Y g:i A') }}</p>
                        @endif
                        @if($report->status === 'needs_revision' && filled($report->feedback))
                            <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"><span class="font-semibold">Last revision request:</span> {{ $report->feedback }} <span class="text-xs">({{ $report->reviewed_at?->format('M d, Y') ?? '—' }})</span></div>
                        @endif
                    </div>
                    @if($report->status === 'approved')
                        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">Approved by {{ $report->reviewer?->name ?? 'Admin' }} on {{ $report->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</div>
                    @endif
                </div>

                @if(in_array($report->status, ['pending', 'needs_revision'], true))
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <form method="POST" action="{{ route('admin.reports.approve', $report) }}" data-confirm data-confirm-title="Approve narrative report?" data-confirm-message="The activity will be marked Completed." data-confirm-label="Approve" data-confirm-variant="primary">@csrf<button type="submit" class="min-h-10 w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 sm:w-auto">Approve</button></form>
                            <button type="button" @click="revisionOpen = !revisionOpen" :aria-expanded="revisionOpen.toString()" aria-controls="revision-panel-{{ $report->id }}" class="min-h-10 w-full rounded-md border border-amber-600 bg-white px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50 sm:w-auto">Return for revision</button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Returning a report sends your feedback to the organization so they can fix and resubmit.</p>
                        <div id="revision-panel-{{ $report->id }}" x-show="revisionOpen" x-cloak x-transition class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <form method="POST" action="{{ route('admin.reports.request-revision', $report) }}" class="space-y-3" @submit="const button = $el.querySelector('[data-revision-submit]'); button.disabled = true; button.textContent = 'Sending…'">@csrf
                                <label for="feedback" class="block text-sm font-semibold text-slate-800">Reason for revision (sent to the organization)</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['Incomplete content', 'Needs correction', 'Missing photos or attachments', 'Wrong format'] as $reason)
                                        <button type="button" @click="const separator = $refs.feedback.value.trim() ? '\n' : ''; $refs.feedback.value += separator + '{{ $reason }}'; feedbackLength = $refs.feedback.value.length; $refs.feedback.dispatchEvent(new Event('input', { bubbles: true }));" class="min-h-10 rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100">{{ $reason }}</button>
                                    @endforeach
                                </div>
                                <textarea id="feedback" x-ref="feedback" name="feedback" required minlength="10" maxlength="1000" rows="4" @input="feedbackLength = $event.target.value.length" class="w-full rounded-md border-slate-300 text-sm" placeholder="Explain what needs to be revised">{{ old('feedback') }}</textarea>
                                <div class="flex items-start justify-between gap-3">
                                    @error('feedback')<p class="text-xs text-rose-700" role="alert">{{ $message }}</p>@else<p></p>@enderror
                                    <span class="shrink-0 text-xs tabular-nums text-slate-500"><span x-text="feedbackLength">{{ strlen(old('feedback', '')) }}</span> / 1000</span>
                                </div>
                                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                    <button type="button" @click="revisionOpen = false" class="min-h-10 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                    <button type="submit" data-revision-submit class="min-h-10 rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Return to organization</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @elseif($report->status === 'approved')
                    <details class="mt-4 border-t border-slate-200 pt-4" @if($errors->has('feedback')) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-amber-800">Reopen for revision</summary>
                        <form method="POST" action="{{ route('admin.reports.request-revision', $report) }}" class="mt-3 space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-4" @submit="const button = $el.querySelector('[data-revision-submit]'); button.disabled = true; button.textContent = 'Sending…'">@csrf
                            <label for="reopen-feedback" class="block text-sm font-semibold text-slate-800">Reason for revision (sent to the organization)</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Incomplete content', 'Needs correction', 'Missing photos or attachments', 'Wrong format'] as $reason)
                                    <button type="button" @click="const separator = $refs.reopenFeedback.value.trim() ? '\n' : ''; $refs.reopenFeedback.value += separator + '{{ $reason }}'; feedbackLength = $refs.reopenFeedback.value.length; $refs.reopenFeedback.dispatchEvent(new Event('input', { bubbles: true }));" class="min-h-10 rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100">{{ $reason }}</button>
                                @endforeach
                            </div>
                            <textarea id="reopen-feedback" x-ref="reopenFeedback" name="feedback" required minlength="10" maxlength="1000" rows="4" @input="feedbackLength = $event.target.value.length" class="w-full rounded-md border-slate-300 text-sm" placeholder="Explain what needs to be revised">{{ old('feedback') }}</textarea>
                            <div class="flex items-start justify-between gap-3">
                                @error('feedback')<p class="text-xs text-rose-700" role="alert">{{ $message }}</p>@else<p></p>@enderror
                                <span class="shrink-0 text-xs tabular-nums text-slate-500"><span x-text="feedbackLength">{{ strlen(old('feedback', '')) }}</span> / 1000</span>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" data-revision-submit class="min-h-10 rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Return to organization</button>
                            </div>
                        </form>
                    </details>
                @endif
            </section>
        @endif

        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-bold text-slate-900">Monitoring remarks</h2>
            @if($monitoringResult)
                @php
                    $monitoringAssessmentLabel = $assessmentLabels[$monitoringResult->compliance_status] ?? ucfirst($monitoringResult->compliance_status);
                @endphp
                <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center gap-2 text-slate-700">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-200 text-slate-600">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="10" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Assessment</span>
                    </div>
                    <div class="mt-3">
                        <x-status-pill :status="$monitoringAssessmentLabel" />
                    </div>
                    <p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ $monitoringResult->compliance_notes ?: 'No remark recorded.' }}</p>
                    <p class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                        <svg aria-hidden="true" class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="10" cy="7" r="4"/>
                        </svg>
                        <span>Recorded by <span class="font-semibold text-slate-700">{{ $monitoringResult->admin?->name ?? 'Admin' }}</span> on {{ $monitoringResult->recorded_at?->format('M j, Y g:i A') ?? '—' }}</span>
                    </p>
                </div>
            @else
                <p class="mt-2 text-sm text-slate-500">No monitoring assessment recorded.</p>
            @endif
            <form method="POST" action="{{ route('admin.monitoring.record', $plannedActivity?->id ?? $activityRequest->gpoa_activity_id) }}" class="mt-4 grid gap-3 border-t border-slate-200 pt-4 sm:grid-cols-[220px_1fr_auto]">@csrf
                <label class="sr-only" for="compliance_status">Assessment</label>
                <select id="compliance_status" name="compliance_status" required class="rounded-md border-slate-300 text-sm">
                    <option value="">Assessment</option>
                    @foreach($assessmentLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('compliance_status', $monitoringResult?->compliance_status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div><label class="sr-only" for="compliance_notes">Remark</label><textarea id="compliance_notes" name="compliance_notes" maxlength="1000" rows="2" class="w-full rounded-md border-slate-300 text-sm" placeholder="Optional remark">{{ old('compliance_notes', $monitoringResult?->compliance_notes) }}</textarea>@error('compliance_status')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror @error('compliance_notes')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror</div>
                <button type="submit" class="self-start rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Save assessment</button>
            </form>
        </section>
    </main>
</x-app-layout>
