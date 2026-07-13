<x-app-layout>
<div class="mb-6">
    <a href="{{ route('activity-requests.index') }}" class="text-sky-600 text-sm hover:underline">← Back to Activity Requests</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Request Activity</h2>
    <p class="text-sm text-gray-500">Select a planned activity from your approved GPOA. Details must match exactly.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-8 max-w-3xl">
    <form action="{{ route('activity-requests.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="requestForm">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">GPOA Activity *</label>
            <select name="gpoa_activity_id" id="gpoa_activity_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-sky-500 focus:ring-sky-500"
                    onchange="fillFromGpoa(this)">
                <option value="">Select from approved GPOA</option>
                @foreach($lineItems as $item)
                <option value="{{ $item->id }}"
                        data-title="{{ $item->title }}"
                        data-date="{{ $item->date->format('Y-m-d') }}"
                        data-venue="{{ $item->venue }}"
                        data-description="{{ $item->description }}"
                        data-participants="{{ $item->participants_count }}"
                        {{ old('gpoa_activity_id')==$item->id?'selected':'' }}>
                    {{ $item->title }} — {{ $item->date->format('M d, Y') }} @ {{ $item->venue }}
                </option>
                @endforeach
            </select>
            @error('gpoa_activity_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            @error('match')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Title * <span class="text-xs text-gray-400">(must match GPOA)</span></label>
                <input type="text" name="title" id="title" required value="{{ old('title') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-sky-500">
                @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date * <span class="text-xs text-gray-400">(must match GPOA)</span></label>
                <input type="date" name="date" id="date" required value="{{ old('date') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-sky-500">
                @error('date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Venue * <span class="text-xs text-gray-400">(must match GPOA)</span></label>
                <input type="text" name="venue" id="venue" required value="{{ old('venue') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-sky-500">
                @error('venue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-sky-500">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Participants</label>
                <input type="number" name="participants_count" id="participants_count" min="1" value="{{ old('participants_count') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-sky-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Communication Letter (PDF) *</label>
                <input type="file" name="communication_letter" accept=".pdf" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @error('communication_letter')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="bg-sky-50 border border-sky-200 rounded-lg p-4 text-sm text-sky-800">
            Activity requests are approved only if title, date, and venue match your approved GPOA entry exactly.
        </div>

        <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-8 rounded-lg">
            Submit Activity Request
        </button>
    </form>
</div>

<script>
function fillFromGpoa(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt.value) return;
    document.getElementById('title').value = opt.dataset.title || '';
    document.getElementById('date').value = opt.dataset.date || '';
    document.getElementById('venue').value = opt.dataset.venue || '';
    document.getElementById('description').value = opt.dataset.description || '';
    document.getElementById('participants_count').value = opt.dataset.participants || '';
}
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('gpoa_activity_id');
    if (sel.value) fillFromGpoa(sel);
});
</script>
</x-app-layout>
