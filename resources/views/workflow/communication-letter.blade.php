<x-app-layout>
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Submit Communication Letter</h2>
        <p class="text-sm text-gray-500 mb-6">Upload your organization's communication letter for OSDW review.</p>

        @if(!$canSubmit)
        <div class="mb-6 rounded-lg border border-orange-200 bg-orange-50 text-orange-800 px-4 py-3">
            <p class="font-semibold">Communication Letter is not yet available</p>
            <p class="text-sm mt-1">Your Communication Letter submission is currently locked until your GPOA is approved.</p>
        </div>
        @endif

        @if($submission && $submission->status === 'rejected')
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <p class="font-semibold">Previous submission was rejected</p>
            <p class="text-sm mt-1">{{ $submission->reject_reason }}</p>
        </div>
        @endif

        <form action="{{ route('workflow.communication-letter.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Communication Letter (PDF) <span class="text-red-500">*</span></label>
                <input type="file" name="communication_letter" accept=".pdf" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-300" @if(!$canSubmit) disabled @endif>
                <p class="text-xs text-gray-400 mt-1">PDF format only, max 20MB</p>
                @error('communication_letter')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-start gap-2">
                <input type="checkbox" name="verify" id="verify" value="1" required class="mt-1" @if(!$canSubmit) disabled @endif>
                <label for="verify" class="text-sm text-gray-600">I verify that the information provided is accurate and complete.</label>
            </div>
            @error('verify')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror

            <div class="flex gap-3">
                <a href="{{ route('dashboard') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300">Cancel</a>
                <button type="submit" class="px-6 py-2 text-white rounded-lg font-semibold hover:opacity-90" style="background:#e89600;" @if(!$canSubmit) disabled @endif>Submit for Review</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
