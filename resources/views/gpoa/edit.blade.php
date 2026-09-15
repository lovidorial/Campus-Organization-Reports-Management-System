<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <main class="page-wrapper">
        <div class="page-header">
            <div>
                <p class="eyebrow">Edit General Plan of Activities</p>
                <h1>Edit GPOA (Pending Review)</h1>
                <p class="page-description">Update only the GPOA metadata and document while your submission is under review.</p>
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
            @json($gpoa->activities->map(function ($activity) {
                return [
                    'title' => $activity->title,
                    'date' => $activity->date ? $activity->date->format('Y-m-d') : '',
                    'venue' => $activity->venue ?? '',
                    'category' => $activity->category ?? '',
                    'sdgs' => is_array($activity->sdgs) ? implode(', ', $activity->sdgs) : '',
                    'objectives' => $activity->objectives ?? '',
                    'expected_outcome' => $activity->expected_outcome ?? '',
                    'target_participants' => $activity->target_participants ?? '',
                    'person_in_charge' => $activity->person_in_charge ?? '',
                    'facilities_materials' => $activity->facilities_materials ?? '',
                    'estimated_budget' => $activity->estimated_budget ?? '',
                    'source_of_funds' => $activity->source_of_funds ?? '',
                    'plan_key_strategy' => $activity->plan_key_strategy ?? '',
                    'preceding_activity' => $activity->preceding_activity ?? '',
                ];
            })->values()->all())
        </script>

        <form action="{{ route('gpoa.update', $gpoa) }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="gpoaForm">
            @csrf
            @method('PUT')

            <section class="form-section">
                <div class="section-heading">
                    <div>
                        <h2 class="section-title">GPOA Information</h2>
                        <p class="section-description">Update the college and upload an optional document for this GPOA submission.</p>
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
                    <label for="document_path">GPOA Document (PDF)</label>
                    <input type="file" id="document_path" name="document_path" accept=".pdf">
                    @if($gpoa->document_path)
                        <p class="help-text">Current document uploaded. Leave empty to keep existing.</p>
                    @endif
                </div>

                <div class="form-group mt-6">
                    <label>Planned Activities Preview</label>
                    <div id="planned-activities-preview" x-data="plannedActivities()">
                        <template x-if="activities.length === 0">
                            <p class="help-text">No planned activities are currently recorded. Select a PDF file to preview candidate rows, or add rows manually.</p>
                        </template>

                        <template x-for="(activity, index) in activities" :key="index">
                            <div class="mt-4 rounded-xl border border-gray-300 bg-gray-50 p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <strong class="text-sm text-gray-700">Activity <span x-text="index + 1"></span></strong>
                                    <button type="button" @click="removeActivity(index)" class="btn-secondary btn-small">Remove</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" :name="'planned_activities[' + index + '][title]'" x-model="activity.title" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="date" :name="'planned_activities[' + index + '][date]'" x-model="activity.date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label>Venue</label>
                                        <input type="text" :name="'planned_activities[' + index + '][venue]'" x-model="activity.venue" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>Category</label>
                                        <input type="text" :name="'planned_activities[' + index + '][category]'" x-model="activity.category" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>SDGs</label>
                                        <input type="text" :name="'planned_activities[' + index + '][sdgs]'" x-model="activity.sdgs" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label>Objectives</label>
                                        <textarea :name="'planned_activities[' + index + '][objectives]'" x-model="activity.objectives" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label>Expected Outcome</label>
                                        <textarea :name="'planned_activities[' + index + '][expected_outcome]'" x-model="activity.expected_outcome" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Target Participants</label>
                                        <input type="text" :name="'planned_activities[' + index + '][target_participants]'" x-model="activity.target_participants" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>Person in Charge</label>
                                        <input type="text" :name="'planned_activities[' + index + '][person_in_charge]'" x-model="activity.person_in_charge" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label>Facilities / Materials</label>
                                        <textarea :name="'planned_activities[' + index + '][facilities_materials]'" x-model="activity.facilities_materials" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Estimated Budget</label>
                                        <input type="number" step="0.01" :name="'planned_activities[' + index + '][estimated_budget]'" x-model="activity.estimated_budget" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group">
                                        <label>Source of Funds</label>
                                        <input type="text" :name="'planned_activities[' + index + '][source_of_funds]'" x-model="activity.source_of_funds" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label>Plan / Key Strategy</label>
                                        <textarea :name="'planned_activities[' + index + '][plan_key_strategy]'" x-model="activity.plan_key_strategy" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label>Preceding Activity</label>
                                        <input type="text" :name="'planned_activities[' + index + '][preceding_activity]'" x-model="activity.preceding_activity" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="mt-4 flex justify-end">
                            <button type="button" @click="addActivity()" class="btn-secondary">Add row</button>
                        </div>
                    </div>
                </div>

                <div class="form-group checkbox mt-6">
                    <input type="checkbox" id="verify" name="verify" required>
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
            activities: (() => {
                try {
                    const seed = document.getElementById('planned-activities-seed');
                    return seed ? JSON.parse(seed.textContent || '[]') : [];
                } catch (error) {
                    return [];
                }
            })(),
            addActivity() {
                this.activities.push({
                    title: '',
                    date: '',
                    venue: '',
                    category: '',
                    sdgs: '',
                    objectives: '',
                    expected_outcome: '',
                    target_participants: '',
                    person_in_charge: '',
                    facilities_materials: '',
                    estimated_budget: '',
                    source_of_funds: '',
                    plan_key_strategy: '',
                    preceding_activity: '',
                });
            },
            removeActivity(index) {
                this.activities.splice(index, 1);
            }
        });

    </script>
</x-app-layout>
