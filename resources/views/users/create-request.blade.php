<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <main class="page-wrapper">
        <div class="gpoa-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow">Activity Request</p>
                    <h1>Request an Activity</h1>
                    <p class="page-description">Choose an activity from your approved GPOA and submit the request with matching details.</p>
                </div>
                <a href="{{ route('activity-requests.index') }}" class="icon-close">×</a>
            </div>

            <form action="{{ route('activity-requests.store') }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="requestForm">
                @csrf

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
                            <h2 class="section-title">Choose Planned Activity</h2>
                            <p class="section-description">Select one approved GPOA line item. Request data is locked to the chosen activity's planning details.</p>
                        </div>
                    </div>

                    @if($lineItems->isEmpty())
                        <div class="activity-card bg-yellow-50 border-yellow-200 text-yellow-800">
                            <p>No pending GPOA activities are available for request in this GPOA.</p>
                        </div>
                    @else
                        @php
                            $selectedLineItem = $lineItems->firstWhere('id', old('gpoa_activity_id', $lineItems->first()->id));
                        @endphp

                        <div class="form-group">
                            <label>GPOA Activity *</label>
                            <select name="gpoa_activity_id" id="gpoa_activity_id" required onchange="fillFromGpoa(this)">
                                <option value="">Select from approved GPOA</option>
                                @foreach($lineItems as $item)
                                    <option value="{{ $item->id }}"
                                            data-title="{{ e($item->title) }}"
                                            data-category="{{ e($item->category) }}"
                                            data-activity-level="{{ e($item->activity_level) }}"
                                            data-sdgs="{{ implode(',', $item->sdgs ?? []) }}"
                                            data-objectives="{{ e($item->objectives) }}"
                                            data-expected-outcome="{{ e($item->expected_outcome) }}"
                                            data-plan-key-strategy="{{ e($item->plan_key_strategy) }}"
                                            data-target-participants="{{ e($item->target_participants) }}"
                                            data-person-in-charge="{{ e($item->person_in_charge) }}"
                                            data-facilities-materials="{{ e($item->facilities_materials) }}"
                                            data-estimated-budget="{{ $item->estimated_budget }}"
                                            data-remarks="{{ e($item->remarks) }}"
                                            data-source-of-funds="{{ e($item->source_of_funds) }}"
                                            data-preceding-activity="{{ e($item->preceding_activity) }}"
                                            {{ old('gpoa_activity_id', $selectedLineItem?->id) == $item->id ? 'selected' : '' }}>
                                        {{ $item->title }} — {{ $item->date->format('M d, Y') }} @ {{ $item->venue }}
                                    </option>
                                @endforeach
                            </select>
                            @error('gpoa_activity_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="activity-card bg-slate-50 border-slate-200">
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Classification</h3>
                            </div>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <p class="text-xs text-slate-500">Category</p>
                                    <p id="summary-category" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->category ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Activity Level</p>
                                    <p id="summary-activity_level" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->activity_level ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">SDGs</p>
                                    <p id="summary-sdgs" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->sdgs ? 'SDG '.implode(', SDG ', $selectedLineItem->sdgs) : '—' }}</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Planning</h3>
                                <div class="grid gap-4 md:grid-cols-2 mt-3">
                                    <div>
                                        <p class="text-xs text-slate-500">Objectives</p>
                                        <p id="summary-objectives" class="mt-1 text-sm text-slate-900 whitespace-pre-line">{{ $selectedLineItem?->objectives ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Expected Outcome</p>
                                        <p id="summary-expected_outcome" class="mt-1 text-sm text-slate-900 whitespace-pre-line">{{ $selectedLineItem?->expected_outcome ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Key Strategy</p>
                                        <p id="summary-plan_key_strategy" class="mt-1 text-sm text-slate-900 whitespace-pre-line">{{ $selectedLineItem?->plan_key_strategy ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Preceding Activity</p>
                                        <p id="summary-preceding_activity" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->preceding_activity ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Logistics</h3>
                                <div class="grid gap-4 md:grid-cols-3 mt-3">
                                    <div>
                                        <p class="text-xs text-slate-500">Target Participants</p>
                                        <p id="summary-target_participants" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->target_participants ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Person in Charge</p>
                                        <p id="summary-person_in_charge" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->person_in_charge ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Facilities / Materials</p>
                                        <p id="summary-facilities_materials" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->facilities_materials ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Estimated Budget</p>
                                        <p id="summary-estimated_budget" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->estimated_budget !== null ? number_format($selectedLineItem->estimated_budget, 2) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Source of Funds</p>
                                        <p id="summary-source_of_funds" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->source_of_funds ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Remarks</p>
                                        <p id="summary-remarks" class="mt-1 text-sm text-slate-900">{{ $selectedLineItem?->remarks ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row grid-2 mt-6">
                            <div class="form-group">
                                <label>Additional Notes</label>
                                <textarea name="description" id="description" rows="4">{{ old('description') }}</textarea>
                                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group">
                                <label>Participants Override</label>
                                <input type="number" name="participants_count" id="participants_count" min="1" value="{{ old('participants_count') }}" placeholder="Leave blank to use planned amount">
                                @error('participants_count')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-group mt-6">
                            <label>Communication Letter (PDF) *</label>
                            <input type="file" name="communication_letter" accept=".pdf" required>
                            @error('communication_letter')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </section>

                <div class="activity-card" style="background:#eef2ff;border-color:#dbeafe;margin-bottom:24px;">
                    <p class="text-sm text-sky-900">This request will carry the selected activity's approved GPOA planning details into the request record.</p>
                </div>

                <button type="submit" class="btn-secondary">Submit Activity Request</button>
            </form>
        </div>
    </main>

    <script>
        function fillFromGpoa(select) {
            const opt = select.options[select.selectedIndex];
            if (!opt.value) return;

            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = value || '—';
                }
            };

            setText('summary-category', opt.dataset.category);
            setText('summary-activity_level', opt.dataset.activityLevel);
            setText('summary-sdgs', opt.dataset.sdgs ? 'SDG ' + opt.dataset.sdgs.split(',').join(', SDG ') : '—');
            setText('summary-objectives', opt.dataset.objectives);
            setText('summary-expected_outcome', opt.dataset.expectedOutcome);
            setText('summary-plan_key_strategy', opt.dataset.planKeyStrategy);
            setText('summary-preceding_activity', opt.dataset.precedingActivity);
            setText('summary-target_participants', opt.dataset.targetParticipants);
            setText('summary-person_in_charge', opt.dataset.personInCharge);
            setText('summary-facilities_materials', opt.dataset.facilitiesMaterials);
            setText('summary-estimated_budget', opt.dataset.estimatedBudget ? parseFloat(opt.dataset.estimatedBudget).toFixed(2) : '—');
            setText('summary-source_of_funds', opt.dataset.sourceOfFunds);
            setText('summary-remarks', opt.dataset.remarks);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sel = document.getElementById('gpoa_activity_id');
            if (sel && sel.value) fillFromGpoa(sel);
        });
    </script>
</x-app-layout>
