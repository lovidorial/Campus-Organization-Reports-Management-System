<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <main class="page-wrapper">
        <div class="gpoa-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow">Submit General Plan of Activities</p>
                    <h1>Submit General Plan of Activities (GPOA)</h1>
                    <p class="page-description">Complete the GPOA form. Activity requests are created separately after your GPOA is approved.</p>
                </div>
                <a href="{{ route('gpoa.index') }}" class="icon-close">×</a>
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

            <script type="application/json" id="planned-activities-seed">[]</script>

            <form action="{{ route('gpoa.store') }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="gpoaForm">
                @csrf

                <section class="form-section">
                    <div class="section-heading">
                        <div>
                            <h2 class="section-title">GPOA Information</h2>
                            <p class="section-description">Complete the following fields to submit your General Plan of Activities for OSDW review and approval.</p>
                        </div>
                    </div>

                    <!-- Organization -->
                    <div class="form-row grid-2">
                        <div class="form-group">
                            <label for="organization">Organization *</label>
                            <input type="text" id="organization" name="organization" disabled value="{{ auth()->user()->org_name ?? auth()->user()->name ?? 'N/A' }}" class="bg-gray-100">
                            <small class="help-text">Auto-filled from your profile.</small>
                        </div>

                        <!-- College -->
                        <div class="form-group">
                            <label for="colleges">College *</label>
                            @if(!empty($detectedCollege))
                                <select id="colleges" name="colleges" required>
                                    <option value="{{ $detectedCollege }}" selected>{{ $detectedCollege }}</option>
                                    @foreach(['CTICS','CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS'] as $c)
                                        @if($c !== $detectedCollege)
                                            <option value="{{ $c }}">{{ $c }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <small class="help-text">Auto-detected from your organization. You can override this.</small>
                            @else
                                <select id="colleges" name="colleges" required>
                                    <option value="">Select college</option>
                                    @foreach(['CTICS','CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS'] as $c)
                                        <option value="{{ $c }}" {{ old('colleges') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>

                    <!-- Term and School Year -->
                    <div class="form-row grid-2">
                        <div class="form-group">
                            <label for="term">Term *</label>
                            @if(auth()->user()->term)
                                <input type="text" disabled value="{{ old('term', auth()->user()->term) }}" class="bg-gray-100">
                                <input type="hidden" name="term" value="{{ old('term', auth()->user()->term) }}">
                            @else
                                <select id="term" name="term" required>
                                    <option value="">Select term</option>
                                    <option value="1st Term" {{ old('term') == '1st Term' ? 'selected' : '' }}>1st Term</option>
                                    <option value="2nd Term" {{ old('term') == '2nd Term' ? 'selected' : '' }}>2nd Term</option>
                                </select>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="school_year">School Year *</label>
                            @if(auth()->user()->school_year)
                                <input type="text" disabled value="{{ old('school_year', auth()->user()->school_year) }}" class="bg-gray-100">
                                <input type="hidden" name="school_year" value="{{ old('school_year', auth()->user()->school_year) }}">
                            @else
                                <input type="text" id="school_year" name="school_year" required placeholder="e.g. 2026-2027" value="{{ old('school_year') }}">
                            @endif
                        </div>
                    </div>

                    <!-- Prepared By -->
                    <div class="form-group">
                        <label for="prepared_by">Prepared By *</label>
                        <input type="text" id="prepared_by" name="prepared_by" required placeholder="e.g. John Doe, Officer" 
                               value="{{ old('prepared_by', (auth()->user()->name ?? '') . (auth()->user()->position ? ', ' . auth()->user()->position : '')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <small class="help-text">Defaults to your name and position. You can edit this field.</small>
                    </div>

                    <!-- Document Attachment -->
                    <div class="form-group">
                        <label for="document_path">GPOA Document (PDF) *</label>
                        <input type="file" id="document_path" name="document_path" accept=".pdf" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('document_path')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="help-text">Upload your official GPOA document as a supplementary attachment (Max 20MB).</p>
                    </div>

                    <div class="form-group mt-6">
                        <label>Planned Activities Preview</label>
                        <div id="planned-activities-preview" x-data="plannedActivities()">
                            <template x-if="activities.length === 0">
                                <p class="help-text">Select a PDF file to preview best-effort candidate activities. You can review, edit, add, or remove rows before submitting.</p>
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

                    <!-- Verification -->
                    <div class="form-group checkbox mt-6">
                        <input type="checkbox" id="verify" name="verify" required>
                        <label for="verify">I verify that the GPOA information provided is accurate and complete.</label>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-actions mt-8">
                        <button type="submit" class="btn-primary">Submit GPOA</button>
                    </div>
                </section>
            </form>
        </div>
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
