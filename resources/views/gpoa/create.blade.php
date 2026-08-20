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

                    <!-- Document Attachment (Optional) -->
                    <div class="form-group">
                        <label for="document_path">GPOA Document (PDF) - Optional</label>
                        <input type="file" id="document_path" name="document_path" accept=".pdf" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="help-text">Optional: upload your official GPOA document as a supplementary attachment (Max 20MB).</p>
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
</x-app-layout>
