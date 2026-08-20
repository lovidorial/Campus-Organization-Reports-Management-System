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
</x-app-layout>
