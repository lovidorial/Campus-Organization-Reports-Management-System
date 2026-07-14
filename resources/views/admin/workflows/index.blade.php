<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Organization Workflows</h2>
        <p class="text-sm text-gray-500">Track all organization document submissions and workflow progress</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.workflows.export') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Export CSV</a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl border shadow-sm"><p class="text-xs text-gray-500 uppercase">Total</p><p class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</p></div>
    <div class="bg-white p-4 rounded-xl border shadow-sm"><p class="text-xs text-gray-500 uppercase">Completed</p><p class="text-2xl font-bold text-green-500">{{ $stats['completed'] }}</p></div>
    <div class="bg-white p-4 rounded-xl border shadow-sm"><p class="text-xs text-gray-500 uppercase">In Progress</p><p class="text-2xl font-bold text-orange-500">{{ $stats['pending'] }}</p></div>
    <div class="bg-white p-4 rounded-xl border shadow-sm"><p class="text-xs text-gray-500 uppercase">Not Started</p><p class="text-2xl font-bold text-gray-500">{{ $stats['not_started'] }}</p></div>
    <div class="bg-white p-4 rounded-xl border shadow-sm"><p class="text-xs text-gray-500 uppercase">Overdue (30d+)</p><p class="text-2xl font-bold text-red-500">{{ $stats['overdue'] }}</p></div>
</div>

<!-- Pending Reviews -->
@if($pendingSubmissions->count())
<div class="bg-orange-50 border border-orange-200 rounded-xl p-5 mb-6">
    <h3 class="font-bold text-orange-800 mb-3">Pending Reviews ({{ $pendingSubmissions->count() }})</h3>
    <div class="space-y-2">
        @foreach($pendingSubmissions as $sub)
        <div class="flex flex-wrap justify-between items-center gap-2 bg-white p-3 rounded-lg border">
            <div>
                <span class="font-semibold text-gray-800">{{ $sub->workflow->user->org_name ?? $sub->workflow->user->name }}</span>
                <span class="text-sm text-gray-500">— {{ $sub->documentLabel() }}</span>
                <span class="text-xs text-gray-400 ml-2">Submitted {{ $sub->submitted_at?->diffForHumans() }}</span>
            </div>
            <a href="{{ route('admin.workflows.show', $sub->workflow) }}" class="text-xs px-3 py-1 bg-orange-600 text-white rounded font-semibold hover:bg-orange-700">Review</a>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Filters -->
<form method="GET" class="bg-white rounded-xl border p-4 mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" placeholder="Search organization..." value="{{ request('search') }}" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[200px]">
    <select name="status" class="border rounded-lg px-3 py-2 text-sm">
        <option value="">All Status</option>
        <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
        <option value="pending" {{ request('status')=='pending'?'selected':'' }}>In Progress</option>
        <option value="overdue" {{ request('status')=='overdue'?'selected':'' }}>Overdue</option>
    </select>
    <select name="stage" class="border rounded-lg px-3 py-2 text-sm">
        <option value="">All Stages</option>
        @foreach(['gpoa_pending','gpoa_submitted','gpoa_approved','comm_submitted','comm_approved','summary_submitted','summary_approved','completed'] as $s)
        <option value="{{ $s }}" {{ request('stage')==$s?'selected':'' }}>{{ str_replace('_',' ',ucfirst($s)) }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm hover:bg-sky-700">Filter</button>
    <a href="{{ route('admin.workflows.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm">Reset</a>
</form>

<!-- Workflows Table -->
<div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
    <table class="w-full text-sm min-w-[800px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 text-gray-500">Organization</th>
                <th class="text-left px-4 py-3 text-gray-500">Term / SY</th>
                <th class="text-left px-4 py-3 text-gray-500">Current Stage</th>
                <th class="text-left px-4 py-3 text-gray-500">Progress</th>
                <th class="text-left px-4 py-3 text-gray-500">Status</th>
                <th class="text-left px-4 py-3 text-gray-500">Last Updated</th>
                <th class="text-center px-4 py-3 text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($workflows as $workflow)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4 font-medium">{{ $workflow->user->org_name ?? $workflow->user->name }}</td>
                <td class="px-4 py-4 text-xs">{{ $workflow->term }}<br>{{ $workflow->school_year }}</td>
                <td class="px-4 py-4 text-xs">{{ str_replace('_', ' ', ucfirst($workflow->current_stage)) }}</td>
                <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                        <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width:{{ $workflow->completion_percentage }}%;background:#e89600;"></div>
                        </div>
                        <span class="text-xs font-semibold">{{ $workflow->completion_percentage }}%</span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    @if($workflow->is_completed)
                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 font-bold">Completed</span>
                    @elseif($workflow->is_locked)
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 font-bold">Locked</span>
                    @else
                    <span class="text-xs px-2 py-1 rounded-full bg-orange-100 text-orange-700 font-bold">In Progress</span>
                    @endif
                </td>
                <td class="px-4 py-4 text-xs text-gray-500">{{ $workflow->updated_at->format('M d, Y') }}</td>
                <td class="px-4 py-4 text-center">
                    <a href="{{ route('admin.workflows.show', $workflow) }}" class="text-sky-600 hover:underline text-xs font-semibold">View Details</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No workflows found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $workflows->links() }}</div>
</x-app-layout>
