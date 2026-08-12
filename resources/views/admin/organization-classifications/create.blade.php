<x-app-layout>
<div class="max-w-3xl mx-auto space-y-8">
    <div>
        <a href="{{ route('admin.organization-classifications.index') }}" class="text-slate-500 hover:text-slate-700 text-sm">← Back to Classifications</a>
        <h1 class="mt-3 text-3xl font-bold text-slate-900">Add Organization Classification</h1>
        <p class="mt-2 text-sm text-slate-500">Use this list to auto-detect organization type and college area from org name.</p>
    </div>

    <form action="{{ route('admin.organization-classifications.store') }}" method="POST" class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Organization Name</label>
            <input name="org_name" value="{{ old('org_name') }}" required class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
            <x-input-error :messages="$errors->get('org_name')" class="mt-2" />
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Aliases (comma or newline separated)</label>
            <textarea name="aliases" rows="3" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ old('aliases') }}</textarea>
            <x-input-error :messages="$errors->get('aliases')" class="mt-2" />
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Classification</label>
            <select name="classification" required class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                <option value="">Select classification</option>
                <option value="Major" {{ old('classification')=='Major' ? 'selected' : '' }}>Major</option>
                <option value="Minor" {{ old('classification')=='Minor' ? 'selected' : '' }}>Minor</option>
                <option value="Specialized" {{ old('classification')=='Specialized' ? 'selected' : '' }}>Specialized</option>
            </select>
            <x-input-error :messages="$errors->get('classification')" class="mt-2" />
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">College / Area</label>
            <input name="college_area" value="{{ old('college_area') }}" required class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900" />
            <x-input-error :messages="$errors->get('college_area')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('admin.organization-classifications.index') }}" class="rounded-3xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-3xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Save Classification</button>
        </div>
    </form>
</div>
</x-app-layout>
