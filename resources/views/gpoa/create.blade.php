<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <main class="page-wrapper">
        <div class="gpoa-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow">Submit General Plan of Activities</p>
                    <h1>Submit General Plan of Activities (GPOA)</h1>
                    <p class="page-description">Submit your planned activities for the term. Admin must approve your GPOA before you can request individual activities.</p>
                </div>
                <a href="{{ route('gpoa.index') }}" class="icon-close">×</a>
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
            $oldActivities = old('activities', [[]]);
            if (!is_array($oldActivities) || count($oldActivities) === 0) {
                $oldActivities = [[]];
            }
            $activityLimits = [
                'Symposium' => 4,
                'Convocation' => 4,
                'Religious' => 5,
                'Socio-Cultural' => 5,
                'Sports' => 5,
                'Environmental' => 5,
                'Outreach' => 5,
            ];
            $categoryCounts = [];
            foreach ($oldActivities as $act) {
                if (!empty($act['category'])) {
                    $categoryCounts[$act['category']] = ($categoryCounts[$act['category']] ?? 0) + 1;
                }
            }
        @endphp

        <form action="{{ route('gpoa.store') }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="gpoaForm">
            @csrf

            <section class="form-section step-section active" data-step="1">
                <div class="section-heading">
                    <div>
                        <h2 class="section-title">GPOA Information</h2>
                        <p class="section-description">Provide the term details and college so the OSDW can review your GPOA submission.</p>
                    </div>
                </div>

                <div class="form-row grid-3">
                    <div class="form-group">
                        <label for="colleges">College *</label>
                        @if(!empty($detectedCollege))
                        <div class="detected-value">
                            <span>{{ $detectedCollege }}</span>
                            <small class="help-text">Detected from your student organization.</small>
                        </div>
                        <input type="hidden" name="colleges" value="{{ $detectedCollege }}">
                        @else
                        <select id="colleges" name="colleges" required>
                            <option value="">Select college</option>
                            @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS'] as $c)
                            <option value="{{ $c }}" {{ old('colleges')==$c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="term">Term *</label>
                        @if(auth()->user()->term)
                        <input type="text" disabled value="{{ old('term', auth()->user()->term) }}" class="bg-gray-100">
                        <input type="hidden" name="term" value="{{ old('term', auth()->user()->term) }}">
                        @else
                        <select id="term" name="term" required>
                            <option value="">Select term</option>
                            <option value="1st Term" {{ old('term')=='1st Term' ? 'selected' : '' }}>1st Term</option>
                            <option value="2nd Term" {{ old('term')=='2nd Term' ? 'selected' : '' }}>2nd Term</option>
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

                <div class="form-group">
                    <label for="document_path">GPOA Document (PDF)</label>
                    <input type="file" id="document_path" name="document_path" accept=".pdf">
                    <p class="help-text">Optional: upload your official GPOA document (Max 20MB)</p>
                </div>
            </section>

            <section class="form-section step-section" data-step="2">
                <div class="section-heading">
                    <div>
                        <h2 class="section-title">Planned Activities</h2>
                        <p class="section-description">Add your planned activities, objectives, priority SDGs, and budget details.</p>
                    </div>
                    <button type="button" onclick="addActivityRow()" class="btn-secondary">+ Add Activity</button>
                </div>

                <div class="activity-limits card">
                    <div class="limit-header">
                        <div>
                            <h3>Activity Limits</h3>
                            <p>Use these limits as a guide when planning your activities.</p>
                        </div>
                    </div>
                    <div class="limit-grid">
                        @foreach($activityLimits as $name => $limit)
                        <div class="limit-item">
                            <span>{{ $name }}</span>
                            <strong>{{ $categoryCounts[$name] ?? 0 }}/{{ $limit }}</strong>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div id="activitiesContainer">
                    @foreach($oldActivities as $i => $act)
                    <div class="activity-card" data-index="{{ $i }}">
                        <div class="activity-card-header">
                            <div>
                                <span class="activity-label">Activity #{{ $loop->iteration }}</span>
                                <h3>{{ $act['title'] ?? 'New activity' }}</h3>
                            </div>
                            @if($loop->iteration > 1)
                            <button type="button" onclick="removeActivityRow(this)" class="text-red-600 text-sm">Delete</button>
                            @endif
                        </div>

                        <div class="form-row grid-3">
                            <div class="form-group">
                                <label>Activity Title *</label>
                                <input type="text" name="activities[{{ $i }}][title]" required value="{{ $act['title'] ?? '' }}" placeholder="Leadership Seminar">
                            </div>
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="activities[{{ $i }}][category]" required>
                                    <option value="">Select category</option>
                                    @foreach(['Symposium','Convocation','Religious','Socio-Cultural','Sports','Environmental','Outreach'] as $cat)
                                    <option value="{{ $cat }}" {{ ($act['category'] ?? '')==$cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Target Date *</label>
                                <input type="date" name="activities[{{ $i }}][date]" required value="{{ $act['date'] ?? '' }}">
                            </div>
                        </div>

                        <div class="form-row grid-3">
                            <div class="form-group">
                                <label>Budget (₱) *</label>
                                <input type="number" step="0.01" name="activities[{{ $i }}][estimated_budget]" required value="{{ $act['estimated_budget'] ?? '' }}" placeholder="10000.00">
                            </div>
                            <div class="form-group">
                                <label>Venue *</label>
                                <input type="text" name="activities[{{ $i }}][venue]" required value="{{ $act['venue'] ?? '' }}" placeholder="Activity venue">
                            </div>
                            <div class="form-group">
                                <label>Preceding Activity</label>
                                <select name="activities[{{ $i }}][preceding_activity]">
                                    <option value="">None</option>
                                    @foreach($oldActivities as $j => $other)
                                        @if($j !== $i && !empty($other['title']))
                                        <option value="{{ $other['title'] }}" {{ ($act['preceding_activity'] ?? '')==($other['title'] ?? '') ? 'selected' : '' }}>{{ $other['title'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row grid-3">
                            <div class="form-group">
                                <label>Target Participants *</label>
                                <input type="text" name="activities[{{ $i }}][target_participants]" required value="{{ $act['target_participants'] ?? '' }}" placeholder="Student leaders, officers, active members">
                            </div>
                            <div class="form-group">
                                <label>Source of Funds *</label>
                                <select name="activities[{{ $i }}][source_of_funds]" required>
                                    <option value="">Select source</option>
                                    @foreach(['Student Trust Funds','Membership/Registration Fees','Sponsorships/Donations','University Subsidy'] as $source)
                                    <option value="{{ $source }}" {{ ($act['source_of_funds'] ?? '')==$source ? 'selected' : '' }}>{{ $source }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Person / Committee in Charge *</label>
                                <input type="text" name="activities[{{ $i }}][person_in_charge]" required value="{{ $act['person_in_charge'] ?? '' }}" placeholder="Officer or committee responsible">
                            </div>
                        </div>

                        <div class="form-row grid-2">
                            <div class="form-group">
                                <label>Objectives *</label>
                                <textarea name="activities[{{ $i }}][objectives]" required rows="4" placeholder="Enter measurable objectives">{{ $act['objectives'] ?? '' }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>SDGs (1-17) *</label>
                                @php
                                    $sdgs = [
                                        1 => 'No Poverty',
                                        2 => 'Zero Hunger',
                                        3 => 'Good Health and Well-being',
                                        4 => 'Quality Education',
                                        5 => 'Gender Equality',
                                        6 => 'Clean Water and Sanitation',
                                        7 => 'Affordable and Clean Energy',
                                        8 => 'Decent Work and Economic Growth',
                                        9 => 'Industry, Innovation and Infrastructure',
                                        10 => 'Reduced Inequality',
                                        11 => 'Sustainable Cities and Communities',
                                        12 => 'Responsible Consumption and Production',
                                        13 => 'Climate Action',
                                        14 => 'Life Below Water',
                                        15 => 'Life on Land',
                                        16 => 'Peace, Justice and Strong Institutions',
                                        17 => 'Partnerships for the Goals',
                                    ];
                                    $selectedSdgs = old('activities.'.$i.'.sdgs', $act['sdgs'] ?? []);
                                    if (!is_array($selectedSdgs)) {
                                        $selectedSdgs = json_decode($selectedSdgs, true);
                                    }
                                    $selectedSdgs = is_array($selectedSdgs) ? $selectedSdgs : [];
                                @endphp
                                <div class="sdg-dropdown" data-index="{{ $i }}">
                                    <button type="button" class="sdg-dropdown-toggle">{{ count($selectedSdgs) ? 'Selected: ' . implode(', ', $selectedSdgs) : 'Select SDGs' }}</button>
                                    <div class="sdg-dropdown-menu">
                                        @foreach($sdgs as $number => $title)
                                        <label class="sdg-option">
                                            <input type="checkbox" name="activities[{{ $i }}][sdgs][]" value="{{ $number }}" {{ in_array($number, $selectedSdgs) ? 'checked' : '' }}>
                                            <span>SDG {{ $number }}: {{ $title }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <small class="help-text">Select 1 to 17 SDGs</small>
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
                    <button type="submit" class="btn-primary">Submit GPOA</button>
                </div>
            </section>
        </form>
    </main>

    <template id="activityRowTemplate">
        <div class="activity-card" data-index="__INDEX__">
            <div class="activity-card-header">
                <div>
                    <span class="activity-label">Activity #<span class="row-num">__ROW__</span></span>
                    <h3>New activity</h3>
                </div>
                <button type="button" onclick="removeActivityRow(this)" class="text-red-600 text-sm">Delete</button>
            </div>

            <div class="form-row grid-3">
                <div class="form-group">
                    <label>Activity Title *</label>
                    <input type="text" name="activities[__INDEX__][title]" required placeholder="Activity title">
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="activities[__INDEX__][category]" required>
                        <option value="">Select category</option>
                        @foreach(['Symposium','Convocation','Religious','Socio-Cultural','Sports','Environmental','Outreach'] as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Date *</label>
                    <input type="date" name="activities[__INDEX__][date]" required>
                </div>
            </div>

            <div class="form-row grid-3">
                <div class="form-group">
                    <label>Budget (₱) *</label>
                    <input type="number" step="0.01" name="activities[__INDEX__][estimated_budget]" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Venue *</label>
                    <input type="text" name="activities[__INDEX__][venue]" required placeholder="Activity venue">
                </div>
                <div class="form-group">
                    <label>Preceding Activity</label>
                    <select name="activities[__INDEX__][preceding_activity]">
                        <option value="">None</option>
                    </select>
                </div>
            </div>

            <div class="form-row grid-3">
                <div class="form-group">
                    <label>Target Participants *</label>
                    <input type="text" name="activities[__INDEX__][target_participants]" required placeholder="Target beneficiaries">
                </div>
                <div class="form-group">
                    <label>Source of Funds *</label>
                    <select name="activities[__INDEX__][source_of_funds]" required>
                        <option value="">Select source</option>
                        @foreach(['Student Trust Funds','Membership/Registration Fees','Sponsorships/Donations','University Subsidy'] as $source)
                        <option value="{{ $source }}">{{ $source }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Person / Committee in Charge *</label>
                    <input type="text" name="activities[__INDEX__][person_in_charge]" required placeholder="Officer or committee responsible">
                </div>
            </div>

            <div class="form-row grid-2">
                <div class="form-group">
                    <label>Objectives *</label>
                    <textarea name="activities[__INDEX__][objectives]" required rows="4" placeholder="Enter measurable objectives"></textarea>
                </div>
                <div class="form-group">
                    <label>SDGs (1-17) *</label>
                    <div class="sdg-dropdown" data-index="__INDEX__">
                        <button type="button" class="sdg-dropdown-toggle">Select SDGs</button>
                        <div class="sdg-dropdown-menu">
                            @php
                                $sdgs = [
                                    1 => 'No Poverty',
                                    2 => 'Zero Hunger',
                                    3 => 'Good Health and Well-being',
                                    4 => 'Quality Education',
                                    5 => 'Gender Equality',
                                    6 => 'Clean Water and Sanitation',
                                    7 => 'Affordable and Clean Energy',
                                    8 => 'Decent Work and Economic Growth',
                                    9 => 'Industry, Innovation and Infrastructure',
                                    10 => 'Reduced Inequality',
                                    11 => 'Sustainable Cities and Communities',
                                    12 => 'Responsible Consumption and Production',
                                    13 => 'Climate Action',
                                    14 => 'Life Below Water',
                                    15 => 'Life on Land',
                                    16 => 'Peace, Justice and Strong Institutions',
                                    17 => 'Partnerships for the Goals',
                                ];
                            @endphp
                            @foreach($sdgs as $number => $title)
                            <label class="sdg-option">
                                <input type="checkbox" name="activities[__INDEX__][sdgs][]" value="{{ $number }}">
                                <span>SDG {{ $number }}: {{ $title }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <small class="help-text">Select 1 to 17 SDGs</small>
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

        function addActivityRow() {
            const container = document.getElementById('activitiesContainer');
            const template = document.getElementById('activityRowTemplate').innerHTML;
            const html = template
                .replace(/__INDEX__/g, activityIndex)
                .replace(/__ROW__/g, activityIndex + 1);
            container.insertAdjacentHTML('beforeend', html);
            const newDropdown = container.querySelector('.activity-card:last-child .sdg-dropdown');
            setupSdgDropdown(newDropdown);
            activityIndex++;
            renumberRows();
        }

        function removeActivityRow(btn) {
            btn.closest('.activity-card').remove();
            renumberRows();
        }

        function renumberRows() {
            document.querySelectorAll('.activity-card').forEach((card, index) => {
                const label = card.querySelector('.activity-label');
                if (label) {
                    label.innerHTML = 'Activity #' + (index + 1);
                }
            });
        }

        function setupSdgDropdown(dropdown) {
            if (!dropdown) {
                return;
            }

            const toggle = dropdown.querySelector('.sdg-dropdown-toggle');
            const menu = dropdown.querySelector('.sdg-dropdown-menu');
            const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');

            if (!toggle || !menu || !checkboxes.length) {
                return;
            }

            toggle.addEventListener('click', () => {
                menu.classList.toggle('active');
            });

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    const selected = Array.from(checkboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.value);
                    toggle.textContent = selected.length ? 'Selected: ' + selected.join(', ') : 'Select SDGs (1-11)';
                });
            });
        }

        function setupSdgDropdowns() {
            document.querySelectorAll('.sdg-dropdown').forEach(setupSdgDropdown);

            document.addEventListener('click', (event) => {
                document.querySelectorAll('.sdg-dropdown-menu.active').forEach(menu => {
                    if (!menu.closest('.sdg-dropdown').contains(event.target)) {
                        menu.classList.remove('active');
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', setupSdgDropdowns);
    </script>
</x-app-layout>
