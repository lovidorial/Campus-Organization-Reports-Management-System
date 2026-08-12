<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <main class="page-wrapper">
        <div class="page-header">
            <div>
                <p class="eyebrow">Edit General Plan of Activities</p>
                <h1>Edit GPOA (Pending Review)</h1>
                <p class="page-description">Update your GPOA while it is pending OSDW review. Make sure your planned activities reflect final objectives, budgets, and sustainability goals.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary btn-close">Back to Dashboard</a>
        </div>

        <div class="stepper">
            <button type="button" class="step active" data-step="1">1 GPOA Information</button>
            <button type="button" class="step" data-step="2">2 Activities</button>
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

        @php
            $oldActivities = old('activities', $gpoa->activities->toArray());
            if (!is_array($oldActivities) || count($oldActivities) === 0) {
                $oldActivities = [[]];
            }
        @endphp

        <form action="{{ route('gpoa.update', $gpoa) }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="gpoaForm">
            @csrf
            @method('PUT')

            <section class="form-section step-section active" data-step="1">
                <div class="section-heading">
                    <div>
                        <h2 class="section-title">GPOA Information</h2>
                        <p class="section-description">Update the college and document details for your current submission.</p>
                    </div>
                    <button type="button" class="btn-primary" onclick="showStep(2)">Next: Edit Activities →</button>
                </div>

                <div class="form-row grid-3">
                    <div class="form-group">
                        <label for="colleges">College *</label>
                        <select id="colleges" name="colleges" required>
                            @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS'] as $c)
                                <option value="{{ $c }}" {{ old('colleges', $gpoa->college)==$c ? 'selected' : '' }}>{{ $c }}</option>
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

                <div class="form-group">
                    <label for="document_path">GPOA Document (PDF)</label>
                    <input type="file" id="document_path" name="document_path" accept=".pdf">
                    @if($gpoa->document_path)
                        <p class="help-text">Current document uploaded. Leave empty to keep existing.</p>
                    @endif
                </div>
            </section>

            <section class="form-section step-section" data-step="2">
                <div class="section-heading">
                    <div>
                        <h2 class="section-title">Planned Activities</h2>
                        <p class="section-description">Edit your planned activities for the term.</p>
                    </div>
                    <button type="button" onclick="addActivityRow()" class="btn-secondary">+ Add Another Activity</button>
                </div>

                <div id="activitiesContainer">
                    @foreach($oldActivities as $i => $act)
                        @php
                            $sdgs = [1 => 'No Poverty',2 => 'Zero Hunger',3 => 'Good Health and Well-being',4 => 'Quality Education',5 => 'Gender Equality',6 => 'Clean Water and Sanitation',7 => 'Affordable and Clean Energy',8 => 'Decent Work and Economic Growth',9 => 'Industry, Innovation and Infrastructure',10 => 'Reduced Inequality',11 => 'Sustainable Cities and Communities',12 => 'Responsible Consumption and Production',13 => 'Climate Action',14 => 'Life Below Water',15 => 'Life on Land',16 => 'Peace, Justice and Strong Institutions',17 => 'Partnerships for the Goals'];
                            $selectedSdgs = old('activities.'.$i.'.sdgs', $act['sdgs'] ?? []);
                            if (!is_array($selectedSdgs)) { $selectedSdgs = json_decode($selectedSdgs, true); }
                            $selectedSdgs = is_array($selectedSdgs) ? $selectedSdgs : [];
                        @endphp
                        <div class="activity-card" data-index="{{ $i }}">
                            <div class="activity-card-header">
                                <div class="activity-label">Activity #{{ $i + 1 }}</div>
                                <button type="button" class="remove-row-btn" onclick="removeActivityRow(this)">Remove</button>
                            </div>

                            <div class="form-group">
                                <label>Program/Activity/Project *</label>
                                <input type="text" name="activities[{{ $i }}][title]" value="{{ $act['title'] ?? '' }}" placeholder="e.g. Leadership Training Seminar">
                            </div>

                            <div class="form-row grid-3">
                                <div class="form-group">
                                    <label>Category *</label>
                                    <select name="activities[{{ $i }}][category]">
                                        <option value="">Select category</option>
                                        @foreach(['Symposium','Convocation','Religious','Socio-Cultural','Sports','Environmental','Outreach'] as $cat)
                                            <option value="{{ $cat }}" {{ ($act['category'] ?? '')==$cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Activity Level *</label>
                                    <select name="activities[{{ $i }}][activity_level]" required>
                                        <option value="">Select level</option>
                                        @foreach(['Local','University','Regional','National'] as $level)
                                            <option value="{{ $level }}" {{ ($act['activity_level'] ?? '')==$level ? 'selected' : '' }}>{{ $level }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>SDG's Addressed *</label>
                                    <div class="sdg-picker" data-index="{{ $i }}">
                                        <button type="button" class="sdg-picker-toggle">{{ count($selectedSdgs) ? 'Selected: ' . implode(', ', array_map(fn($v) => 'SDG '.$v, $selectedSdgs)) : 'e.g. SDG 4, SDG 17' }}</button>
                                        <div class="sdg-picker-menu">
                                            @foreach($sdgs as $value => $label)
                                                <label class="sdg-picker-option">
                                                    <input type="checkbox" name="activities[{{ $i }}][sdgs][]" value="{{ $value }}" {{ in_array($value, $selectedSdgs) ? 'checked' : '' }}>
                                                    <span>SDG {{ $value }}: {{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section-label">Planning</div>
                            <div class="form-group">
                                <label>Objectives *</label>
                                <textarea name="activities[{{ $i }}][objectives]" rows="3" placeholder="e.g. Improve leadership capacity and increase student engagement ...">{{ $act['objectives'] ?? '' }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Expected Outcome *</label>
                                <textarea name="activities[{{ $i }}][expected_outcome]" rows="3" placeholder="e.g. Increased awareness, confidence, and participation ...">{{ $act['expected_outcome'] ?? '' }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Plan/Key Strategy *</label>
                                <textarea name="activities[{{ $i }}][plan_key_strategy]" rows="3" placeholder="e.g. Conduct workshops, peer mentoring, and collaboration sessions ...">{{ $act['plan_key_strategy'] ?? '' }}</textarea>
                            </div>

                            <div class="section-label">Logistics</div>
                            <div class="form-row grid-4">
                                <div class="form-group">
                                    <label>Time Frame *</label>
                                    <input type="date" name="activities[{{ $i }}][date]" value="{{ $act['date'] ?? '' }}">
                                </div>

                                <div class="form-group">
                                    <label>Venue *</label>
                                    <input type="text" name="activities[{{ $i }}][venue]" value="{{ $act['venue'] ?? '' }}" placeholder="e.g. Student Center, AVR">
                                </div>

                                <div class="form-group">
                                    <label>Target Participants *</label>
                                    <input type="text" name="activities[{{ $i }}][target_participants]" value="{{ $act['target_participants'] ?? '' }}" placeholder="e.g. Student leaders and officers">
                                </div>

                                <div class="form-group">
                                    <label>Persons Involved *</label>
                                    <input type="text" name="activities[{{ $i }}][person_in_charge]" value="{{ $act['person_in_charge'] ?? '' }}" placeholder="e.g. Faculty Adviser, Student Officers">
                                </div>
                            </div>

                            <div class="section-label">Resources</div>
                            <div class="form-row grid-3">
                                <div class="form-group">
                                    <label>Facilities/Materials *</label>
                                    <input type="text" name="activities[{{ $i }}][facilities_materials]" value="{{ $act['facilities_materials'] ?? '' }}" placeholder="e.g. AVR, projector, laptop">
                                </div>

                                <div class="form-group">
                                    <label>Budget Allocation *</label>
                                    <input type="number" step="0.01" name="activities[{{ $i }}][estimated_budget]" value="{{ $act['estimated_budget'] ?? '' }}" placeholder="0.00">
                                </div>

                                <div class="form-group">
                                    <label>Remarks</label>
                                    <input type="text" name="activities[{{ $i }}][remarks]" value="{{ $act['remarks'] ?? '' }}" placeholder="e.g. For certificate and tokens">
                                </div>
                            </div>

                            <div class="form-row grid-2">
                                <div class="form-group">
                                    <label>Source of Funds *</label>
                                    <select name="activities[{{ $i }}][source_of_funds]">
                                        <option value="">Select source</option>
                                        <option value="Organization Funds" {{ ($act['source_of_funds'] ?? '')=='Organization Funds' ? 'selected' : '' }}>Organization Funds</option>
                                        <option value="University Allocation" {{ ($act['source_of_funds'] ?? '')=='University Allocation' ? 'selected' : '' }}>University Allocation</option>
                                        <option value="External Sponsorship" {{ ($act['source_of_funds'] ?? '')=='External Sponsorship' ? 'selected' : '' }}>External Sponsorship</option>
                                        <option value="Partnership Funds" {{ ($act['source_of_funds'] ?? '')=='Partnership Funds' ? 'selected' : '' }}>Partnership Funds</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Preceding Activity</label>
                                    <select name="activities[{{ $i }}][preceding_activity]">
                                        <option value="">None</option>
                                        @foreach(['Orientation','Leadership Training','Community Outreach','Volunteer Day','General Assembly'] as $prev)
                                            <option value="{{ $prev }}" {{ ($act['preceding_activity'] ?? '')==$prev ? 'selected' : '' }}>{{ $prev }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="verify" name="verify" required>
                    <label for="verify">I verify that the GPOA information provided is accurate and complete</label>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="showStep(1)">← Back</button>
                    <button type="submit" class="btn-primary">Update GPOA</button>
                </div>
            </section>
        </form>
    </main>

    <template id="activityRowTemplate">
        <div class="activity-card" data-index="__INDEX__">
            <div class="activity-card-header">
                <div class="activity-label">Activity #__INDEX__</div>
                <button type="button" class="remove-row-btn" onclick="removeActivityRow(this)">Remove</button>
            </div>

            <div class="form-group">
                <label>Program/Activity/Project *</label>
                <input type="text" name="activities[__INDEX__][title]" placeholder="e.g. Leadership Training Seminar">
            </div>

            <div class="form-row grid-3">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="activities[__INDEX__][category]">
                        <option value="">Select category</option>
                        @foreach(['Symposium','Convocation','Religious','Socio-Cultural','Sports','Environmental','Outreach'] as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Activity Level *</label>
                    <select name="activities[__INDEX__][activity_level]" required>
                        <option value="">Select level</option>
                        @foreach(['Local','University','Regional','National'] as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>SDG's Addressed *</label>
                    <div class="sdg-picker" data-index="__INDEX__">
                        <button type="button" class="sdg-picker-toggle">e.g. SDG 4, SDG 17</button>
                        <div class="sdg-picker-menu">
                            @php
                                $sdgs = [1 => 'No Poverty',2 => 'Zero Hunger',3 => 'Good Health and Well-being',4 => 'Quality Education',5 => 'Gender Equality',6 => 'Clean Water and Sanitation',7 => 'Affordable and Clean Energy',8 => 'Decent Work and Economic Growth',9 => 'Industry, Innovation and Infrastructure',10 => 'Reduced Inequality',11 => 'Sustainable Cities and Communities',12 => 'Responsible Consumption and Production',13 => 'Climate Action',14 => 'Life Below Water',15 => 'Life on Land',16 => 'Peace, Justice and Strong Institutions',17 => 'Partnerships for the Goals'];
                            @endphp
                            @foreach($sdgs as $value => $label)
                                <label class="sdg-picker-option">
                                    <input type="checkbox" name="activities[__INDEX__][sdgs][]" value="{{ $value }}" @if($loop->first) required @endif>
                                    <span>SDG {{ $value }}: {{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-label">Planning</div>
            <div class="form-group">
                <label>Objectives *</label>
                <textarea name="activities[__INDEX__][objectives]" rows="3" placeholder="e.g. Improve leadership capacity and increase student engagement ..."></textarea>
            </div>

            <div class="form-group">
                <label>Expected Outcome *</label>
                <textarea name="activities[__INDEX__][expected_outcome]" rows="3" placeholder="e.g. Increased awareness, confidence, and participation ..."></textarea>
            </div>

            <div class="form-group">
                <label>Plan/Key Strategy *</label>
                <textarea name="activities[__INDEX__][plan_key_strategy]" rows="3" placeholder="e.g. Conduct workshops, peer mentoring, and collaboration sessions ..."></textarea>
            </div>

            <div class="section-label">Logistics</div>
            <div class="form-row grid-4">
                <div class="form-group">
                    <label>Time Frame *</label>
                    <input type="date" name="activities[__INDEX__][date]">
                </div>

                <div class="form-group">
                    <label>Venue *</label>
                    <input type="text" name="activities[__INDEX__][venue]" placeholder="e.g. Student Center, AVR">
                </div>

                <div class="form-group">
                    <label>Target Participants *</label>
                    <input type="text" name="activities[__INDEX__][target_participants]" placeholder="e.g. Student leaders and officers">
                </div>

                <div class="form-group">
                    <label>Persons Involved *</label>
                    <input type="text" name="activities[__INDEX__][person_in_charge]" placeholder="e.g. Faculty Adviser, Student Officers">
                </div>
            </div>

            <div class="section-label">Resources</div>
            <div class="form-row grid-3">
                <div class="form-group">
                    <label>Facilities/Materials *</label>
                    <input type="text" name="activities[__INDEX__][facilities_materials]" placeholder="e.g. AVR, projector, laptop">
                </div>

                <div class="form-group">
                    <label>Budget Allocation *</label>
                    <input type="number" step="0.01" name="activities[__INDEX__][estimated_budget]" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="activities[__INDEX__][remarks]" placeholder="e.g. For certificate and tokens">
                </div>
            </div>

            <div class="form-row grid-2">
                <div class="form-group">
                    <label>Source of Funds *</label>
                    <select name="activities[__INDEX__][source_of_funds]">
                        <option value="">Select source</option>
                        <option value="Organization Funds">Organization Funds</option>
                        <option value="University Allocation">University Allocation</option>
                        <option value="External Sponsorship">External Sponsorship</option>
                        <option value="Partnership Funds">Partnership Funds</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Preceding Activity</label>
                    <select name="activities[__INDEX__][preceding_activity]">
                        <option value="">None</option>
                        @foreach(['Orientation','Leadership Training','Community Outreach','Volunteer Day','General Assembly'] as $prev)
                            <option value="{{ $prev }}">{{ $prev }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </template>

    <script>
        let activityIndex = {{ count($oldActivities) }};

        function showStep(step) {
            document.querySelectorAll('.stepper .step').forEach(button => {
                button.classList.toggle('active', button.dataset.step == step);
            });
            document.querySelectorAll('.step-section').forEach(section => {
                section.classList.toggle('active', section.dataset.step == step);
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function renumberRows() {
            const cards = document.querySelectorAll('.activity-card');
            cards.forEach((card, index) => {
                card.dataset.index = index;
                const label = card.querySelector('.activity-label');
                if (label) {
                    label.textContent = `Activity #${index + 1}`;
                }

                const sdgPicker = card.querySelector('.sdg-picker');
                if (sdgPicker) {
                    sdgPicker.dataset.index = index;
                }

                card.querySelectorAll('input, select, textarea').forEach((field) => {
                    if (!field.name || !field.name.includes('activities[')) {
                        return;
                    }
                    field.name = field.name.replace(/activities\[\d+\]/, `activities[${index}]`);
                });
            });
        }

        function addActivityRow() {
            const container = document.getElementById('activitiesContainer');
            const template = document.getElementById('activityRowTemplate').innerHTML;
            const html = template.replace(/__INDEX__/g, activityIndex);
            container.insertAdjacentHTML('beforeend', html);
            setupSdgDropdown();
            renumberRows();
            activityIndex++;
        }

        function removeActivityRow(button) {
            const card = button.closest('.activity-card');
            if (card) {
                card.remove();
                renumberRows();
            }
        }

        function setupSdgDropdown() {
            document.querySelectorAll('.sdg-picker').forEach((picker) => {
                const toggle = picker.querySelector('.sdg-picker-toggle');
                const menu = picker.querySelector('.sdg-picker-menu');
                const checkboxes = picker.querySelectorAll('input[type="checkbox"]');

                if (!toggle || !menu || !checkboxes.length) {
                    return;
                }

                toggle.onclick = (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const isOpen = menu.classList.contains('active');
                    document.querySelectorAll('.sdg-picker-menu.active').forEach((activeMenu) => {
                        if (activeMenu !== menu) {
                            activeMenu.classList.remove('active');
                        }
                    });
                    menu.classList.toggle('active', !isOpen);
                };

                checkboxes.forEach((checkbox) => {
                    checkbox.onchange = () => {
                        const selected = Array.from(checkboxes)
                            .filter((cb) => cb.checked)
                            .map((cb) => 'SDG ' + cb.value);
                        toggle.textContent = selected.length ? 'Selected: ' + selected.join(', ') : 'e.g. SDG 4, SDG 17';
                    };
                });
            });

            document.onclick = function (event) {
                document.querySelectorAll('.sdg-picker-menu.active').forEach((menu) => {
                    if (!menu.closest('.sdg-picker').contains(event.target)) {
                        menu.classList.remove('active');
                    }
                });
            };
        }

        document.addEventListener('DOMContentLoaded', setupSdgDropdown);
    </script>
</x-app-layout>
