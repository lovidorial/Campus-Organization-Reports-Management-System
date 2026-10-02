<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

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
            @json($plannedActivitySeed)
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
                                        <span x-show="activity.importWarnings && activity.importWarnings.length" class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Check this row</span>
                                        <button type="button" @click="removeActivity(index)" class="btn-secondary btn-small">Remove</button>
                                    </div>
                                </div>
                                <p x-show="activity.importWarnings && activity.importWarnings.length" x-text="(activity.importWarnings || []).join('; ')" class="mb-3 text-xs text-amber-800"></p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" :name="'planned_activities[' + index + '][title]'" x-model="activity.title" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>Time frame</label>
                                        <select :name="'planned_activities[' + index + '][time_frame]'" x-model="activity.time_frame" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            <option value="">Select time frame</option>
                                            <option value="exact_date">Exact date</option>
                                            <option value="date_range">Date range</option>
                                            <option value="month_only">Month only</option>
                                        </select>
                                    </div>

                                    <template x-if="activity.time_frame === 'exact_date'">
                                        <div class="form-group">
                                            <label>Date</label>
                                            <input type="date" :name="'planned_activities[' + index + '][date]'" x-model="activity.date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </div>
                                    </template>

                                    <template x-if="activity.time_frame === 'date_range'">
                                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="form-group">
                                                <label>Start date</label>
                                                <input type="date" :name="'planned_activities[' + index + '][date]'" x-model="activity.date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            </div>
                                            <div class="form-group">
                                                <label>End date</label>
                                                <input type="date" :name="'planned_activities[' + index + '][end_date]'" x-model="activity.end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                                <p x-show="validationErrors['planned_activities.' + index + '.end_date']" x-text="validationErrors['planned_activities.' + index + '.end_date'] && validationErrors['planned_activities.' + index + '.end_date'][0]" class="text-red-500 text-xs mt-1"></p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="activity.time_frame === 'month_only'">
                                        <div class="form-group md:col-span-2">
                                            <label>Month</label>
                                            <input type="month" :name="'planned_activities[' + index + '][date]'" x-model="activity.date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
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
                                        <label>Venue</label>
                                        <input type="text" :name="'planned_activities[' + index + '][venue]'" x-model="activity.venue" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select :name="'planned_activities[' + index + '][category]'" x-model="activity.category" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            <option value="">Select category</option>
                                            @include('partials.category-options')
                                        </select>
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        @include('partials.sdg-checkboxes', ['gpoa' => true])
                                    </div>
                                </div>
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
                        const timeFrame = activity.time_frame || (activity.end_date ? 'date_range' : (activity.date && activity.date.length === 7 ? 'month_only' : 'exact_date'));

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
                            importWarnings: Array.isArray(activity.importWarnings) ? activity.importWarnings : [],
                            _key: activity._key || `activity-${index}-${Date.now()}`,
                        };
                    });
                            if (this.activities.length >= this.maxActivities) return;

                } catch (error) {
                    return [];
                }
            })(),
            addActivity() {
                this.activities.push({
                    id: null,
                    _key: `new-${Date.now()}-${Math.random()}`,
                    title: '',
                    time_frame: 'exact_date',
                    date: '',
                    end_date: '',
                    start_time: '',
                    end_time: '',
                    venue: '',
                    category: '',
                    sdgs: [],
                    importWarnings: [],
                });
            },
            async importFile(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.importError = '';
                this.importSummary = '';
                this.importNotes = [];
                this.repeatedTitleHint = '';

                if (this.activities.some(activity => activity.title || activity.date || activity.end_date || activity.venue || activity.category || (activity.sdgs && activity.sdgs.length))) {
                    if (!window.confirm('Replace the planned activity rows currently entered with the imported rows?')) {
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
                        category: '',
                        sdgs: Array.isArray(row.sdgs) ? row.sdgs.map(Number) : [],
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
