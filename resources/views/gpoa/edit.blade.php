<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">
    <style>
        .sdg-label-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; }
        .sdg-label-row label { margin: 0; flex-shrink: 0; }
        .sdg-summary-badges { display: flex; gap: 6px; flex-wrap: wrap; flex: 1; padding: 6px 8px; background: #F3F4F6; border-radius: 4px; min-height: 24px; align-items: center; }
        .sdg-badge { padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; display: inline-block; white-space: nowrap; }
        .sdg-placeholder { color: #9CA3AF; font-size: 0.875rem; }
        .sdg-checkbox-list { display: flex; flex-direction: column; gap: 8px; max-height: 240px; overflow-y: auto; padding: 8px; border: 1px solid #D1D5DB; border-radius: 4px; background: #FFFFFF; margin-bottom: 8px; }
        .sdg-checkbox-item { display: flex; align-items: center; gap: 8px; padding: 4px; cursor: pointer; user-select: none; }
        .sdg-checkbox-item input[type="checkbox"] { cursor: pointer; }
        .sdg-checkbox-item label { cursor: pointer; margin: 0; font-size: 0.875rem; flex: 1; }
        .sdg-number { display: inline-flex; align-items: center; justify-content: center; width: 1.35rem; height: 1.35rem; flex: 0 0 1.35rem; border-radius: 50%; font-size: 0.7rem; font-weight: 700; }
        .sdg-helper-text { font-size: 0.75rem; color: #6B7280; display: block; margin-bottom: 8px; font-weight: 500; }
        .sdg-validation-error { color: #DC2626; font-size: 0.875rem; margin-top: 6px; }
    </style>

    <main class="page-wrapper">
        <div class="page-header">
            <div>
                <p class="eyebrow">Edit General Plan of Activities</p>
                <h1>Edit GPOA</h1>
                <p class="page-description">Update your GPOA information and planned activities.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary btn-close">Back to Dashboard</a>
        </div>

        @if($errors->any())
            <div class="form-errors">
                <strong>Please fix the following errors:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <script type="application/json" id="planned-activities-seed">
            @json(old('planned_activities', $plannedActivitySeed))
        </script>

        <form action="{{ route('gpoa.update', $gpoa) }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="gpoaForm">
            @csrf
            @method('PUT')

            <section class="form-section">
                <div class="section-heading">
                    <div>
                        <h2 class="section-title">GPOA Information</h2>
                        <p class="section-description">Update the college and replace the approved document if needed. Leave the file empty to keep the current document.</p>
                    </div>
                </div>

                <div class="form-row grid-3">
                    <div class="form-group">
                        <label for="colleges">College *</label>
                        <select id="colleges" name="colleges" required>
                            @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS'] as $c)
                                <option value="{{ $c }}" {{ old('colleges', $gpoa->college) == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Term</label>
                        <input type="text" disabled value="{{ $gpoa->term }}" class="bg-gray-100">
                    </div>

                    <div class="form-group">
                        <label>School Year</label>
                        <input type="text" disabled value="{{ $gpoa->school_year }}" class="bg-gray-100">
                    </div>
                </div>

                <div class="form-group mt-6">
                    <label for="prepared_by">Prepared By *</label>
                    <input type="text" id="prepared_by" name="prepared_by" required placeholder="e.g. John Doe, Officer"
                           value="{{ old('prepared_by', $gpoa->prepared_by ?? '') }}">
                    @error('prepared_by')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mt-6">
                    <label for="document_path">Approved GPOA Document (PDF)</label>
                    <input type="file" id="document_path" name="document_path" accept=".pdf">
                    @if($gpoa->document_path)
                        <p class="help-text">Current approved PDF uploaded. Leave the file empty to keep the current one.</p>
                    @endif
                </div>

                <div class="form-group mt-6">
                    <label>Planned Activities</label>
                    <div id="planned-activities-preview" x-data="plannedActivities()">
                        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" class="btn-secondary" @click="$refs.importFile.click()" :disabled="importing">Import from Word/Excel</button>
                                <input x-ref="importFile" type="file" accept=".docx,.xlsx" class="hidden" @change="importFile($event)">
                                <span x-show="importing" class="help-text">Reading file...</span>
                            </div>
                            <p class="help-text mt-2">Have the Word or Excel version of your GPOA? Import it to fill the rows automatically. PDF files cannot be read reliably, so keep the PDF only as your approved copy.</p>
                            <p x-show="importSummary" x-text="importSummary" class="mt-3 text-sm font-medium text-green-700"></p>
                            <div x-show="importNotes.length" class="mt-2 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                                <template x-for="(note, noteIndex) in importNotes" :key="noteIndex"><p x-text="note"></p></template>
                            </div>
                            <p x-show="repeatedTitleHint" x-text="repeatedTitleHint" class="mt-2 text-sm text-amber-800"></p>
                            <p x-show="importError" x-text="importError" role="alert" class="mt-2 text-sm text-red-700"></p>
                        </div>
                        <template x-if="activities.length === 0">
                            <p class="help-text">No planned activities are currently recorded. Add the official activity index rows manually.</p>
                        </template>

                        <template x-for="(activity, index) in activities" :key="activity._key">
                            <div class="mt-4 rounded-xl border border-gray-300 bg-gray-50 p-4">
                                <input type="hidden" :name="'planned_activities[' + index + '][id]'" x-model="activity.id">
                                <div class="mb-3 flex items-center justify-between">
                                    <strong class="text-sm text-gray-700">Activity <span x-text="index + 1"></span></strong>
                                    <div class="flex items-center gap-2">
                                        <span x-show="activityComplete(activity)" class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Details complete</span>
                                        <span x-show="!activityComplete(activity)" class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Details missing</span>
                                        <span x-show="activity.importWarnings && activity.importWarnings.length" class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Check this row</span>
                                        <button type="button" @click="removeActivity(index)" class="btn-secondary btn-small">Remove</button>
                                    </div>
                                </div>
                                <p x-show="activity.importWarnings && activity.importWarnings.length" x-text="(activity.importWarnings || []).join('; ')" class="mb-3 text-xs text-amber-800"></p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                            <label>Title *</label>
                                            <input type="text" :name="'planned_activities[' + index + '][title]'" x-model="activity.title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                            <label>Date or time frame *</label>
                                            <select :name="'planned_activities[' + index + '][time_frame]'" x-model="activity.time_frame" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            <option value="">Select time frame</option>
                                            <option value="exact_date">Exact date</option>
                                            <option value="date_range">Date range</option>
                                            <option value="month_only">Month only</option>
                                        </select>
                                    </div>

                                    <template x-if="activity.time_frame === 'exact_date'">
                                        <div class="form-group">
                                                <label>Date *</label>
                                                <input type="date" :name="'planned_activities[' + index + '][date]'" x-model="activity.date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </div>
                                    </template>

                                    <template x-if="activity.time_frame === 'date_range'">
                                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="form-group">
                                                    <label>Start date *</label>
                                                    <input type="date" :name="'planned_activities[' + index + '][date]'" x-model="activity.date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            </div>
                                            <div class="form-group">
                                                    <label>End date *</label>
                                                    <input type="date" :name="'planned_activities[' + index + '][end_date]'" x-model="activity.end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                                <p x-show="validationErrors['planned_activities.' + index + '.end_date']" x-text="validationErrors['planned_activities.' + index + '.end_date'] && validationErrors['planned_activities.' + index + '.end_date'][0]" class="text-red-500 text-xs mt-1"></p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="activity.time_frame === 'month_only'">
                                        <div class="form-group md:col-span-2">
                                                <label>Month *</label>
                                                <input type="month" :name="'planned_activities[' + index + '][date]'" x-model="activity.date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </div>
                                    </template>

                                    <template x-if="activity.time_frame === 'exact_date' || activity.time_frame === 'date_range'">
                                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="form-group">
                                                <label>Start time</label>
                                                <input type="time" :name="'planned_activities[' + index + '][start_time]'" x-model="activity.start_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            </div>
                                            <div class="form-group">
                                                <label>End time</label>
                                                <input type="time" :name="'planned_activities[' + index + '][end_time]'" x-model="activity.end_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            </div>
                                        </div>
                                    </template>

                                    <div class="form-group md:col-span-2">
                                        <label>Venue *</label>
                                        <input type="text" :name="'planned_activities[' + index + '][venue]'" x-model="activity.venue" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select :name="'planned_activities[' + index + '][category]'" x-model="activity.category" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            <option value="">Select category (optional)</option>
                                            @include('partials.category-options', ['gpoaCategories' => array_values(array_unique(array_merge(array_keys(config('gpoa_activity_limits', [])), ['Religious Activity', 'Socio-Cultural and Sports', 'Makakalikasan (Clean and Green)', 'Extension Services Conducted'])) )])
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500">Optional. You will provide this when requesting the activity.</p>
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <div class="sdg-widget" x-init="$nextTick(() => { const summary = $el.querySelector('[data-sdg-summary]'); const colors = @js(config('sdg')); const update = () => { const selected = activity.sdgs || []; summary.innerHTML = selected.length ? selected.map(number => `<span class='sdg-badge' style='background-color: ${colors[number].color}; color: ${colors[number].text}'>SDG ${number}</span>`).join('') : '<span class=&quot;sdg-placeholder&quot;>No SDGs selected yet</span>'; }; $el.addEventListener('change', update); update(); })">
                                            <div class="sdg-label-row"><label>SDGs *</label><div class="sdg-summary-badges" :id="'sdgSummary-' + index" data-sdg-summary><span class="sdg-placeholder">No SDGs selected yet</span></div></div>
                                            <div class="sdg-checkbox-list" :id="'sdgCheckboxes-' + index" data-sdg-checkboxes>
                                                @foreach([1 => 'No Poverty', 2 => 'Zero Hunger', 3 => 'Good Health', 4 => 'Quality Education', 5 => 'Gender Equality', 6 => 'Clean Water', 7 => 'Affordable Energy', 8 => 'Decent Work', 9 => 'Industry, Innovation', 10 => 'Reduced Inequality', 11 => 'Sustainable Cities', 12 => 'Responsible Consumption', 13 => 'Climate Action', 14 => 'Life Below Water', 15 => 'Life on Land', 16 => 'Peace/Justice', 17 => 'Partnerships'] as $number => $label)
                                                    <div class="sdg-checkbox-item"><input type="checkbox" :id="'planned-sdg-' + index + '-{{ $number }}'" :name="'planned_activities[' + index + '][sdgs][]'" value="{{ $number }}" x-model="activity.sdgs" :required="activity.sdgs.length === 0 && {{ $number }} === 1"><span class="sdg-number" style="background-color: {{ config('sdg.' . $number . '.color') }}; color: {{ config('sdg.' . $number . '.text') }}">{{ $number }}</span><label :for="'planned-sdg-' + index + '-{{ $number }}'">SDG {{ $number }} - {{ $label }}</label></div>
                                                @endforeach
                                            </div>
                                            <span class="sdg-helper-text">Must select 1-8 SDGs aligned with the activity</span>
                                            <p x-show="activity.sdgs.length === 0" class="sdg-validation-error show">Select at least 1 SDG.</p>
                                        </div>
                                    </div>
                                </div>
                                <details class="mt-4 rounded-lg border border-slate-200 bg-white p-4" :open="activity.detailsOpen || detailsHaveErrors(index)" @invalid.capture="activity.detailsOpen = true">
                                    <summary class="cursor-pointer font-semibold text-slate-800">Activity details</summary>
                                    <div class="mt-4 space-y-4">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="form-group md:col-span-2"><label>Objectives *</label><textarea rows="3" :name="'planned_activities[' + index + '][objectives]'" x-model="activity.objectives" required class="w-full rounded-lg border border-gray-300 px-3 py-2"></textarea><p x-show="fieldError(index, 'objectives')" x-text="fieldError(index, 'objectives')" class="text-xs text-red-600"></p></div>
                                            <div class="form-group md:col-span-2"><label>Expected Outcome *</label><textarea rows="3" :name="'planned_activities[' + index + '][expected_outcome]'" x-model="activity.expected_outcome" required class="w-full rounded-lg border border-gray-300 px-3 py-2"></textarea><p x-show="fieldError(index, 'expected_outcome')" x-text="fieldError(index, 'expected_outcome')" class="text-xs text-red-600"></p></div>
                                            <div class="form-group md:col-span-2"><label>Delivery Strategy *</label><textarea rows="3" :name="'planned_activities[' + index + '][plan_key_strategy]'" x-model="activity.plan_key_strategy" required class="w-full rounded-lg border border-gray-300 px-3 py-2"></textarea><p x-show="fieldError(index, 'plan_key_strategy')" x-text="fieldError(index, 'plan_key_strategy')" class="text-xs text-red-600"></p></div>
                                            <div class="form-group"><label>Target Participants *</label><input type="text" :name="'planned_activities[' + index + '][target_participants]'" x-model="activity.target_participants" required class="w-full rounded-lg border border-gray-300 px-3 py-2"><p x-show="fieldError(index, 'target_participants')" x-text="fieldError(index, 'target_participants')" class="text-xs text-red-600"></p></div>
                                            <div class="form-group"><label>Person in Charge *</label><input type="text" :name="'planned_activities[' + index + '][person_in_charge]'" x-model="activity.person_in_charge" required class="w-full rounded-lg border border-gray-300 px-3 py-2"><p x-show="fieldError(index, 'person_in_charge')" x-text="fieldError(index, 'person_in_charge')" class="text-xs text-red-600"></p></div>
                                            <div class="form-group"><label>Facilities / Materials *</label><input type="text" :name="'planned_activities[' + index + '][facilities_materials]'" x-model="activity.facilities_materials" required class="w-full rounded-lg border border-gray-300 px-3 py-2"><p x-show="fieldError(index, 'facilities_materials')" x-text="fieldError(index, 'facilities_materials')" class="text-xs text-red-600"></p></div>
                                            <div class="form-group"><label>Estimated Budget *</label><input type="number" min="0" step="0.01" :name="'planned_activities[' + index + '][estimated_budget]'" x-model="activity.estimated_budget" required class="w-full rounded-lg border border-gray-300 px-3 py-2"><p x-show="fieldError(index, 'estimated_budget')" x-text="fieldError(index, 'estimated_budget')" class="text-xs text-red-600"></p></div>
                                            <div class="form-group md:col-span-2"><label>Source of Funds</label><input type="text" :name="'planned_activities[' + index + '][source_of_funds]'" x-model="activity.source_of_funds" class="w-full rounded-lg border border-gray-300 px-3 py-2"><p class="mt-1 text-xs text-slate-500">Optional. You will provide this when requesting the activity.</p><p x-show="fieldError(index, 'source_of_funds')" x-text="fieldError(index, 'source_of_funds')" class="text-xs text-red-600"></p></div>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                                            <select :value="''" @change="copyDetailsFrom(index, $event.target.value); $event.target.value = ''" class="w-full rounded-lg border border-gray-300 px-3 py-2"><option value="">Copy details from another activity</option><template x-for="(other, otherIndex) in activities" :key="other._key"><option x-show="otherIndex !== index && activityComplete(other)" :value="otherIndex" x-text="'Activity ' + (otherIndex + 1) + ' - ' + other.title"></option></template></select>
                                            <button type="button" @click="copyCommonDetailsToAll(index)" class="btn-secondary">Copy shared details to all activities</button>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </template>

                        <div class="mt-4 flex justify-end">
                            <button type="button" @click="addActivity()" x-show="activities.length < maxActivities" class="btn-secondary">Add row</button>
                            <span x-show="activities.length >= maxActivities" class="help-text">Maximum of 34 planned activities reached.</span>
                        </div>
                    </div>
                </div>

                <div class="form-group checkbox mt-6">
                    <input type="checkbox" id="verify" name="verify">
                    <label for="verify">I verify that the GPOA information provided is accurate and complete.</label>
                </div>

                <div class="form-actions mt-8">
                    <button type="submit" class="btn-primary">Update GPOA</button>
                </div>
            </section>
        </form>
    </main>

    <script>
        window.plannedActivities = () => ({
            maxActivities: {{ config('gpoa.max_planned_activities') }},
            validationErrors: @json($errors->getMessages()),
            schoolYear: @json($gpoa->school_year),
            importing: false,
            importSummary: '',
            importNotes: [],
            repeatedTitleHint: '',
            importError: '',
            activities: (() => {
                try {
                    const seed = document.getElementById('planned-activities-seed');
                    const activities = seed ? JSON.parse(seed.textContent || '[]') : [];
                    return activities.map((activity, index) => {
                        const timeFrame = activity.time_frame || (activity.end_date ? 'date_range' : (activity.date && activity.date.length === 7 ? 'month_only' : (activity.date ? 'exact_date' : '')));

                        return {
                            ...activity,
                            time_frame: timeFrame,
                            date: timeFrame === 'month_only' && activity.date ? activity.date.slice(0, 7) : (activity.date || ''),
                            end_date: activity.end_date || '',
                            start_time: activity.start_time || '',
                            end_time: activity.end_time || '',
                            venue: activity.venue || '',
                            category: activity.category || '',
                            sdgs: Array.isArray(activity.sdgs) ? activity.sdgs : [],
                            objectives: activity.objectives || '',
                            expected_outcome: activity.expected_outcome || '',
                            plan_key_strategy: activity.plan_key_strategy || '',
                            target_participants: activity.target_participants || '',
                            person_in_charge: activity.person_in_charge || '',
                            facilities_materials: activity.facilities_materials || '',
                            estimated_budget: activity.estimated_budget ?? '',
                            source_of_funds: activity.source_of_funds || '',
                            detailsOpen: false,
                            importWarnings: Array.isArray(activity.importWarnings) ? activity.importWarnings : [],
                            _key: activity._key || `activity-${index}-${Date.now()}`,
                        };
                    });

                } catch (error) {
                    return [];
                }
            })(),
            addActivity() {
                if (this.activities.length >= this.maxActivities) return;
                this.activities.push({
                    id: null,
                    _key: `new-${Date.now()}-${Math.random()}`,
                    title: '',
                    time_frame: '',
                    date: '',
                    end_date: '',
                    start_time: '',
                    end_time: '',
                    venue: '',
                    category: '',
                    sdgs: [],
                    objectives: '',
                    expected_outcome: '',
                    plan_key_strategy: '',
                    target_participants: '',
                    person_in_charge: '',
                    facilities_materials: '',
                    estimated_budget: '',
                    source_of_funds: '',
                    detailsOpen: false,
                    importWarnings: [],
                });
            },
            detailFields: ['objectives', 'expected_outcome', 'plan_key_strategy', 'target_participants', 'person_in_charge', 'facilities_materials', 'estimated_budget', 'source_of_funds'],
            fieldError(index, field) { return this.validationErrors[`planned_activities.${index}.${field}`]?.[0] || ''; },
            detailsHaveErrors(index) { return this.detailFields.some(field => this.fieldError(index, field)) || Boolean(this.fieldError(index, 'sdgs')); },
            activityComplete(activity) {
                const validDate = activity.time_frame === 'exact_date' ? Boolean(activity.date) : activity.time_frame === 'date_range' ? Boolean(activity.date && activity.end_date) : activity.time_frame === 'month_only' ? Boolean(activity.date) : false;
                return Boolean(activity.title?.trim() && validDate && activity.venue?.trim() && activity.sdgs?.length && activity.objectives?.trim() && activity.expected_outcome?.trim() && activity.plan_key_strategy?.trim() && activity.target_participants?.trim() && activity.person_in_charge?.trim() && activity.facilities_materials?.trim() && activity.estimated_budget !== '' && activity.estimated_budget !== null);
            },
            copyDetailsFrom(index, sourceIndex) {
                if (sourceIndex === '' || sourceIndex === null) return;
                const source = this.activities[Number(sourceIndex)];
                if (source) {
                    ['title', 'time_frame', 'date', 'end_date', 'start_time', 'end_time', 'venue', 'category', ...this.detailFields]
                        .forEach(field => { this.activities[index][field] = source[field] ?? ''; });
                    this.activities[index].sdgs = [...(source.sdgs || [])];
                }
            },
            copyFieldToAll(index, field) {
                const value = this.activities[index][field];
                this.activities.forEach((activity, activityIndex) => { if (activityIndex !== index) activity[field] = value; });
            },
            copyCommonDetailsToAll(index) {
                ['target_participants', 'person_in_charge', 'source_of_funds', 'facilities_materials'].forEach(field => this.copyFieldToAll(index, field));
            },
            async importFile(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.importError = '';
                this.importSummary = '';
                this.importNotes = [];
                this.repeatedTitleHint = '';

                if (this.activities.some(activity => ['title', 'date', 'end_date', 'start_time', 'end_time', 'venue', 'category', ...this.detailFields].some(field => String(activity[field] ?? '').trim() !== '') || activity.sdgs?.length)) {
                    const replaceConfirmed = await window.orgConfirm({
                        title: 'Replace planned activities?',
                        message: 'Replace the planned activity rows currently entered with the imported rows?',
                        label: 'Replace rows',
                        variant: 'warning',
                    });
                    if (!replaceConfirmed) {
                        event.target.value = '';
                        return;
                    }
                }

                this.importing = true;
                try {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('school_year', document.querySelector('[name="school_year"]')?.value || this.schoolYear);
                    const token = document.querySelector('#gpoaForm input[name="_token"]')?.value || '';
                    const response = await fetch(@json(route('gpoa.import-preview')), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: formData,
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'The selected file could not be read.');

                    this.activities = result.rows.map((row, index) => ({
                        id: null,
                        _key: `import-${Date.now()}-${index}`,
                        title: row.title || '',
                        time_frame: ({ exact: 'exact_date', range: 'date_range', month: 'month_only' })[row.time_frame] || '',
                        date: row.time_frame === 'month' && row.date ? row.date.slice(0, 7) : (row.date || ''),
                        end_date: row.end_date || '',
                        start_time: row.start_time || '',
                        end_time: row.end_time || '',
                        venue: row.venue || '',
                        category: row.category || '',
                        sdgs: Array.isArray(row.sdgs) ? row.sdgs.map(Number) : [],
                        objectives: row.objectives || '',
                        expected_outcome: row.expected_outcome || '',
                        plan_key_strategy: row.plan_key_strategy || '',
                        target_participants: row.target_participants || '',
                        person_in_charge: row.person_in_charge || '',
                        facilities_materials: row.facilities_materials || '',
                        estimated_budget: row.estimated_budget ?? '',
                        source_of_funds: row.source_of_funds || '',
                        importWarnings: row.warnings || [],
                    }));
                    this.importSummary = `Imported ${this.activities.length} activities`;
                    this.importNotes = result.warnings || [];
                    if (result.skipped) this.importNotes.unshift(`${result.skipped} rows skipped because the maximum is 34.`);
                    this.repeatedTitleHint = (result.repeated_titles || []).map(item => `${item.title} x${item.count}`).join('; ');
                } catch (error) {
                    this.importError = error.message || 'This file could not be read. Choose a valid DOCX or XLSX file.';
                } finally {
                    this.importing = false;
                    event.target.value = '';
                }
            },
            removeActivity(index) {
                this.activities.splice(index, 1);
            }
        });

    </script>
</x-app-layout>
