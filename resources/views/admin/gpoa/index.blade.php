<x-app-layout>
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">GPOA Review</h2>
    <p class="text-sm text-gray-500">Verify, approve, and store organization GPOA submissions</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg border"><p class="text-xs text-gray-500 uppercase">Total</p><p class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</p></div>
    <div class="bg-white p-4 rounded-lg border"><p class="text-xs text-gray-500 uppercase">Pending</p><p class="text-2xl font-bold text-yellow-500">{{ $stats['pending'] }}</p></div>
    <div class="bg-white p-4 rounded-lg border"><p class="text-xs text-gray-500 uppercase">Approved/Stored</p><p class="text-2xl font-bold text-green-500">{{ $stats['approved'] }}</p></div>
    <div class="bg-white p-4 rounded-lg border"><p class="text-xs text-gray-500 uppercase">Rejected</p><p class="text-2xl font-bold text-red-500">{{ $stats['rejected'] }}</p></div>
</div>

<form method="GET" class="bg-white rounded-xl border p-4 mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" placeholder="Search org, term, SY..." value="{{ request('search') }}" class="border rounded px-3 py-2 text-sm flex-1 min-w-[200px]">
    <select name="status" class="border rounded px-3 py-2 text-sm">
        <option value="">All Status</option>
        @foreach(['pending','stored','approved','rejected'] as $s)
        <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded text-sm">Filter</button>
</form>

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Organization</th>
                <th class="p-3 text-left">Term / SY</th>
                <th class="p-3 text-left">College</th>
                <th class="p-3 text-left">Activities</th>
                <th class="p-3 text-left">Document</th>
                <th class="p-3 text-left">Status</th>
                <th class="p-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gpoas as $gpoa)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3">{{ $gpoa->user->org_name ?? $gpoa->user->name }}</td>
                <td class="p-3">{{ $gpoa->term }}<br><span class="text-xs text-gray-500">{{ $gpoa->school_year }}</span></td>
                <td class="p-3">{{ $gpoa->college ?? '—' }}</td>
                <td class="p-3">{{ $gpoa->activities_count }}</td>
                <td class="p-3">
                    @if($gpoa->document_path)
                    <a href="{{ route('admin.gpoa.document', $gpoa) }}" target="_blank" class="text-blue-600 text-xs hover:underline">View PDF</a>
                    @else — @endif
                </td>
                <td class="p-3">
                    <span class="px-2 py-1 rounded-full text-xs font-bold
                        {{ $gpoa->status=='pending'?'bg-yellow-100 text-yellow-700':'' }}
                        {{ in_array($gpoa->status,['approved','stored'])?'bg-green-100 text-green-700':'' }}
                        {{ $gpoa->status=='rejected'?'bg-red-100 text-red-700':'' }}">
                        {{ ucfirst($gpoa->status) }}
                    </span>
                </td>
                <td class="p-3 text-center">
                    <a href="{{ route('admin.gpoa.show', $gpoa) }}" class="text-sky-600 text-xs font-semibold hover:underline">Review</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="p-8 text-center text-gray-400">No GPOA submissions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $gpoas->links() }}</div>
</x-app-layout>
