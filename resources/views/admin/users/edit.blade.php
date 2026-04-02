<x-app-layout>
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <h2 class="text-2xl font-bold text-gray-800">Edit Member Profile</h2>
    </div>

    <form action="{{ route('admin.users.update', $user) }}" method="POST"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf @method('PATCH')

        <!-- Profile Photo Preview -->
        <div class="flex items-center gap-4">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"/>
            @else
                <div class="w-16 h-16 rounded-full bg-sky-500 text-white flex items-center justify-center text-2xl font-bold">
                    {{ substr($user->name,0,1) }}
                </div>
            @endif
            <div>
                <p class="font-bold text-gray-800">{{ $user->name }}</p>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t pt-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"/>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"/>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
                <select name="role" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="user"  {{ old('role',$user->role)=='user' ?'selected':'' }}>User</option>
                    <option value="admin" {{ old('role',$user->role)=='admin'?'selected':'' }}>Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Position in Org</label>
                <input type="text" name="position" value="{{ old('position', $user->position) }}"
                       placeholder="e.g. President, Secretary, Member"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"/>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Org Type</label>
                <select name="org_type" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select</option>
                    @foreach(['Student Council','Academic Org','Cultural Org','Sports Org','Religious Org','Publication','Other'] as $t)
                    <option value="{{ $t }}" {{ old('org_type',$user->org_type)==$t?'selected':'' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">College</label>
                <select name="college" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select</option>
                    @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS','University-Wide'] as $c)
                    <option value="{{ $c }}" {{ old('college',$user->college)==$c?'selected':'' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">SC President</label>
                <input type="text" name="sc_president" value="{{ old('sc_president', $user->sc_president) }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"/>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Term</label>
                <select name="term" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300">
                    <option value="">Select</option>
                    <option value="1st Term" {{ old('term',$user->term)=='1st Term'?'selected':'' }}>1st Term</option>
                    <option value="2nd Term" {{ old('term',$user->term)=='2nd Term'?'selected':'' }}>2nd Term</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">School Year</label>
                <input type="text" name="school_year" value="{{ old('school_year', $user->school_year) }}"
                       placeholder="e.g. 2025-2026"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-300"/>
            </div>
        </div>

        <div class="flex gap-3 pt-2 border-t">
            <button type="submit" class="px-5 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">Save Changes</button>
            <a href="{{ route('admin.users.index') }}" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
