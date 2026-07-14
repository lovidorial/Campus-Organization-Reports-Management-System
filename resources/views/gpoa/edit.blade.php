<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <div class="modal-backdrop">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Edit GPOA (Pending Review)</h2>
                    <a href="{{ route('dashboard') }}" class="modal-close">&times;</a>
                </div>

                <div class="modal-body">
                    <p class="modal-subtitle">Update your GPOA while it is pending OSDW review.</p>

                    <form action="{{ route('gpoa.update', $gpoa) }}" method="POST" enctype="multipart/form-data" class="gpoa-form" id="gpoaForm">
                        @csrf
                        @method('PUT')

                        <div class="form-section">
                            <h3 class="section-title">GPOA Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="colleges">College *</label>
                                    <select id="colleges" name="colleges" required>
                                        @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS'] as $c)
                                        <option value="{{ $c }}" {{ old('colleges', $gpoa->college)==$c?'selected':'' }}>{{ $c }}</option>
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
                        </div>

                        <div class="form-section">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="section-title mb-0">Planned Activities *</h3>
                                <button type="button" onclick="addActivityRow()" class="btn-secondary text-sm">+ Add Activity</button>
                            </div>
                            <div id="activitiesContainer">
                                @php $oldActivities = old('activities', $gpoa->activities->toArray()); @endphp
                                @foreach($oldActivities as $i => $act)
                                <div class="activity-row border rounded-lg p-4 mb-4 bg-gray-50">
                                    <div class="flex justify-between mb-2">
                                        <strong>Activity #<span class="row-num">{{ $loop->iteration }}</span></strong>
                                        @if($loop->iteration > 1)
                                        <button type="button" onclick="removeActivityRow(this)" class="text-red-600 text-xs">Remove</button>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label>Title *</label>
                                        <input type="text" name="activities[{{ $i }}][title]" required value="{{ $act['title'] ?? '' }}">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Date *</label>
                                            <input type="date" name="activities[{{ $i }}][date]" required value="{{ isset($act['date']) ? (\Carbon\Carbon::parse($act['date'])->format('Y-m-d')) : '' }}">
                                        </div>
                                        <div class="form-group">
                                            <label>Venue *</label>
                                            <input type="text" name="activities[{{ $i }}][venue]" required value="{{ $act['venue'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Category *</label>
                                            <select name="activities[{{ $i }}][category]" required>
                                                @foreach(['Academic','Civic/Community','Cultural','Environmental','Health & Wellness','Leadership','Religious/Spiritual','Skills Training','Sports','Other'] as $cat)
                                                <option value="{{ $cat }}" {{ ($act['category'] ?? '')==$cat?'selected':'' }}>{{ $cat }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Participants *</label>
                                            <input type="number" name="activities[{{ $i }}][participants_count]" required min="1" value="{{ $act['participants_count'] ?? '' }}">
                                        </div>
                                        <div class="form-group">
                                            <label>Basis for Grading</label>
                                            <select name="activities[{{ $i }}][basis_grading]">
                                                @foreach(['Mandatory','Elective','NSTP','Voluntary','N/A'] as $b)
                                                <option value="{{ $b }}" {{ ($act['basis_grading'] ?? '')==$b?'selected':'' }}>{{ $b }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Description *</label>
                                        <textarea name="activities[{{ $i }}][description]" required rows="3">{{ $act['description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group checkbox">
                            <input type="checkbox" id="verify" name="verify" required>
                            <label for="verify">I verify that the GPOA information provided is accurate and complete</label>
                        </div>

                        <div class="form-actions">
                            <a href="{{ route('dashboard') }}" class="btn-secondary">Cancel</a>
                            <button type="submit" class="btn-primary">Update GPOA</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <template id="activityRowTemplate">
        <div class="activity-row border rounded-lg p-4 mb-4 bg-gray-50">
            <div class="flex justify-between mb-2">
                <strong>Activity #<span class="row-num">1</span></strong>
                <button type="button" onclick="removeActivityRow(this)" class="text-red-600 text-xs">Remove</button>
            </div>
            <div class="form-group"><label>Title *</label><input type="text" name="activities[__INDEX__][title]" required></div>
            <div class="form-row">
                <div class="form-group"><label>Date *</label><input type="date" name="activities[__INDEX__][date]" required></div>
                <div class="form-group"><label>Venue *</label><input type="text" name="activities[__INDEX__][venue]" required></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="activities[__INDEX__][category]" required>
                        @foreach(['Academic','Civic/Community','Cultural','Environmental','Health & Wellness','Leadership','Religious/Spiritual','Skills Training','Sports','Other'] as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Participants *</label><input type="number" name="activities[__INDEX__][participants_count]" required min="1"></div>
            </div>
            <div class="form-group"><label>Description *</label><textarea name="activities[__INDEX__][description]" required rows="3"></textarea></div>
        </div>
    </template>

    <script>
        document.body.classList.add('modal-open');
        let activityIndex = {{ count(old('activities', $gpoa->activities)) }};
        function addActivityRow() {
            const container = document.getElementById('activitiesContainer');
            const html = document.getElementById('activityRowTemplate').innerHTML.replace(/__INDEX__/g, activityIndex);
            container.insertAdjacentHTML('beforeend', html);
            activityIndex++;
            renumberRows();
        }
        function removeActivityRow(btn) { btn.closest('.activity-row').remove(); renumberRows(); }
        function renumberRows() {
            document.querySelectorAll('.activity-row').forEach((row, i) => {
                row.querySelector('.row-num').textContent = i + 1;
            });
        }
    </script>
</x-app-layout>
