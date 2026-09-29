<x-app-layout>
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">GPOA Monitoring</h2>
    <p class="text-sm text-gray-500">Browse organizational plans and planned activities</p>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-lg border bg-white p-4"><p class="text-xs uppercase text-gray-500">GPOAs</p><p class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</p></div>
</div>

<form method="GET" class="bg-white rounded-xl border p-4 mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" placeholder="Search org, term, SY..." value="{{ request('search') }}" class="border rounded px-3 py-2 text-sm flex-1 min-w-[200px]">
    <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded text-sm">Filter</button>
</form>

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Organization</th>
                <th class="p-3 text-left">Term / SY</th>
                <th class="p-3 text-left">College</th>
                <th class="p-3 text-left">Planned Activities</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gpoas as $gpoa)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3">{{ $gpoa->user->org_name ?? $gpoa->user->name }}</td>
                <td class="p-3">{{ $gpoa->term }}<br><span class="text-xs text-gray-500">{{ $gpoa->school_year }}</span></td>
                <td class="p-3">{{ $gpoa->college ?? '—' }}</td>
                <td class="p-3">{{ $gpoa->activities_count }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="p-8 text-center text-gray-400">No GPOAs found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $gpoas->links() }}</div>
</x-app-layout>
