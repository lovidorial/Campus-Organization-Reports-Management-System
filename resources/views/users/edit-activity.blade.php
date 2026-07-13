<x-app-layout>
    <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Edit Submitted Activity</h2>

        <form action="{{ route('user.activities.update', $activity) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')



            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Title of Activity <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('title') border-red-500 @enderror" value="{{ old('title', $activity->title) }}" required>
                        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Organization</label>
                        <input type="text" disabled value="{{ $activity->organization ?? auth()->user()->org_name }}" class="w-full border border-gray-300 rounded-lg shadow-sm bg-gray-100 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="date" id="date" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('date') border-red-500 @enderror" value="{{ old('date', $activity->date->format('Y-m-d')) }}" required>
                        @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Venue <span class="text-red-500">*</span></label>
                        <input type="text" name="venue" id="venue" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('venue') border-red-500 @enderror" value="{{ old('venue', $activity->venue) }}" required>
                        @error('venue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Communication Letter</label>
                        @if($activity->communication_letter)
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <a href="{{ asset('storage/'.$activity->communication_letter) }}" target="_blank" class="text-sky-600 hover:underline text-xs">View current file</a>
                            </div>
                        @endif
                        <input type="file" name="communication_letter" id="communication_letter" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('communication_letter') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Upload a new file to replace the current one. Accepted: PDF, DOC, DOCX (Max 20MB)</p>
                        @error('communication_letter')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Narrative Report</label>
                        @if($activity->narrative_report)
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <a href="{{ asset('storage/'.$activity->narrative_report) }}" target="_blank" class="text-sky-600 hover:underline text-xs">View current file</a>
                            </div>
                        @endif
                        <input type="file" name="narrative_report" id="narrative_report" class="w-full border border-gray-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500 px-3 py-2 @error('narrative_report') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">Upload a new file to replace the current one. Accepted: PDF, DOC, DOCX (Max 20MB)</p>
                        @error('narrative_report')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center pt-4 border-t gap-3">
                <a href="{{ route('user.activities') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-8 rounded-lg shadow-md transition">
                    Cancel
                </a>
                <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
