<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Organizations</h2>
        <p class="text-sm text-gray-500">Manage registered organizations and their structure</p>
    </div>
    <a href="{{ route('admin.users.create') }}"
       class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700 transition">+ Add Organization</a>
</div>

<!-- Search/Filter -->
<form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" placeholder="Search by name, type, college..."
           value="{{ request('search') }}" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[200px]"/>
    <select name="type" class="border rounded-lg px-3 py-2 text-sm">
        <option value="">All Types</option>
        <option value="Student Council" {{ request('type')=='Student Council'?'selected':'' }}>Student Council</option>
        <option value="Academic Org"    {{ request('type')=='Academic Org'   ?'selected':'' }}>Academic Org</option>
        <option value="Cultural Org"    {{ request('type')=='Cultural Org'   ?'selected':'' }}>Cultural Org</option>
        <option value="Sports Org"      {{ request('type')=='Sports Org'     ?'selected':'' }}>Sports Org</option>
        <option value="Religious Org"   {{ request('type')=='Religious Org'  ?'selected':'' }}>Religious Org</option>
        <option value="Publication"     {{ request('type')=='Publication'    ?'selected':'' }}>Publication</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm hover:bg-sky-700">Filter</button>
    <a href="{{ route('admin.organizations.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Reset</a>
</form>

<div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left text-gray-500">#</th>
                <th class="p-3 text-left text-gray-500">Organization Name</th>
                <th class="p-3 text-left text-gray-500">Type</th>
                <th class="p-3 text-left text-gray-500">College</th>
                <th class="p-3 text-left text-gray-500">Contact Person</th>
                <th class="p-3 text-left text-gray-500">Term / SY</th>
                <th class="p-3 text-left text-gray-500">Activities</th>
                <th class="p-3 text-left text-gray-500">Status</th>
                <th class="p-3 text-center text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($organizations as $org)
            <tr class="border-b last:border-0 hover:bg-gray-50">
                <td class="p-3 text-gray-400">{{ $loop->iteration }}</td>
                <td class="p-3 font-semibold text-gray-800">{{ $org->org_name ?? $org->name }}</td>
                <td class="p-3">
                    <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $org->org_type ?? 'General' }}</span>
                </td>
                <td class="p-3">{{ $org->college ?? '—' }}</td>
                <td class="p-3">{{ $org->position ?? $org->name ?? '—' }}</td>
                <td class="p-3 text-xs text-gray-500">{{ $org->term ?? '—' }}<br>{{ $org->school_year ?? '—' }}</td>
                <td class="p-3">
                    <span class="font-bold text-gray-700">{{ $org->activities_count }}</span>
                </td>
                <td class="p-3">
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">Active</span>
                </td>
                <td class="p-3">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.users.index') }}?search={{ $org->org_name }}"
                           class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs hover:bg-blue-100 font-semibold">View</a>
                        <a href="{{ route('admin.users.edit', $org) }}"
                           class="px-2 py-1 bg-yellow-50 text-yellow-700 rounded text-xs hover:bg-yellow-100 font-semibold">Edit</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="p-8 text-center text-gray-400">No organizations found. <a href="{{ route('admin.users.create') }}" class="text-sky-600 hover:underline">Add one</a>.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $organizations->links() }}</div>
</x-app-layout>
