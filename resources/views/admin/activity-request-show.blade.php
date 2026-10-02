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
                <h1 class="mt-1 break-words text-xl font-bold text-slate-900">{{ $activityRequest->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $activityRequest->user?->org_name ?? $activityRequest->user?->name ?? 'Organization' }} · {{ $activityRequest->gpoa?->college ?? '—' }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <x-status-pill :status="$plannedActivity?->monitoringStatus()['status'] ?? 'Pending'" />
                    @if($report)<x-status-pill :status="$reportStatus" />@endif
                </div>
            </div>
            <a href="{{ $backUrl }}" class="shrink-0 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Activity Monitoring</a>
        </header>

        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-bold text-slate-900">Activity details</h2>
            <dl class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    'Organization' => $activityRequest->user?->org_name ?? $activityRequest->user?->name ?? '—',
                    'College' => $activityRequest->gpoa?->college ?? '—',
                    'Category' => $activityRequest->category ?? '—',
                    'Venue' => $activityRequest->venue ?? '—',
                    'Date' => $activityRequest->date?->format('M j, Y') ?? '—',
                    'End date' => $activityRequest->end_date?->format('M j, Y') ?? '—',
                    'Start / end time' => trim(($activityRequest->start_time ? substr((string) $activityRequest->start_time, 0, 5) : '—') . ' – ' . ($activityRequest->end_time ? substr((string) $activityRequest->end_time, 0, 5) : '—')),
                    'Status' => $plannedActivity?->monitoringStatus()['status'] ?? 'Pending',
                    'Term / School year' => ($activityRequest->gpoa?->term ?? '—') . ' / ' . ($activityRequest->gpoa?->school_year ?? '—'),
                    'Objectives' => $activityRequest->objectives ?? '—',
                    'Expected outcome' => $activityRequest->expected_outcome ?? '—',
                    'Target participants' => $activityRequest->target_participants ?? '—',
                    'Person in charge' => $activityRequest->person_in_charge ?? '—',
                    'Estimated budget' => $activityRequest->estimated_budget !== null ? number_format((float) $activityRequest->estimated_budget, 2) : '—',
                ] as $label => $value)
                    <div class="min-w-0 rounded border border-slate-200 bg-slate-50 px-3 py-2">
                        <dt class="text-[10px] font-semibold uppercase text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 break-words whitespace-pre-wrap text-sm text-slate-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
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
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-bold text-slate-900">Communication letter</h2>
                @if(filled($activityRequest->communication_letter))
                    <a href="{{ route('admin.file.view', [$activityRequest->id, 'communication']) }}" data-file-viewer data-title="Communication Letter – {{ $activityRequest->title }}" class="mt-2 inline-block text-sm font-semibold text-sky-700 underline">{{ basename($activityRequest->communication_letter) }}</a>
                @else
                    <p class="mt-2 rounded bg-slate-100 px-3 py-2 text-sm text-slate-500">Not uploaded yet</p>
                @endif
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-bold text-slate-900">Narrative report</h2>
                @if($report && filled($report->narrative_report))
                    <a href="{{ route('admin.file.view', [$activityRequest->id, 'narrative']) }}" data-file-viewer data-title="Narrative Report – {{ $activityRequest->title }}" class="mt-2 inline-block text-sm font-semibold text-sky-700 underline">{{ basename($report->narrative_report) }}</a>
                @elseif(data_get($report?->narrative_content, 'body'))
                    <div class="mt-2 max-h-48 overflow-y-auto whitespace-pre-wrap rounded bg-slate-50 p-3 text-sm text-slate-700">{{ data_get($report->narrative_content, 'body') }}</div>
                @else
                    <p class="mt-2 rounded bg-slate-100 px-3 py-2 text-sm text-slate-500">Not uploaded yet</p>
                @endif
                @if($report?->status === 'approved')
                    <p class="mt-3 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Approved on {{ $report->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</p>
                @endif
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-bold text-slate-900">Attendance sheet</h2>
                @if(filled($report?->attendance_sheet_path))
                    <a href="{{ route('admin.reports.evidence', [$report, 'attendance']) }}" data-file-viewer data-title="Attendance Sheet – {{ $activityRequest->title }}" class="mt-2 inline-block text-sm font-semibold text-sky-700 underline">{{ basename($report->attendance_sheet_path) }}</a>
                @else
                    <p class="mt-2 rounded bg-slate-100 px-3 py-2 text-sm text-slate-500">Not uploaded yet</p>
                @endif
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-bold text-slate-900">Photos</h2>
                @if($report?->photos->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($report->photos as $photo)
                            <a href="{{ route('admin.reports.evidence', [$report, 'photo-' . $photo->id]) }}" data-file-viewer data-title="Activity Photo {{ $loop->iteration }} – {{ $activityRequest->title }}" class="rounded border border-slate-200 px-3 py-1.5 text-sm font-semibold text-sky-700 hover:bg-slate-50">Photo {{ $loop->iteration }}</a>
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 rounded bg-slate-100 px-3 py-2 text-sm text-slate-500">Not uploaded yet</p>
                @endif
            </div>
        </section>

        @if($report)
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Report review</h2>
                        <p class="mt-2 text-sm text-slate-700">Status: <span class="font-semibold">{{ $reportStatus }}</span></p>
                        @if($report->reviewed_at)
                            <p class="mt-1 text-xs text-slate-500">Reviewed by {{ $report->reviewer?->name ?? 'Admin' }} on {{ $report->reviewed_at->format('M j, Y g:i A') }}</p>
                        @endif
                        @if($report->status === 'needs_revision' && filled($report->feedback))
                            <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"><span class="font-semibold">Previous feedback:</span> {{ $report->feedback }}</div>
                        @endif
                    </div>
                    @if($report->status === 'approved')
                        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">Approved by {{ $report->reviewer?->name ?? 'Admin' }} on {{ $report->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</div>
                    @endif
                </div>

                @if(in_array($report->status, ['pending', 'needs_revision'], true))
                    <div class="mt-4 flex flex-wrap items-start gap-3 border-t border-slate-200 pt-4">
                        <form method="POST" action="{{ route('admin.reports.approve', $report) }}">@csrf<button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Approve</button></form>
                        <form method="POST" action="{{ route('admin.reports.request-revision', $report) }}" class="min-w-0 flex-1 space-y-2 sm:max-w-xl">@csrf
                            <label for="feedback" class="block text-sm font-semibold text-slate-700">Request revision</label>
                            <textarea id="feedback" name="feedback" required maxlength="1000" rows="3" class="w-full rounded-md border-slate-300 text-sm" placeholder="Explain what needs to be revised">{{ old('feedback') }}</textarea>
                            @error('feedback')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                            <button type="submit" class="rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Send revision request</button>
                        </form>
                    </div>
                @elseif($report->status === 'approved')
                    <details class="mt-4 border-t border-slate-200 pt-4">
                        <summary class="cursor-pointer text-sm font-semibold text-amber-800">Reopen / request revision</summary>
                        <form method="POST" action="{{ route('admin.reports.request-revision', $report) }}" class="mt-3 space-y-2 sm:max-w-xl">@csrf
                            <label for="reopen-feedback" class="block text-sm font-semibold text-slate-700">Required feedback</label>
                            <textarea id="reopen-feedback" name="feedback" required maxlength="1000" rows="3" class="w-full rounded-md border-slate-300 text-sm" placeholder="Explain why the report needs revision">{{ old('feedback') }}</textarea>
                            @error('feedback')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                            <button type="submit" class="rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Request revision</button>
                        </form>
                    </details>
                @endif
            </section>
        @endif

        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-bold text-slate-900">Monitoring remarks</h2>
            @if($monitoringResult)
                <div class="mt-3 rounded-md bg-slate-50 p-3">
                    <p class="text-sm font-semibold text-slate-800">{{ $assessmentLabels[$monitoringResult->compliance_status] ?? ucfirst($monitoringResult->compliance_status) }}</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $monitoringResult->compliance_notes ?: 'No remark recorded.' }}</p>
                    <p class="mt-2 text-xs text-slate-500">Recorded by {{ $monitoringResult->admin?->name ?? 'Admin' }} on {{ $monitoringResult->recorded_at?->format('M j, Y g:i A') ?? '—' }}</p>
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
