<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">My Activities</h2>
        <p class="text-sm text-gray-500">Track all your submitted activity reports</p>
    </div>
    <a href="{{ route('gpoa.create') }}"
       class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700 transition">+ Submit Activity</a>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
        <p class="text-xs text-gray-500 uppercase font-semibold">Total</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $activities->total() }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
        <p class="text-xs text-gray-500 uppercase font-semibold">Approved</p>
        <p class="text-2xl font-bold text-green-500 mt-1">
            {{ \App\Models\Activity::where('user_id', auth()->id())->where('status','approved')->count() }}
        </p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
        <p class="text-xs text-gray-500 uppercase font-semibold">Pending</p>
        <p class="text-2xl font-bold text-yellow-500 mt-1">
            {{ \App\Models\Activity::where('user_id', auth()->id())->where('status','pending')->count() }}
        </p>
    </div>
</div>

<!-- User Profile / Org Info -->
@if(auth()->user()->organization || auth()->user()->org_name)
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6 flex flex-wrap gap-4 items-center">
    <div>
        <p class="text-xs text-gray-500 uppercase font-semibold">Organization</p>
        <p class="font-bold text-gray-800">
            {{ auth()->user()->organization->name ?? auth()->user()->org_name }}
        </p>
    </div>
    @if(auth()->user()->position)
    <div>
        <p class="text-xs text-gray-500 uppercase font-semibold">Position</p>
        <p class="font-bold text-gray-800">{{ auth()->user()->position }}</p>
    </div>
    @endif
    @if(auth()->user()->term)
    <div>
        <p class="text-xs text-gray-500 uppercase font-semibold">Term</p>
        <p class="font-bold text-gray-800">{{ auth()->user()->term }}</p>
    </div>
    @endif
    @if(auth()->user()->school_year)
    <div>
        <p class="text-xs text-gray-500 uppercase font-semibold">School Year</p>
        <p class="font-bold text-gray-800">{{ auth()->user()->school_year }}</p>
    </div>
    @endif
</div>
@endif

<!-- Activities Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Title</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Organization</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Category</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Term / SY</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Basis</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Files</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($activities as $item)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4 font-medium max-w-[180px] truncate" title="{{ $item->title }}">{{ $item->title }}</td>
                <td class="px-4 py-4 text-gray-500">
                    @if($item->user)
                        {{ $item->user->org_name ?? $item->user->name }}
                    @else
                        {{ $item->organization ?? '—' }}
                    @endif
                </td>
                <td class="px-4 py-4">
                    @if($item->category)
                    <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $item->category }}</span>
                    @else <span class="text-gray-300">—</span> @endif
                </td>
                <td class="px-4 py-4">{{ $item->date->format('M d, Y') }}</td>
                <td class="px-4 py-4 text-xs text-gray-500">{{ $item->term ?? '—' }}<br>{{ $item->school_year ?? '—' }}</td>
                <td class="px-4 py-4 text-xs text-gray-500">{{ $item->basis_grading ?? '—' }}</td>
                <td class="px-4 py-4">
                    @if($item->communication_letter)
                        <a href="{{ asset('storage/'.$item->communication_letter) }}" target="_blank"
                           class="text-sky-600 hover:underline mr-2 text-xs">Comm</a>
                    @endif
                    @if($item->narrative_report)
                        <a href="{{ asset('storage/'.$item->narrative_report) }}" target="_blank"
                           class="text-green-600 hover:underline text-xs">Narr</a>
                    @endif
                    @if(!$item->communication_letter && !$item->narrative_report)
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-4">
                    <span class="px-2 py-1 rounded-full text-xs font-bold
                        {{ $item->status == 'pending'  ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $item->status == 'approved' ? 'bg-green-100 text-green-700'  : '' }}
                        {{ $item->status == 'rejected' ? 'bg-red-100 text-red-700'      : '' }}">
                        {{ ucfirst($item->status) }}
                    </span>
                    @if($item->status == 'rejected' && $item->reject_reason)
                    <p class="text-xs text-red-500 mt-1 max-w-[180px]" title="{{ $item->reject_reason }}">
                        💬 {{ Str::limit($item->reject_reason, 50) }}
                    </p>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                    No activities submitted yet.
                    <a href="{{ route('gpoa.create') }}" class="text-sky-600 hover:underline ml-1">Submit your first activity →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $activities->links() }}</div>
</x-app-layout>
