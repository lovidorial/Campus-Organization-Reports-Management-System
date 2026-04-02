<x-app-layout>
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manage Users</h2>
        <p class="text-sm text-gray-500">Edit member profiles, positions, and org assignments</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition shadow-md">
        <i class="fas fa-plus-circle me-2"></i>Create User
    </a>
</div>

@if(session('success'))
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
@endif

<div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
    <table class="w-full text-sm min-w-[800px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left text-gray-500">Member</th>
                <th class="p-3 text-left text-gray-500">Email</th>
                <th class="p-3 text-left text-gray-500">Organization</th>
                <th class="p-3 text-left text-gray-500">Position</th>
                <th class="p-3 text-left text-gray-500">College</th>
                <th class="p-3 text-left text-gray-500">Term / SY</th>
                <th class="p-3 text-left text-gray-500">Activities</th>
                <th class="p-3 text-center text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr class="border-b last:border-0 hover:bg-gray-50">
                <td class="p-3">
                    <div class="flex items-center gap-3">
                        @if($user->profile_photo_path)
                            <img src="{{ asset('storage/'.$user->profile_photo_path) }}" class="w-9 h-9 rounded-full object-cover"/>
                        @else
                            <div class="w-9 h-9 rounded-full bg-sky-500 text-white flex items-center justify-center font-bold text-sm">
                                {{ substr($user->name,0,1) }}
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-gray-800">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->role }}</p>
                        </div>
                    </div>
                </td>
                <td class="p-3 text-gray-500">{{ $user->email }}</td>
                <td class="p-3">
                    @php
                        // Extract organization from user name (e.g., "CTE-SC" from "CTE-SC" or "CICS-SC")
                        $orgName = null;
                        if($user->organization) {
                            $orgName = $user->organization->name;
                        } elseif($user->org_name) {
                            $orgName = $user->org_name;
                        } else {
                            // Try to extract from the name - look for pattern like "ABC-SC" or similar
                            if(preg_match('/^([A-Z]+(?:-[A-Z]+)*)\s*(?:SC|Student Council)?/i', $user->name, $matches)) {
                                $orgName = $matches[1];
                            }
                        }
                    @endphp
                    @if($orgName)
                        <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1.5 rounded-full font-semibold">{{ $orgName }}</span>
                    @else
                        <span class="text-gray-300 text-xs">—</span>
                    @endif
                </td>
                <td class="p-3 text-sm text-gray-600">{{ $user->position ?? '—' }}</td>
                <td class="p-3 text-sm text-gray-600">{{ $user->college ?? '—' }}</td>
                <td class="p-3 text-xs text-gray-500">
                    {{ $user->term ?? '—' }}<br>{{ $user->school_year ?? '—' }}
                </td>
                <td class="p-3 font-bold text-gray-700">{{ $user->activities_count ?? $user->activities()->count() }}</td>
                <td class="p-3">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="px-2 py-1 bg-yellow-50 text-yellow-700 rounded text-xs hover:bg-yellow-100 font-semibold">Edit</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline"
                              onsubmit="return confirm('Delete {{ $user->name }}?')">
                            @csrf @method('DELETE')
                            <button class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs hover:bg-red-100 font-semibold">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="p-8 text-center text-gray-400">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>

