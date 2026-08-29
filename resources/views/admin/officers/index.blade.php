<x-app-layout>
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Current Officers</h2>
            <p class="text-sm text-gray-500">Active officers in the current org chart</p>
        </div>
        <a href="{{ route('admin.officers.history') }}" class="px-4 py-2 bg-slate-700 text-white rounded-lg text-sm font-semibold hover:bg-slate-800">Officer History</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.officers.index') }}" class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <select name="organization_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                <option value="">All Organizations</option>
                @foreach($organizations as $organization)
                    <option value="{{ $organization->organization_id }}" {{ request('organization_id') == $organization->organization_id ? 'selected' : '' }}>
                        {{ $organization->org_name ?? 'Unknown Organization' }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="term" value="{{ request('term') }}" placeholder="Term" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
            <input type="text" name="school_year" value="{{ request('school_year') }}" placeholder="School Year" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Filter</button>
                <a href="{{ route('admin.officers.index') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">Reset</a>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-3 text-left text-gray-500">Officer</th>
                    <th class="p-3 text-left text-gray-500">Organization</th>
                    <th class="p-3 text-left text-gray-500">Position</th>
                    <th class="p-3 text-left text-gray-500">Term / SY</th>
                    <th class="p-3 text-left text-gray-500">College</th>
                    <th class="p-3 text-center text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($officers as $officer)
                    <tr class="border-t border-gray-200 hover:bg-gray-50">
                        <td class="p-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-500 text-sm font-bold text-white">
                                    {{ strtoupper(substr($officer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">{{ $officer->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $officer->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                                {{ $officer->org_name ?? $officer->organization?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="p-3 text-gray-700">{{ $officer->position ?? '—' }}</td>
                        <td class="p-3 text-gray-700">{{ $officer->term ?? '—' }}<br><span class="text-xs text-gray-500">{{ $officer->school_year ?? '—' }}</span></td>
                        <td class="p-3 text-gray-700">{{ $officer->college ?? '—' }}</td>
                        <td class="p-3 text-center">
                            <form method="POST" action="{{ route('admin.officers.archive', $officer) }}" onsubmit="return confirm('Archive {{ $officer->name }}? This will block their login but keep their records intact.')">
                                @csrf
                                <input type="hidden" name="archived_reason" value="Term ended">
                                <button type="submit" class="rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">Archive</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400">No active officers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
