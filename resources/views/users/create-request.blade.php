<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <main class="page-wrapper">
        <div class="gpoa-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow">Activity Request</p>
                    <h1>Request an Activity</h1>
                    <p class="page-description">Submit a detailed activity request under your approved GPOA.</p>
                </div>
            </div>
            <form action="{{ route('activity-requests.store') }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="requestForm">
                @csrf

                {{-- Organization Information (read-only reference) --}}
                @php
                    $prefill = $prefill ?? [];
                    $fromPlannedActivity = $fromPlannedActivity ?? false;
                    $programFlows = $programFlows ?? collect();
                    $org = $organization ?? auth()->user()->organization ?? null;
                    $limitObj = $activityLimitTemplate ?? $activityLimit ?? null;
                    $usedCount = $limitObj->used ?? $limitObj->used_count ?? $limitObj->usedActivities ?? null;
                    $limitCount = $limitObj->limit ?? $limitObj->max ?? $limitObj->allowed ?? null;
                    $atCap = ($usedCount !== null && $limitCount !== null && $usedCount >= $limitCount);
                @endphp

                <div class="org-info-block" aria-label="Organization Information">
                    <div class="org-info-title">Organization Information</div>
                    <div class="org-info-grid">
                        <div class="cell label">Organization Name</div>
                        <div class="cell value">{{ $org?->org_name ?? $org?->name ?? '—' }}
                            @php
                                $status = strtolower($gpoa?->status ?? 'not submitted');
                                $statusClass = match($status) {
                                    'approved' => 'badge-approved',
                                    'pending' => 'badge-pending',
                                    default => 'badge-not-submitted',
                                };
                                $statusLabel = $gpoa?->status ? ucfirst($gpoa->status) : 'Not Submitted';
                            @endphp
                            <span class="gpoa-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        <div class="cell label">Organization Type</div>
                        <div class="cell value"><span class="value-badge">{{ $org?->type ?? $org?->organization_classification?->name ?? '—' }}</span></div>

                        <div class="cell label">Executive Secretary</div>
                        <div class="cell value">{{ auth()->user()->name ?? '—' }}</div>

                        <div class="cell label">Allowed Activities</div>
                        <div class="cell value">@if($usedCount !== null && $limitCount !== null)
                                <span @class(['allowed-warning' => $atCap])>{{ $usedCount }} / {{ $limitCount }}</span>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-sm text-gray-500">Planning Activity Under</p>
                    <div class="inline-flex flex-wrap items-center gap-3 bg-slate-100 rounded-2xl px-4 py-3">
                        <span class="font-semibold text-slate-800">{{ $gpoa->college }} — {{ $gpoa->term }} / SY {{ $gpoa->school_year }}</span>
                        @if(!$fromPlannedActivity && $availableGpoas->count() > 1)
                            <form id="switchGpoaForm" method="GET" action="{{ route('activity-requests.create') }}">
                                <label class="sr-only">Select GPOA</label>
                                <select name="gpoa" onchange="document.getElementById('switchGpoaForm').submit()"
                                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                                    @foreach($availableGpoas as $available)
                                        <option value="{{ $available->id }}" {{ $selectedGpoaId == $available->id ? 'selected' : '' }}>
                                            {{ $available->college }} — {{ $available->term }} / SY {{ $available->school_year }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>
                </div>

                <section class="form-section">
                    <div class="section-heading">
                        <div>
                            <h2 class="section-title">Plan the Activity Request</h2>
                            <p class="section-description">Enter the activity details you want to request under the selected GPOA.</p>
                        </div>
                    </div>

                    <input type="hidden" name="gpoa_id" value="{{ old('gpoa_id', $gpoa->id) }}">

                    <div class="form-group mt-6">
                        <label for="gpoa_activity_id">Planned activity *</label>
                        @if($fromPlannedActivity)
                            <input id="selectedPlannedActivityId" type="hidden" name="gpoa_activity_id" value="{{ old('gpoa_activity_id', $gpoaActivity->id) }}">
                            <div class="flex flex-wrap items-center gap-3">
                                <span id="plannedActivityChip" class="rounded-full border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-900">{{ $gpoaActivity->title }} <span class="font-normal text-sky-700">· {{ $gpoa->college }} / {{ $gpoa->term }} / SY {{ $gpoa->school_year }}</span></span>
                                <button type="button" id="chooseDifferentActivity" class="text-sm font-semibold text-sky-700 underline">Choose a different activity</button>
                            </div>
                        @endif
                        <select id="gpoa_activity_id" name="gpoa_activity_id" @if($fromPlannedActivity) class="mt-3 hidden" disabled @endif>
                            <option value="">Select a planned activity</option>
                            @foreach($gpoa->activities as $activity)
                                <option value="{{ $activity->id }}" {{ old('gpoa_activity_id', $selectedActivityId ?? null) == $activity->id ? 'selected' : '' }}>
                                    {{ $activity->title }} — {{ $activity->date ? $activity->date->format('M d, Y') : 'No date' }} @ {{ $activity->venue ?? 'Venue not set' }}
                                </option>
                            @endforeach
                        </select>
                        @error('gpoa_activity_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    @if($fromPlannedActivity)
                        <div id="gpoaPrefillHint" class="mb-6 flex items-start justify-between gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900" role="status">
                            <p>Prefilled from your GPOA. Review and edit anything that changed.</p>
                            <button type="button" id="dismissPrefillHint" aria-label="Dismiss prefill hint" class="shrink-0 font-semibold text-sky-800">×</button>
                        </div>
                        <div class="mb-6">
                            <button type="button" id="resetGpoaPrefill" class="rounded-md border border-amber-300 bg-white px-3 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-50">Reset to GPOA values</button>
                        </div>
                    @endif

                    <div class="mb-6 bg-slate-50 border border-slate-200 rounded-2xl p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Selected GPOA</p>
                        <p class="mt-2 text-sm text-slate-900 font-semibold">{{ $gpoa->college }} — {{ $gpoa->term }} / SY {{ $gpoa->school_year }}</p>
                        <p class="text-sm text-slate-600 mt-1">Status: {{ ucfirst($gpoa->status) }}</p>
                    </div>

                    @if(count($activityLimits))
                        <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900">
                            <p class="font-semibold">Category request limits</p>
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach($activityLimits as $category => $limit)
                                    <li>{{ $category }}: {{ $limit }} requests max</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="form-group">
                            <label for="title">Activity Title *</label>
                            <input id="title" type="text" name="title" value="{{ old('title', $prefill['title'] ?? '') }}" required>
                            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select category</option>
                                @include('partials.category-options', ['prefillCategory' => $prefill['category'] ?? ''])
                            </select>
                            @error('category')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-1 mt-6">
                        @include('partials.sdg-checkboxes', ['gpoa' => false, 'oldNums' => collect((array) old('sdgs', $prefill['sdgs'] ?? []))->map(fn ($value) => (string) $value)->all()])
                    </div>

                        <div class="form-group mt-6">
                            <label for="objectives">Objectives *</label>
                            <textarea id="objectives" name="objectives" rows="4" required>{{ old('objectives', $prefill['objectives'] ?? '') }}</textarea>
                        @error('objectives')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group mt-6">
                        <label for="expected_outcome">Expected Outcome *</label>
                        <textarea id="expected_outcome" name="expected_outcome" rows="4" required>{{ old('expected_outcome', $prefill['expected_outcome'] ?? '') }}</textarea>
                        @error('expected_outcome')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group mt-6">
                        <label for="plan_key_strategy">Plan / Key Strategy *</label>
                        <textarea id="plan_key_strategy" name="plan_key_strategy" rows="4" required>{{ old('plan_key_strategy', $prefill['plan_key_strategy'] ?? '') }}</textarea>
                        @error('plan_key_strategy')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-6 md:grid-cols-1 mt-6">
                        <div class="form-group">
                            <label for="date">Date *</label>
                            <input id="date" type="date" name="date" value="{{ old('date', $prefill['date'] ?? '') }}" min="{{ old('date') && old('date') < today()->toDateString() ? old('date') : (!empty($prefill['date']) && $prefill['date'] < today()->toDateString() ? $prefill['date'] : today()->toDateString()) }}" required>
                            <p id="plannedActivityPastDateWarning" class="mt-1 {{ old('date', $prefill['date'] ?? '') && old('date', $prefill['date'] ?? '') < today()->toDateString() ? '' : 'hidden' }} text-xs text-amber-700">This planned activity date is in the past. Choose today or a future date to submit the request.</p>
                            @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date (optional, for multi-day activities)</label>
                            <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $prefill['end_date'] ?? '') }}">
                            @error('end_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input id="start_time" type="time" name="start_time" value="{{ old('start_time', $prefill['start_time'] ?? '') }}">
                            @error('start_time')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time (optional)</label>
                            <input id="end_time" type="time" name="end_time" value="{{ old('end_time', $prefill['end_time'] ?? '') }}">
                            @error('end_time')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <section class="mt-6 min-w-0 max-w-full rounded-xl border border-slate-200 bg-slate-50 p-4"
                             x-data="{
                                rows: @js(old('program_flows', $programFlows ?: [['time' => '', 'flow' => '', 'person_in_charge' => '']])),
                                normalizeFlows(flows) {
                                    const normalizedRows = (flows || []).map(flow => ({
                                        time: flow.time ?? '',
                                        flow: flow.flow ?? '',
                                        person_in_charge: flow.person_in_charge ?? '',
                                    }));
                                    return normalizedRows.length ? normalizedRows : [{ time: '', flow: '', person_in_charge: '' }];
                                },
                                init() {
                                    window.addEventListener('reset-gpoa-prefill', event => { this.rows = this.normalizeFlows(event.detail.program_flows); });
                                    window.addEventListener('planned-activity-changed', event => { this.rows = this.normalizeFlows(event.detail.program_flows); });
                                },
                                addRow() {
                                    this.rows.push({ time: '', flow: '', person_in_charge: '' });
                                },
                                removeRow(index) {
                                    this.rows.splice(index, 1);
                                }
                             }">
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-slate-800">Program Flow</h3>
                            <p class="mt-1 text-xs text-slate-500">Optional schedule for the Activity Date selected above.</p>
                        </div>
                        <div class="max-w-full min-w-0 overflow-x-auto">
                            <table class="w-full min-w-[640px] text-sm">
                                <thead class="text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-2 py-2 text-left">Time</th>
                                        <th class="px-2 py-2 text-left">Flow / Program</th>
                                        <th class="px-2 py-2 text-left">Person in Charge</th>
                                        <th class="w-12 px-2 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <template x-for="(row, index) in rows" :key="index">
                                        <tr>
                                            <td class="px-2 py-2 align-top">
                                                <input type="text" :name="`program_flows[${index}][time]`" x-model="row.time"
                                                       placeholder="e.g. 8:00 AM" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                            </td>
                                            <td class="px-2 py-2 align-top">
                                                <input type="text" :name="`program_flows[${index}][flow]`" x-model="row.flow"
                                                       placeholder="e.g. Opening Remarks" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                            </td>
                                            <td class="px-2 py-2 align-top">
                                                <input type="text" :name="`program_flows[${index}][person_in_charge]`" x-model="row.person_in_charge"
                                                       class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                            </td>
                                            <td class="px-2 py-2 align-top">
                                                <button type="button" @click="removeRow(index)" aria-label="Remove program flow row"
                                                        title="Remove row" class="rounded p-2 text-red-600 hover:bg-red-50">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 4v6m4-6v6" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        @foreach($errors->getMessages() as $field => $messages)
                            @if(str_starts_with($field, 'program_flows.'))
                                <p class="mt-2 text-xs text-red-600">{{ $messages[0] }}</p>
                            @endif
                        @endforeach
                        <button type="button" @click="addRow()"
                                class="mt-3 inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            Add row
                        </button>
                    </section>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="venue">Venue *</label>
                            <input id="venue" type="text" name="venue" value="{{ old('venue', $prefill['venue'] ?? '') }}" required>
                            @error('venue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="target_participants">Target Participants *</label>
                            <input id="target_participants" type="text" name="target_participants" value="{{ old('target_participants', $prefill['target_participants'] ?? '') }}" required>
                            @error('target_participants')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="person_in_charge">Person in Charge *</label>
                            <input id="person_in_charge" type="text" name="person_in_charge" value="{{ old('person_in_charge', $prefill['person_in_charge'] ?? '') }}" required>
                            @error('person_in_charge')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="facilities_materials">Facilities / Materials *</label>
                            <input id="facilities_materials" type="text" name="facilities_materials" value="{{ old('facilities_materials', $prefill['facilities_materials'] ?? '') }}" required>
                            @error('facilities_materials')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="estimated_budget">Estimated Budget *</label>
                            <input id="estimated_budget" type="number" step="0.01" name="estimated_budget" value="{{ old('estimated_budget', $prefill['estimated_budget'] ?? '') }}" required>
                            @error('estimated_budget')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="source_of_funds">Source of Funds *</label>
                            <select id="source_of_funds" name="source_of_funds" required>
                                <option value="">Select source of funds</option>
                                <option value="Organization Funds" {{ old('source_of_funds', $prefill['source_of_funds'] ?? '') === 'Organization Funds' ? 'selected' : '' }}>Organization Funds</option>
                                <option value="Student Council Funds" {{ old('source_of_funds', $prefill['source_of_funds'] ?? '') === 'Student Council Funds' ? 'selected' : '' }}>Student Council Funds</option>
                                <option value="School-Generated Funds / MOOE" {{ old('source_of_funds', $prefill['source_of_funds'] ?? '') === 'School-Generated Funds / MOOE' ? 'selected' : '' }}>School-Generated Funds / MOOE</option>
                                <option value="Sponsorship / Donations" {{ old('source_of_funds', $prefill['source_of_funds'] ?? '') === 'Sponsorship / Donations' ? 'selected' : '' }}>Sponsorship / Donations</option>
                                <option value="UniFast" {{ old('source_of_funds', $prefill['source_of_funds'] ?? '') === 'UniFast' ? 'selected' : '' }}>UniFast</option>
                                <option value="Cash on Hand" {{ old('source_of_funds', $prefill['source_of_funds'] ?? '') === 'Cash on Hand' ? 'selected' : '' }}>Cash on Hand</option>
                            </select>
                            @error('source_of_funds')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                </section>

                <div class="flex flex-col items-stretch gap-3 md:flex-row md:items-center">
                    <a href="{{ route('activity-requests.index') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-gray-200 px-5 py-3 text-sm font-semibold text-gray-800 transition hover:bg-gray-300 md:w-auto">
                        Cancel
                    </a>
                    <button type="submit" class="btn-secondary">Submit Activity Request</button>
                </div>
            </form>
        </div>
    </main>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const serverPrefill = @js($prefill);
    const serverProgramFlows = @js($programFlows);
    const plannedActivities = @js($gpoa->activities->mapWithKeys(fn ($activity) => [$activity->id => [
        'title' => $activity->title,
        'category' => $activity->category,
        'sdgs' => $activity->sdgs ?? [],
        'date' => $activity->date ? ($activity->date_is_month_only ? $activity->date->format('Y-m-01') : $activity->date->toDateString()) : '',
        'date_is_month_only' => (bool) $activity->date_is_month_only,
        'end_date' => $activity->end_date?->toDateString() ?? '',
        'start_time' => $activity->start_time ? substr((string) $activity->start_time, 0, 5) : '',
        'end_time' => $activity->end_time ? substr((string) $activity->end_time, 0, 5) : '',
        'venue' => $activity->venue,
        'objectives' => $activity->objectives,
        'expected_outcome' => $activity->expected_outcome,
        'plan_key_strategy' => $activity->plan_key_strategy,
        'target_participants' => $activity->target_participants,
        'person_in_charge' => $activity->person_in_charge,
        'facilities_materials' => $activity->facilities_materials,
        'estimated_budget' => $activity->estimated_budget,
        'source_of_funds' => $activity->source_of_funds,
        'program_flows' => $activity->getAttribute('program_flows') ?? [],
    ]])->all());
    const plannedActivitySelect = document.getElementById('gpoa_activity_id');
    const sdgCheckboxContainer = document.getElementById('sdgCheckboxes');
    const sdgSummary = document.getElementById('sdgSummary');
    const sdgColors = @json(config('sdg'));
    const sdgValidationError = document.getElementById('sdgValidationError');
    const requestForm = document.getElementById('requestForm');
    const hasOldInput = @json(session()->hasOldInput());
    const dateInput = document.getElementById('date');
    const endDateInput = document.getElementById('end_date');
    const targetParticipantsInput = document.getElementById('target_participants');
    const estimatedBudgetInput = document.getElementById('estimated_budget');
    const plannedDateWarning = document.getElementById('plannedActivityPastDateWarning');
    const resetPrefillButton = document.getElementById('resetGpoaPrefill');
    const originalActivityId = @js($gpoaActivity->id ?? null);
    const MAX_SDGS = 8;
    const MIN_SDGS = 1;

    function normalized(value) {
        return String(value ?? '').trim().toLocaleLowerCase();
    }

    function selectValue(select, value, field) {
        const wanted = normalized(value);
        if (!wanted) {
            select.value = '';
            return;
        }

        const option = Array.from(select.options).find(candidate =>
            normalized(candidate.value) === wanted || normalized(candidate.textContent) === wanted
        );
        if (option) {
            select.value = option.value;
            return;
        }

        select.value = '';
        console.warn(`Could not match planned GPOA value for ${field}:`, value);
    }

    function setPastDateState(value) {
        if (!dateInput) return;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const plannedDate = value ? new Date(`${value}T00:00:00`) : null;
        const isPast = plannedDate && plannedDate < today;
        plannedDateWarning?.classList.toggle('hidden', !isPast);
        const localToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        dateInput.min = isPast ? value : localToday;
    }

    function updateChangedFields() {
        const fields = ['title', 'category', 'objectives', 'expected_outcome', 'plan_key_strategy', 'date', 'end_date', 'start_time', 'end_time', 'venue', 'target_participants', 'person_in_charge', 'facilities_materials', 'estimated_budget', 'source_of_funds'];
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (!element || !(field in serverPrefill)) return;
            const expected = normalized(serverPrefill[field]);
            const current = normalized(element.value);
            element.classList.toggle('border-amber-500', current !== expected);
            element.classList.toggle('border-l-4', current !== expected);
        });

        const selectedSdgs = Array.from(sdgCheckboxContainer?.querySelectorAll('input[type="checkbox"]:checked') ?? []).map(input => input.value).sort();
        const expectedSdgs = (serverPrefill.sdgs ?? []).map(String).sort();
        sdgCheckboxContainer?.classList.toggle('border-l-4', JSON.stringify(selectedSdgs) !== JSON.stringify(expectedSdgs));
        sdgCheckboxContainer?.classList.toggle('border-amber-500', JSON.stringify(selectedSdgs) !== JSON.stringify(expectedSdgs));
    }

    function prefillFromPlannedActivity(onlyEmpty = false) {
        const activity = plannedActivities[plannedActivitySelect?.value];
        const pastDateWarning = document.getElementById('plannedActivityPastDateWarning');
        if (!activity) {
            pastDateWarning?.classList.add('hidden');
            return;
        }

        ['title', 'category', 'objectives', 'expected_outcome', 'plan_key_strategy', 'date',
            'end_date', 'start_time', 'end_time', 'venue', 'target_participants', 'person_in_charge', 'facilities_materials',
            'estimated_budget', 'source_of_funds'].forEach(field => {
            const input = document.getElementById(field);
            if (input && (!onlyEmpty || !input.value)) {
                const value = activity[field] ?? '';
                if (input instanceof HTMLSelectElement) selectValue(input, value, field);
                else input.value = value;
            }
        });

        if (!onlyEmpty || !hasOldInput) {
            sdgCheckboxContainer.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = (activity.sdgs || []).map(String).includes(checkbox.value);
            });
            updateSdgSummary();
        }

        setPastDateState(activity.date);
        window.dispatchEvent(new CustomEvent('planned-activity-changed', { detail: { program_flows: activity.program_flows || [] } }));
        updateChangedFields();
    }

    if(!sdgCheckboxContainer) return;

    plannedActivitySelect?.addEventListener('change', () => {
        const hiddenActivityId = document.getElementById('selectedPlannedActivityId');
        if (hiddenActivityId) {
            hiddenActivityId.disabled = true;
            plannedActivitySelect.disabled = false;
        }
        prefillFromPlannedActivity(false);
    });

    document.getElementById('chooseDifferentActivity')?.addEventListener('click', () => {
        const hiddenActivityId = document.getElementById('selectedPlannedActivityId');
        if (hiddenActivityId) hiddenActivityId.disabled = false;
        if (plannedActivitySelect) {
            plannedActivitySelect.disabled = false;
            plannedActivitySelect.classList.remove('hidden');
            plannedActivitySelect.focus();
        }
    });

    document.getElementById('dismissPrefillHint')?.addEventListener('click', () => {
        document.getElementById('gpoaPrefillHint')?.remove();
    });

    resetPrefillButton?.addEventListener('click', () => {
        Object.entries(serverPrefill).forEach(([field, value]) => {
            const element = document.getElementById(field);
            if (!element) return;
            if (element instanceof HTMLSelectElement) selectValue(element, value, field);
            else element.value = value ?? '';
        });
        sdgCheckboxContainer.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = (serverPrefill.sdgs ?? []).map(String).includes(checkbox.value);
        });
        if (plannedActivitySelect && originalActivityId) plannedActivitySelect.value = originalActivityId;
        const hiddenActivityId = document.getElementById('selectedPlannedActivityId');
        if (hiddenActivityId && originalActivityId) {
            hiddenActivityId.value = originalActivityId;
            hiddenActivityId.disabled = false;
            plannedActivitySelect.disabled = true;
            plannedActivitySelect.classList.add('hidden');
        }
        window.dispatchEvent(new CustomEvent('reset-gpoa-prefill', { detail: { program_flows: serverProgramFlows } }));
        setPastDateState(serverPrefill.date);
        updateSdgSummary();
        updateChangedFields();
    });

    requestForm?.addEventListener('input', updateChangedFields);
    requestForm?.addEventListener('change', updateChangedFields);

    /**
     * Update the summary badges area with currently selected SDGs
     */
    function updateSdgSummary() {
        const checkedBoxes = Array.from(
            sdgCheckboxContainer.querySelectorAll('input[type="checkbox"]:checked')
        );
        sdgSummary.innerHTML = '';

        if (checkedBoxes.length === 0) {
            const placeholder = document.createElement('span');
            placeholder.className = 'sdg-placeholder';
            placeholder.textContent = 'No SDGs selected yet';
            sdgSummary.appendChild(placeholder);
        } else {
            checkedBoxes.forEach(checkbox => {
                const badge = document.createElement('span');
                badge.className = 'sdg-badge';
                badge.textContent = `SDG ${checkbox.value}`;
                badge.style.backgroundColor = sdgColors[checkbox.value].color;
                badge.style.color = sdgColors[checkbox.value].text;
                sdgSummary.appendChild(badge);
            });
        }
    }

    /**
     * Clear validation error
     */
    function clearValidationError() {
        sdgValidationError.classList.remove('show');
        sdgValidationError.textContent = '';
    }

    function validateEndDate() {
        if (!dateInput || !endDateInput || !dateInput.value || !endDateInput.value) {
            return true;
        }

        if (new Date(endDateInput.value) < new Date(dateInput.value)) {
            endDateInput.setCustomValidity('End date cannot be earlier than the start date.');
            endDateInput.reportValidity();
            return false;
        }

        endDateInput.setCustomValidity('');
        return true;
    }

    /**
     * Validate SDG selection on form submit
     */
    function validateSdgSelection() {
        const checkedBoxes = Array.from(
            sdgCheckboxContainer.querySelectorAll('input[type="checkbox"]:checked')
        );
        const count = checkedBoxes.length;

        if (count < MIN_SDGS || count > MAX_SDGS) {
            sdgValidationError.textContent = `Please select between ${MIN_SDGS} and ${MAX_SDGS} SDGs. You have selected ${count}.`;
            sdgValidationError.classList.add('show');
            return false;
        }

        clearValidationError();
        return true;
    }

    // Listen for checkbox changes on the stable container so it still works if the
    // checkbox nodes are replaced during Alpine.js re-renders.
    sdgCheckboxContainer.addEventListener('change', function(e) {
        if (e.target.matches('input[type="checkbox"]')) {
            updateSdgSummary();
            clearValidationError();
        }
    });

    // Validate on form submit
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            const endDateIsValid = validateEndDate();

            if (!validateSdgSelection() || !endDateIsValid) {
                e.preventDefault();

                if (!validateSdgSelection()) {
                    sdgCheckboxContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    // Initialize on page load - update badges to reflect any pre-checked checkboxes
    updateSdgSummary();
    if (!hasOldInput && plannedActivitySelect?.value) {
        prefillFromPlannedActivity(true);
    } else {
        updateChangedFields();
    }

    window.addEventListener('pageshow', function(event) {
        updateSdgSummary();
    });
});
</script>
@endpush
</x-app-layout>
