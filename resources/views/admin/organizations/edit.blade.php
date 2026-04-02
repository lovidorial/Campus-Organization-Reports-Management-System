<x-app-layout>
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.organizations.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h2 class="text-2xl font-bold text-gray-800">Edit Organization</h2>
    </div>

    <form action="{{ route('admin.organizations.update', $organization) }}" method="POST"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf @method('PATCH')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Organization Name *</label>
                <input type="text" name="name" value="{{ old('name', $organization->name) }}" required
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"/>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Type *</label>
                <select name="type" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select type</option>
                    @foreach(['Student Council','Academic Org','Cultural Org','Sports Org','Religious Org','Publication','Other'] as $t)
                    <option value="{{ $t }}" {{ old('type',$organization->type)==$t?'selected':'' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">College / Unit</label>
                <select name="college" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select college</option>
                    @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS','University-Wide'] as $c)
                    <option value="{{ $c }}" {{ old('college',$organization->college)==$c?'selected':'' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">SC President / Head</label>
                <input type="text" name="sc_president" value="{{ old('sc_president', $organization->sc_president) }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"/>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Term</label>
                <select name="term" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select term</option>
                    <option value="1st Term" {{ old('term',$organization->term)=='1st Term'?'selected':'' }}>1st Term</option>
                    <option value="2nd Term" {{ old('term',$organization->term)=='2nd Term'?'selected':'' }}>2nd Term</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">School Year</label>
                <input type="text" name="school_year" value="{{ old('school_year', $organization->school_year) }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"
                       placeholder="e.g. 2025-2026"/>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">{{ old('description', $organization->description) }}</textarea>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $organization->is_active) ? 'checked' : '' }}
                           class="rounded text-sky-600"/>
                    <span class="text-sm font-semibold text-gray-700">Active Organization</span>
                </label>
            </div>
        </div>

        <!-- Org Members Section -->
        <div class="border-t pt-5">
            <h3 class="font-bold text-gray-700 mb-3">Members ({{ $allUsers->count() }} users)</h3>
            <p class="text-xs text-gray-500 mb-3">Assign users to this organization by editing their profile.</p>
            @if($organization->members->count())
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($organization->members as $member)
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-2 text-sm">
                    <div class="w-8 h-8 rounded-full bg-sky-500 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                        {{ substr($member->name,0,1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium truncate">{{ $member->name }}</p>
                        <p class="text-xs text-gray-400">{{ $member->position ?? 'Member' }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400">No members assigned yet.</p>
            @endif
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">Update</button>
            <a href="{{ route('admin.organizations.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
