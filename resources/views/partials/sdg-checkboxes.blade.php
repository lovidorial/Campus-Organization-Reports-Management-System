@php
    $isGpoa = $gpoa ?? false;
    $oldNums = $oldNums ?? collect((array) old('sdgs'))->map(fn ($value) => (string) $value)->all();
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
        17 => 'Partnerships',
    ];
@endphp

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
    .sdg-validation-error { color: #DC2626; font-size: 0.875rem; margin-top: 6px; display: none; }
    .sdg-validation-error.show { display: block; }
</style>

@if($isGpoa)
<div class="sdg-widget" x-init="$nextTick(() => { const inputs = [...$el.querySelectorAll('input[type=checkbox]')]; const summary = $el.querySelector('[data-sdg-summary]'); const colors = JSON.parse(summary.dataset.sdgColors); const error = $el.querySelector('[data-sdg-validation-error]'); const update = () => { const selected = inputs.filter(input => input.checked); summary.innerHTML = selected.length ? selected.map(input => { const sdg = colors[input.value]; return `<span class='sdg-badge' style='background-color: ${sdg.color}; color: ${sdg.text}'>SDG ${input.value}</span>`; }).join('') : '<span class=&quot;sdg-placeholder&quot;>No SDGs selected yet</span>'; inputs.forEach(input => { input.disabled = !input.checked && selected.length >= 8; }); error.textContent = selected.length === 0 ? 'Select at least 1 SDG.' : ''; error.classList.toggle('show', selected.length === 0); }; $el.addEventListener('change', update); update(); })">
@endif
<div class="sdg-label-row">
    <label>SDGs *</label>
    <div class="sdg-summary-badges" @if($isGpoa) :id="'sdgSummary-' + index" data-sdg-summary @else id="sdgSummary" data-sdg-summary @endif data-sdg-colors='@json(config('sdg'))' aria-live="polite" aria-atomic="true">
        <span class="sdg-placeholder">No SDGs selected yet</span>
    </div>
</div>

<div class="sdg-checkbox-list" @if($isGpoa) :id="'sdgCheckboxes-' + index" data-sdg-checkboxes @else id="sdgCheckboxes" @endif>
    @foreach($sdgList as $num => $label)
        <div class="sdg-checkbox-item">
            @if($isGpoa)
                <input type="checkbox" :id="'planned-sdg-' + index + '-{{ $num }}'" :name="'planned_activities[' + index + '][sdgs][]'" value="{{ $num }}" x-model="activity.sdgs" style="accent-color: {{ config('sdg.' . $num . '.color') }}">
                <span class="sdg-number" style="background-color: {{ config('sdg.' . $num . '.color') }}; color: {{ config('sdg.' . $num . '.text') }}" aria-hidden="true">{{ $num }}</span>
                <label :for="'planned-sdg-' + index + '-{{ $num }}'">SDG {{ $num }} - {{ $label }}</label>
            @else
                <input type="checkbox" id="sdg{{ $num }}" name="sdgs[]" value="{{ $num }}" style="accent-color: {{ config('sdg.' . $num . '.color') }}" {{ in_array((string) $num, $oldNums ?? []) ? 'checked' : '' }}>
                <span class="sdg-number" style="background-color: {{ config('sdg.' . $num . '.color') }}; color: {{ config('sdg.' . $num . '.text') }}" aria-hidden="true">{{ $num }}</span>
                <label for="sdg{{ $num }}">SDG {{ $num }} - {{ $label }}</label>
            @endif
        </div>
    @endforeach
</div>

<div class="sdg-helper-text">Must select 1-8 SDGs aligned with the activity</div>
@if($isGpoa)
    <div class="sdg-validation-error" data-sdg-validation-error></div>
@else
    <div id="sdgValidationError" class="sdg-validation-error"></div>
@endif
@if($isGpoa)
</div>
@endif
