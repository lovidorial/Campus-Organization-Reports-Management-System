<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/gpoa-form.css') }}">

    <div class="modal-backdrop">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Edit & Resubmit Activity Report</h2>
                    <a href="{{ route('user.activities') }}" class="modal-close">&times;</a>
                </div>

                <div class="modal-body">
                    <p class="modal-subtitle">Update your activity details and resubmit for approval</p>

                    @if($activity->reject_reason)
                    <div class="alert alert-error">
                        <strong>Previous Rejection Reason:</strong> {{ $activity->reject_reason }}
                    </div>
                    @endif

                    <form action="{{ route('gpoa.update', $activity) }}" method="POST" enctype="multipart/form-data" class="gpoa-form">
                        @csrf
                        @method('PUT')

                        <!-- Activity Information -->
                        <div class="form-section">
                            <h3 class="section-title">Activity Information</h3>

                            <div class="form-group">
                                <label for="title">Activity Title *</label>
                                <input type="text" id="title" name="title" required
                                       placeholder="Enter activity title" value="{{ old('title', $activity->title) }}">
                                @error('title')<span class="error-msg">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="colleges">College *</label>
                                    <select id="colleges" name="colleges" required>
                                        <option value="">Select college</option>
                                        @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS'] as $c)
                                        <option value="{{ $c }}" {{ old('colleges', $activity->organization)==$c?'selected':'' }}>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                    @error('colleges')<span class="error-msg">{{ $message }}</span>@enderror
                                </div>

                                <div class="form-group">
                                    <label for="date">Activity Date *</label>
                                    <input type="date" id="date" name="date" required value="{{ old('date', $activity->date->format('Y-m-d')) }}">
                                    @error('date')<span class="error-msg">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <!-- Category -->
                                <div class="form-group">
                                    <label for="category">Category *</label>
                                    <select id="category" name="category" required>
                                        <option value="">Select category</option>
                                        <option value="Academic"          {{ old('category', $activity->category)=='Academic'         ?'selected':'' }}>Academic</option>
                                        <option value="Civic/Community"   {{ old('category', $activity->category)=='Civic/Community'  ?'selected':'' }}>Civic / Community</option>
                                        <option value="Cultural"          {{ old('category', $activity->category)=='Cultural'         ?'selected':'' }}>Cultural</option>
                                        <option value="Environmental"     {{ old('category', $activity->category)=='Environmental'    ?'selected':'' }}>Environmental</option>
                                        <option value="Health & Wellness" {{ old('category', $activity->category)=='Health & Wellness'?'selected':'' }}>Health & Wellness</option>
                                        <option value="Leadership"        {{ old('category', $activity->category)=='Leadership'       ?'selected':'' }}>Leadership</option>
                                        <option value="Religious/Spiritual"{{ old('category', $activity->category)=='Religious/Spiritual'?'selected':'' }}>Religious / Spiritual</option>
                                        <option value="Skills Training"   {{ old('category', $activity->category)=='Skills Training'  ?'selected':'' }}>Skills Training</option>
                                        <option value="Sports"            {{ old('category', $activity->category)=='Sports'           ?'selected':'' }}>Sports</option>
                                        <option value="Other"             {{ old('category', $activity->category)=='Other'            ?'selected':'' }}>Other</option>
                                    </select>
                                    @error('category')<span class="error-msg">{{ $message }}</span>@enderror
                                </div>

                                <!-- Basis for Grading -->
                                <div class="form-group">
                                    <label for="basis_grading">Basis for Grading</label>
                                    <select id="basis_grading" name="basis_grading">
                                        <option value="">Select basis</option>
                                        <option value="Mandatory" {{ old('basis_grading', $activity->basis_grading)=='Mandatory'?'selected':'' }}>Mandatory</option>
                                        <option value="Elective"  {{ old('basis_grading', $activity->basis_grading)=='Elective' ?'selected':'' }}>Elective</option>
                                        <option value="NSTP"      {{ old('basis_grading', $activity->basis_grading)=='NSTP'     ?'selected':'' }}>NSTP</option>
                                        <option value="Voluntary" {{ old('basis_grading', $activity->basis_grading)=='Voluntary'?'selected':'' }}>Voluntary</option>
                                        <option value="N/A"       {{ old('basis_grading', $activity->basis_grading)=='N/A'      ?'selected':'' }}>N/A</option>
                                    </select>
                                </div>

                                <!-- Term -->
                                <div class="form-group">
                                    <label for="term">Term *</label>
                                    <select id="term" name="term" required>
                                        <option value="">Select term</option>
                                        <option value="1st Term" {{ old('term', $activity->term)=='1st Term'?'selected':'' }}>1st Term</option>
                                        <option value="2nd Term" {{ old('term', $activity->term)=='2nd Term'?'selected':'' }}>2nd Term</option>
                                    </select>
                                    @error('term')<span class="error-msg">{{ $message }}</span>@enderror
                                </div>

                                <!-- School Year -->
                                <div class="form-group">
                                    <label for="school_year">School Year *</label>
                                    <input type="text" id="school_year" name="school_year" required
                                           placeholder="e.g. 2025-2026" value="{{ old('school_year', $activity->school_year) }}">
                                    @error('school_year')<span class="error-msg">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="venue">Venue *</label>
                                <input type="text" id="venue" name="venue" required
                                       placeholder="Enter venue location" value="{{ old('venue', $activity->venue) }}">
                                @error('venue')<span class="error-msg">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label for="description">Description *</label>
                                <textarea id="description" name="description" required
                                          placeholder="Describe the activity in detail..." rows="4">{{ old('description', $activity->description) }}</textarea>
                                @error('description')<span class="error-msg">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <!-- Participants -->
                        <div class="form-section">
                            <h3 class="section-title">Participants & Numbers</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="participants_count">Number of Participants *</label>
                                    <input type="number" id="participants_count" name="participants_count"
                                           required min="1" value="{{ old('participants_count', $activity->participants_count) }}">
                                    @error('participants_count')<span class="error-msg">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="form-section">
                            <h3 class="section-title">Supporting Documents</h3>

                            <div class="form-group">
                                <label for="communication_letter">Communication Letter (PDF)</label>
                                <input type="file" id="communication_letter" name="communication_letter" accept=".pdf">
                                <p class="help-text">Leave empty to keep current file. New file must be PDF (Max size: 20MB)</p>
                                @if($activity->communication_letter)
                                <p class="help-text">Current: <a href="{{ asset('storage/'.$activity->communication_letter) }}" target="_blank" class="text-blue-600">View current file</a></p>
                                @endif
                                @error('communication_letter')<span class="error-msg">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label for="narrative_report">Narrative Report (PDF)</label>
                                <input type="file" id="narrative_report" name="narrative_report" accept=".pdf">
                                <p class="help-text">Leave empty to keep current file. New file must be PDF (Max size: 20MB)</p>
                                @if($activity->narrative_report)
                                <p class="help-text">Current: <a href="{{ asset('storage/'.$activity->narrative_report) }}" target="_blank" class="text-green-600">View current file</a></p>
                                @endif
                                @error('narrative_report')<span class="error-msg">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group checkbox">
                                <input type="checkbox" id="verify" name="verify" required>
                                <label for="verify">I verify that the information provided is accurate and complete</label>
                                @error('verify')<span class="error-msg">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="{{ route('user.activities') }}" class="btn-secondary">Cancel</a>
                            <button type="submit" class="btn-primary">Update & Resubmit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.body.classList.add('modal-open');
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') window.location.href = '{{ route('user.activities') }}';
        });
    </script>
</x-app-layout>