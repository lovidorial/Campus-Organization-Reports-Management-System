<x-app-layout>
    @php
        $isAdminView = request()->routeIs('admin.*');
        $currentRoute = $isAdminView ? 'admin.officers.index' : 'organization.officers.index';
        $historyRoute = $isAdminView ? 'admin.officers.history' : 'organization.officers.history';
    @endphp

    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Officer History</h2>
            <p class="text-sm text-gray-500">Archived officers retained as historical records</p>
        </div>
        <a href="{{ route($currentRoute) }}" class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">Current Officers</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route($historyRoute) }}" class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
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
                <a href="{{ route($historyRoute) }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300">Reset</a>
            </div>
        </div>
    </form>

    @forelse($history as $period => $officers)
        <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 font-semibold text-gray-700">{{ $period }}</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-white text-left text-gray-500">
                            <th class="p-3">Officer</th>
                            <th class="p-3">Organization</th>
                            <th class="p-3">Position</th>
                            <th class="p-3">College</th>
                            <th class="p-3">Archived</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($officers as $officer)
                            <tr class="border-t border-gray-200 hover:bg-gray-50">
                                <td class="p-3">
                                    <div class="font-semibold text-gray-800">{{ $officer->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $officer->email }}</div>
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ $officer->org_name ?? $officer->organization?->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="p-3 text-gray-700">{{ $officer->position ?? '—' }}</td>
                                <td class="p-3 text-gray-700">{{ $officer->college ?? '—' }}</td>
                                <td class="p-3">
                                    <div class="text-xs text-gray-600">{{ $officer->archived_at?->format('M d, Y') ?? '—' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $officer->archived_reason ?? 'No reason provided' }}</div>
                                </td>
                                <td class="p-3 text-center">
                                    <form method="POST" action="{{ route($isAdminView ? 'admin.officers.restore' : 'organization.officers.restore', $officer) }}" onsubmit="return confirm('Restore {{ $officer->name }} to active status?')">
                                        @csrf
                                        <button type="submit" class="rounded bg-green-50 px-2 py-1 text-xs font-semibold text-green-700 hover:bg-green-100">Restore</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center text-gray-400 sm:p-8">No archived officers found.</div>
    @endforelse
</x-app-layout>
