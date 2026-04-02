<x-app-layout>
    <!-- Error Modal -->
    <div id="errorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg p-6 max-w-md w-full mx-4">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-circle text-red-600 text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-800" id="errorTitle">Submission Error</h3>
                    <p class="text-sm text-gray-600 mt-1" id="errorMessage"></p>
                </div>
            </div>
            <div id="errorList" class="bg-red-50 rounded-lg p-3 mb-4 hidden">
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1" id="errorItems"></ul>
            </div>
            <button onclick="closeErrorModal()" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-lg transition">
                Okay, Got It
            </button>
        </div>
    </div>

    <!-- General Error Alert (if validation fails on page load) -->
    @if ($errors->any())
    <div class="bg-red-50 border-l-4 border-red-600 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">
                    Please fix the following errors:
                </p>
                <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Submit Activity Report</h2>
        
        <form action="{{ route('user.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="activityForm" onsubmit="return validateForm(event)">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Title of Activity <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('title') border-red-500 @enderror" value="{{ old('title') }}" required>
                        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Organization <span class="text-red-500">*</span></label>
                        <select name="organization" id="organization" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('organization') border-red-500 @enderror" required>
                            <option value="">Select organization</option>
                            <option value="PASSED" {{ old('organization')=='PASSED'?'selected':'' }}>PASSED</option>
                            <option value="JAB-CIM" {{ old('organization')=='JAB-CIM'?'selected':'' }}>JAB-CIM</option>
                            <option value="HMS" {{ old('organization')=='HMS'?'selected':'' }}>HMS</option>
                            <option value="FAME" {{ old('organization')=='FAME'?'selected':'' }}>FAME</option>
                            <option value="GFARS-SCO" {{ old('organization')=='GFARS-SCO'?'selected':'' }}>GFARS-SCO</option>
                            <option value="ASDIDS" {{ old('organization')=='ASDIDS'?'selected':'' }}>ASDIDS</option>
                            <option value="CITE-SC" {{ old('organization')=='CITE-SC'?'selected':'' }}>CITE-SC</option>
                            <option value="GFARS-SC" {{ old('organization')=='GFARS-SC'?'selected':'' }}>GFARS-SC</option>
                            <option value="CICS-SC" {{ old('organization')=='CICS-SC'?'selected':'' }}>CICS-SC</option>
                            <option value="MARAHUYO" {{ old('organization')=='MARAHUYO'?'selected':'' }}>MARAHUYO</option>
                            <option value="CTE-SC" {{ old('organization')=='CTE-SC'?'selected':'' }}>CTE-SC</option>
                            <option value="CIM-SC" {{ old('organization')=='CIM-SC'?'selected':'' }}>CIM-SC</option>
                            <option value="LEVEL" {{ old('organization')=='LEVEL'?'selected':'' }}>LEVEL</option>
                            <option value="CSC" {{ old('organization')=='CSC'?'selected':'' }}>CSC</option>
                            <option value="THE WATERWORLD" {{ old('organization')=='THE WATERWORLD'?'selected':'' }}>THE WATERWORLD</option>
                            <option value="THE MANFOR" {{ old('organization')=='THE MANFOR'?'selected':'' }}>THE MANFOR</option>
                            <option value="THE LEDGER" {{ old('organization')=='THE LEDGER'?'selected':'' }}>THE LEDGER</option>
                            <option value="THE CONDUIT" {{ old('organization')=='THE CONDUIT'?'selected':'' }}>THE CONDUIT</option>
                            <option value="THE CALIBER" {{ old('organization')=='THE CALIBER'?'selected':'' }}>THE CALIBER</option>
                            <option value="THE BANQUET" {{ old('organization')=='THE BANQUET'?'selected':'' }}>THE BANQUET</option>
                            <option value="THE ACADEMIA" {{ old('organization')=='THE ACADEMIA'?'selected':'' }}>THE ACADEMIA</option>
                            <option value="THE AQUARIUS" {{ old('organization')=='THE AQUARIUS'?'selected':'' }}>THE AQUARIUS</option>
                            <option value="SARIGAWAN" {{ old('organization')=='SARIGAWAN'?'selected':'' }}>SARIGAWAN</option>
                            <option value="STA" {{ old('organization')=='STA'?'selected':'' }}>STA</option>
                            <option value="SIKLAHON" {{ old('organization')=='SIKLAHON'?'selected':'' }}>SIKLAHON</option>
                            <option value="SARITADA" {{ old('organization')=='SARITADA'?'selected':'' }}>SARITADA</option>
                            <option value="KASINDAKAN" {{ old('organization')=='KASINDAKAN'?'selected':'' }}>KASINDAKAN</option>
                            <option value="TOUCH" {{ old('organization')=='TOUCH'?'selected':'' }}>TOUCH</option>
                            <option value="GS-SC" {{ old('organization')=='GS-SC'?'selected':'' }}>GS-SC</option>
                            <option value="CBI" {{ old('organization')=='CBI'?'selected':'' }}>CBI</option>
                        </select>
                        @error('organization')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="date" id="date" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('date') border-red-500 @enderror" value="{{ old('date') }}" required>
                        @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Venue <span class="text-red-500">*</span></label>
                        <input type="text" name="venue" id="venue" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('venue') border-red-500 @enderror" value="{{ old('venue') }}" required>
                        @error('venue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Communication Letter</label>
                        <input type="file" name="communication_letter" id="communication_letter" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('communication_letter') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, DOC, DOCX (Max 5MB)</p>
                        @error('communication_letter')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Narrative Report</label>
                        <input type="file" name="narrative_report" id="narrative_report" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('narrative_report') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, DOC, DOCX (Max 5MB)</p>
                        @error('narrative_report')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t gap-3">
                <a href="{{ route('user.activities') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-8 rounded-lg shadow-md transition">
                    Cancel
                </a>
                <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition">
                    Submit Activity
                </button>
            </div>
        </form>
    </div>

    <script>
        function validateForm(event) {
            const title = document.getElementById('title').value.trim();
            const organization = document.getElementById('organization').value;
            const date = document.getElementById('date').value;
            const venue = document.getElementById('venue').value.trim();
            const errors = [];

            if (!title) errors.push('Activity Title is required');
            if (!organization) errors.push('Organization selection is required');
            if (!date) errors.push('Activity Date is required');
            if (!venue) errors.push('Venue is required');

            // Validate file sizes
            const communicationLetter = document.getElementById('communication_letter');
            const narrativeReport = document.getElementById('narrative_report');
            const maxFileSize = 5 * 1024 * 1024; // 5MB

            if (communicationLetter.files.length > 0) {
                if (communicationLetter.files[0].size > maxFileSize) {
                    errors.push('Communication Letter file is too large (Max 5MB)');
                }
                const validFormats = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!validFormats.includes(communicationLetter.files[0].type)) {
                    errors.push('Communication Letter must be PDF or DOC/DOCX format');
                }
            }

            if (narrativeReport.files.length > 0) {
                if (narrativeReport.files[0].size > maxFileSize) {
                    errors.push('Narrative Report file is too large (Max 5MB)');
                }
                const validFormats = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!validFormats.includes(narrativeReport.files[0].type)) {
                    errors.push('Narrative Report must be PDF or DOC/DOCX format');
                }
            }

            if (errors.length > 0) {
                showErrorModal(errors);
                event.preventDefault();
                return false;
            }

            return true;
        }

        function showErrorModal(errors) {
            const modal = document.getElementById('errorModal');
            const errorList = document.getElementById('errorList');
            const errorItems = document.getElementById('errorItems');

            if (errors.length > 1) {
                errorList.classList.remove('hidden');
                errorItems.innerHTML = errors.map(err => `<li>${err}</li>`).join('');
            } else {
                errorList.classList.add('hidden');
                document.getElementById('errorMessage').textContent = errors[0];
            }

            modal.classList.remove('hidden');
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('errorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeErrorModal();
            }
        });
    </script>
</x-app-layout>