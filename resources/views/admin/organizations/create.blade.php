<x-app-layout>
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.organizations.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h2 class="text-2xl font-bold text-gray-800">Add Organization</h2>
    </div>

    <form action="{{ route('admin.organizations.store') }}" method="POST"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Organization Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"
                       placeholder="e.g. CITE-SC"/>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Type *</label>
                <select name="type" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select type</option>
                    <option value="Student Council"  {{ old('type')=='Student Council' ?'selected':'' }}>Student Council</option>
                    <option value="Academic Org"     {{ old('type')=='Academic Org'    ?'selected':'' }}>Academic Org</option>
                    <option value="Cultural Org"     {{ old('type')=='Cultural Org'    ?'selected':'' }}>Cultural Org</option>
                    <option value="Sports Org"       {{ old('type')=='Sports Org'      ?'selected':'' }}>Sports Org</option>
                    <option value="Religious Org"    {{ old('type')=='Religious Org'   ?'selected':'' }}>Religious Org</option>
                    <option value="Publication"      {{ old('type')=='Publication'     ?'selected':'' }}>Publication</option>
                    <option value="Other"            {{ old('type')=='Other'           ?'selected':'' }}>Other</option>
                </select>
                @error('type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">College / Unit</label>
                <select name="college" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select college</option>
                    <option value="CTED"  {{ old('college')=='CTED' ?'selected':'' }}>CTED</option>
                    <option value="CCJE"  {{ old('college')=='CCJE' ?'selected':'' }}>CCJE</option>
                    <option value="CHM"   {{ old('college')=='CHM'  ?'selected':'' }}>CHM</option>
                    <option value="CFAS"  {{ old('college')=='CFAS' ?'selected':'' }}>CFAS</option>
                    <option value="CBEA"  {{ old('college')=='CBEA' ?'selected':'' }}>CBEA</option>
                    <option value="CIT"   {{ old('college')=='CIT'  ?'selected':'' }}>CIT</option>
                    <option value="CICS"  {{ old('college')=='CICS' ?'selected':'' }}>CICS</option>
                    <option value="University-Wide" {{ old('college')=='University-Wide'?'selected':'' }}>University-Wide</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">SC President / Head</label>
                <input type="text" name="sc_president" value="{{ old('sc_president') }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"
                       placeholder="Full name"/>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Term</label>
                <select name="term" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select term</option>
                    <option value="1st Term" {{ old('term')=='1st Term'?'selected':'' }}>1st Term</option>
                    <option value="2nd Term" {{ old('term')=='2nd Term'?'selected':'' }}>2nd Term</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">School Year</label>
                <input type="text" name="school_year" value="{{ old('school_year') }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"
                       placeholder="e.g. 2025-2026"/>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"
                          placeholder="Brief description of the organization...">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">Save Organization</button>
            <a href="{{ route('admin.organizations.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
