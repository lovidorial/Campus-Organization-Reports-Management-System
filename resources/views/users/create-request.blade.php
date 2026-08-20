<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <main class="page-wrapper">
        <div class="gpoa-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow">Activity Request</p>
                    <h1>Request an Activity</h1>
                        <p class="page-description">Submit a detailed activity request under your approved GPOA submission.</p>
            <form action="{{ route('activity-requests.store') }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="requestForm">
                @csrf

                {{-- Organization Information (read-only reference) --}}
                @php
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
                        @if($availableGpoas->count() > 1)
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

                    <input type="hidden" name="gpoa_id" value="{{ $gpoa->id }}">

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
                            <input id="title" type="text" name="title" value="{{ old('title') }}" required>
                            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select category</option>
                                <option value="Symposium" {{ old('category') == 'Symposium' ? 'selected' : '' }}>Symposium</option>
                                <option value="Convocation" {{ old('category') == 'Convocation' ? 'selected' : '' }}>Convocation</option>
                                <option value="Religious Activity" {{ old('category') == 'Religious Activity' ? 'selected' : '' }}>Religious Activity</option>
                                <option value="Socio-Cultural and Sports" {{ old('category') == 'Socio-Cultural and Sports' ? 'selected' : '' }}>Socio-Cultural and Sports</option>
                                <option value="Makakalikasan (Clean and Green)" {{ old('category') == 'Makakalikasan (Clean and Green)' ? 'selected' : '' }}>Makakalikasan (Clean and Green)</option>
                                <option value="Extension Services Conducted" {{ old('category') == 'Extension Services Conducted' ? 'selected' : '' }}>Extension Services Conducted</option>
                            </select>
                            @error('category')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-1 mt-6">
                        <div class="form-group">
                            <div class="sdg-label-row">
                                <label for="sdgCheckboxes">SDGs *</label>
                                <div id="sdgSummary" class="sdg-summary-badges" aria-live="polite" aria-atomic="true">
                                    <span class="sdg-placeholder">No SDGs selected yet</span>
                                </div>
                            </div>

                            @php
                                $sdgList = [
                                    1 => 'No Poverty',
                                    2 => 'Zero Hunger',
                                    3 => 'Good Health',
                                    4 => 'Quality Education',
                                    5 => 'Gender Equality',
                                    6 => 'Clean Water',
                                    7 => 'Affordable Energy',
                                    8 => 'Decent Work',
                                    9 => 'Industry, Innovation',
                                    10 => 'Reduced Inequality',
                                    11 => 'Sustainable Cities',
                                    12 => 'Responsible Consumption',
                                    13 => 'Climate Action',
                                    14 => 'Life Below Water',
                                    15 => 'Life on Land',
                                    16 => 'Peace/Justice',
                                    17 => 'Partnerships'
                                ];
                                $oldSdgs = old('sdgs');
                                if (is_array($oldSdgs)) {
                                    $oldNums = $oldSdgs;
                                } else {
                                    $oldSdgs = $oldSdgs ?: '';
                                    preg_match_all('/\b(\d{1,2})\b/', $oldSdgs, $m);
                                    $oldNums = $m[1] ?? [];
                                }
                            @endphp

                            <style>
                                .sdg-label-row {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    gap: 12px;
                                    margin-bottom: 12px;
                                }
                                .sdg-label-row label {
                                    margin: 0;
                                    flex-shrink: 0;
                                }
                                .sdg-summary-badges {
                                    display: flex;
                                    gap: 6px;
                                    flex-wrap: wrap;
                                    flex: 1;
                                    padding: 6px 8px;
                                    background: #F3F4F6;
                                    border-radius: 4px;
                                    min-height: 24px;
                                    align-items: center;
                                }
                                .sdg-badge {
                                    background: #3B82F6;
                                    color: white;
                                    padding: 2px 8px;
                                    border-radius: 999px;
                                    font-size: 0.75rem;
                                    font-weight: 600;
                                    display: inline-block;
                                    white-space: nowrap;
                                }
                                .sdg-placeholder {
                                    color: #9CA3AF;
                                    font-size: 0.875rem;
                                }
                                .sdg-checkbox-list {
                                    display: flex;
                                    flex-direction: column;
                                    gap: 8px;
                                    max-height: 240px;
                                    overflow-y: auto;
                                    padding: 8px 0;
                                    border: 1px solid #D1D5DB;
                                    border-radius: 4px;
                                    background: #FFFFFF;
                                    padding: 8px;
                                    margin-bottom: 8px;
                                }
                                .sdg-checkbox-item {
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
                                    padding: 4px;
                                    cursor: pointer;
                                    user-select: none;
                                }
                                .sdg-checkbox-item input[type="checkbox"] {
                                    cursor: pointer;
                                    accent-color: #3B82F6;
                                }
                                .sdg-checkbox-item input[type="checkbox"]:disabled {
                                    cursor: not-allowed;
                                    opacity: 0.5;
                                }
                                .sdg-checkbox-item label {
                                    cursor: pointer;
                                    margin: 0;
                                    font-size: 0.875rem;
                                    flex: 1;
                                }
                                .sdg-checkbox-item input[type="checkbox"]:disabled + label {
                                    opacity: 0.5;
                                    cursor: not-allowed;
                                }
                                .sdg-count-message {
                                    font-size: 0.75rem;
                                    color: #6B7280;
                                    display: block;
                                    margin-bottom: 8px;
                                    font-weight: 500;
                                }
                                .sdg-helper-text {
                                    font-size: 0.75rem;
                                    color: #6B7280;
                                    display: block;
                                    margin-bottom: 8px;
                                    font-weight: 500;
                                }
                                .sdg-validation-error {
                                    color: #DC2626;
                                    font-size: 0.875rem;
                                    margin-top: 6px;
                                    display: none;
                                }
                                .sdg-validation-error.show {
                                    display: block;
                                }
                            </style>

                            <div class="sdg-checkbox-list" id="sdgCheckboxes">
                                @foreach($sdgList as $num => $label)
                                    <div class="sdg-checkbox-item">
                                        <input type="checkbox" id="sdg{{ $num }}" name="sdgs[]" value="{{ $num }}" 
                                               {{ in_array((string)$num, $oldNums) ? 'checked' : '' }}>
                                        <label for="sdg{{ $num }}">SDG {{ $num }} - {{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div id="sdgHelperText" class="sdg-helper-text">Must select 1-8 SDGs aligned with the activity</div>
                            <div id="sdgValidationError" class="sdg-validation-error"></div>

                            @error('sdgs')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                        <div class="form-group mt-6">
                            <label for="objectives">Objectives *</label>
                            <textarea id="objectives" name="objectives" rows="4" required>{{ old('objectives') }}</textarea>
                        @error('objectives')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group mt-6">
                        <label for="expected_outcome">Expected Outcome *</label>
                        <textarea id="expected_outcome" name="expected_outcome" rows="4" required>{{ old('expected_outcome') }}</textarea>
                        @error('expected_outcome')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group mt-6">
                        <label for="plan_key_strategy">Plan / Key Strategy *</label>
                        <textarea id="plan_key_strategy" name="plan_key_strategy" rows="4" required>{{ old('plan_key_strategy') }}</textarea>
                        @error('plan_key_strategy')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="date">Date *</label>
                            <input id="date" type="date" name="date" value="{{ old('date') }}" required>
                            @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="venue">Venue *</label>
                            <input id="venue" type="text" name="venue" value="{{ old('venue') }}" required>
                            @error('venue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="target_participants">Target Participants *</label>
                            <input id="target_participants" type="text" name="target_participants" value="{{ old('target_participants') }}" required>
                            @error('target_participants')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="person_in_charge">Person in Charge *</label>
                            <input id="person_in_charge" type="text" name="person_in_charge" value="{{ old('person_in_charge') }}" required>
                            @error('person_in_charge')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="facilities_materials">Facilities / Materials *</label>
                            <input id="facilities_materials" type="text" name="facilities_materials" value="{{ old('facilities_materials') }}" required>
                            @error('facilities_materials')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="estimated_budget">Estimated Budget *</label>
                            <input id="estimated_budget" type="number" step="0.01" name="estimated_budget" value="{{ old('estimated_budget') }}" required>
                            @error('estimated_budget')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 mt-6">
                        <div class="form-group">
                            <label for="source_of_funds">Source of Funds *</label>
                            <select id="source_of_funds" name="source_of_funds" required>
                                <option value="">Select source of funds</option>
                                <option value="Organization Funds" {{ old('source_of_funds') == 'Organization Funds' ? 'selected' : '' }}>Organization Funds</option>
                                <option value="Student Council Funds" {{ old('source_of_funds') == 'Student Council Funds' ? 'selected' : '' }}>Student Council Funds</option>
                                <option value="School-Generated Funds / MOOE" {{ old('source_of_funds') == 'School-Generated Funds / MOOE' ? 'selected' : '' }}>School-Generated Funds / MOOE</option>
                                <option value="Sponsorship / Donations" {{ old('source_of_funds') == 'Sponsorship / Donations' ? 'selected' : '' }}>Sponsorship / Donations</option>
                                <option value="Others" {{ old('source_of_funds') == 'Others' ? 'selected' : '' }}>Others</option>
                            </select>
                            @error('source_of_funds')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="preceding_activity">Preceding Activity</label>
                            <input id="preceding_activity" type="text" name="preceding_activity" value="{{ old('preceding_activity') }}">
                            @error('preceding_activity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-group mt-6">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" rows="4">{{ old('remarks') }}</textarea>
                        @error('remarks')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-row grid-2 mt-6">
                        <div class="form-group">
                            <label for="description">Additional Notes</label>
                            <textarea name="description" id="description" rows="4">{{ old('description') }}</textarea>
                            @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="participants_count">Participants Override</label>
                            <input id="participants_count" type="number" name="participants_count" min="1" value="{{ old('participants_count') }}" placeholder="Leave blank to use planned amount">
                            @error('participants_count')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-group mt-6">
                        <label for="communication_letter">Communication Letter (PDF) *</label>
                        <input id="communication_letter" type="file" name="communication_letter" accept=".pdf" required>
                        @error('communication_letter')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </section>

                <button type="submit" class="btn-secondary">Submit Activity Request</button>
            </form>
        </div>
    </main>
</x-app-layout>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const sdgCheckboxContainer = document.getElementById('sdgCheckboxes');
    const sdgSummary = document.getElementById('sdgSummary');
    const sdgValidationError = document.getElementById('sdgValidationError');
    const requestForm = document.getElementById('requestForm');
    const MAX_SDGS = 8;
    const MIN_SDGS = 1;

    if(!sdgCheckboxContainer) return;

    const allCheckboxes = sdgCheckboxContainer.querySelectorAll('input[type="checkbox"]');

    /**
     * Update the summary badges area with currently selected SDGs
     */
    function updateSdgSummary() {
        const checkedBoxes = Array.from(allCheckboxes).filter(cb => cb.checked);
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

    /**
     * Validate SDG selection on form submit
     */
    function validateSdgSelection() {
        const checkedBoxes = Array.from(allCheckboxes).filter(cb => cb.checked);
        const count = checkedBoxes.length;

        if (count < MIN_SDGS || count > MAX_SDGS) {
            sdgValidationError.textContent = `Please select between ${MIN_SDGS} and ${MAX_SDGS} SDGs. You have selected ${count}.`;
            sdgValidationError.classList.add('show');
            return false;
        }

        clearValidationError();
        return true;
    }

    // Add event listeners to all checkboxes
    allCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSdgSummary();
            clearValidationError();
        });
    });

    // Validate on form submit
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            if (!validateSdgSelection()) {
                e.preventDefault();
                sdgCheckboxContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Initialize on page load - update badges to reflect any pre-checked checkboxes
    updateSdgSummary();
});
</script>
@endpush
