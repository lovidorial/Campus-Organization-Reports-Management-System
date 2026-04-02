<x-app-layout>
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.organizations.index') }}" class="text-gray-400 hover:text-gray-600">← Back</a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $organization->name }}</h2>
            <div class="flex flex-wrap gap-2 mt-1">
                <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $organization->type }}</span>
                @if($organization->college)
                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full">{{ $organization->college }}</span>
                @endif
                <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $organization->term ?? '—' }}</span>
                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">SY {{ $organization->school_year ?? '—' }}</span>
                @if($organization->is_active)
                <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 rounded-full font-semibold">● Active</span>
                @endif
            </div>
        </div>
    </div>
    <a href="{{ route('admin.organizations.edit', $organization) }}"
       class="px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-semibold hover:bg-yellow-600">Edit Org</a>
</div>

<!-- Org Info + SC Pres -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">SC President / Head</p>
        <p class="text-lg font-bold text-gray-800">{{ $organization->sc_president ?? 'Not set' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Members</p>
        <p class="text-lg font-bold text-gray-800">{{ $members->total() }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Description</p>
        <p class="text-sm text-gray-600">{{ $organization->description ?? 'No description.' }}</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-4 rounded-xl shadow-sm border text-center">
        <p class="text-xs text-gray-500 uppercase">Total Activities</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white p-4 rounded-xl shadow-sm border text-center">
        <p class="text-xs text-gray-500 uppercase">Approved</p>
        <p class="text-3xl font-bold text-green-500 mt-1">{{ $stats['approved'] }}</p>
    </div>
    <div class="bg-white p-4 rounded-xl shadow-sm border text-center">
        <p class="text-xs text-gray-500 uppercase">Pending</p>
        <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-white p-4 rounded-xl shadow-sm border text-center">
        <p class="text-xs text-gray-500 uppercase">Rejected</p>
        <p class="text-3xl font-bold text-red-500 mt-1">{{ $stats['rejected'] }}</p>
    </div>
</div>

<!-- Org Chart / Members Grid -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">
    <h3 class="font-bold text-gray-700 mb-4">Org Structure / Members</h3>

    <!-- SC President at top -->
    <div class="flex flex-col items-center mb-6">
        <div class="bg-sky-600 text-white rounded-xl px-6 py-3 text-center shadow">
            <p class="text-xs opacity-80">SC President</p>
            <p class="font-bold">{{ $organization->sc_president ?? 'Not set' }}</p>
        </div>
        <div class="w-px h-8 bg-gray-300"></div>
    </div>

    @if($members->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        @foreach($members as $member)
        <div class="flex flex-col items-center bg-gray-50 hover:bg-sky-50 rounded-xl p-3 border border-gray-200 transition text-center">
            @if($member->profile_photo_path)
                <img src="{{ asset('storage/'.$member->profile_photo_path) }}" alt="{{ $member->name }}"
                     class="w-14 h-14 rounded-full object-cover mb-2 border-2 border-white shadow"/>
            @else
                <div class="w-14 h-14 rounded-full bg-sky-500 text-white flex items-center justify-center text-xl font-bold mb-2">
                    {{ substr($member->name,0,1) }}
                </div>
            @endif
            <p class="text-sm font-semibold text-gray-800 leading-tight">{{ $member->name }}</p>
            <p class="text-xs text-sky-600 font-medium mt-0.5">{{ $member->position ?? 'Member' }}</p>
            <p class="text-xs text-gray-400">{{ $member->activities()->count() }} activities</p>
        </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $members->links() }}</div>
    @else
        <p class="text-center text-gray-400 py-6">No members assigned to this organization yet.</p>
    @endif
</div>

<!-- Accomplishments / Activities Table -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <h3 class="font-bold text-gray-700 mb-4">Accomplishments / Activities Submitted</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-3 text-left text-gray-500">Title</th>
                    <th class="p-3 text-left text-gray-500">Submitted By</th>
                    <th class="p-3 text-left text-gray-500">Category</th>
                    <th class="p-3 text-left text-gray-500">Date</th>
                    <th class="p-3 text-left text-gray-500">Basis</th>
                    <th class="p-3 text-left text-gray-500">Term / SY</th>
                    <th class="p-3 text-left text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                <tr class="border-b last:border-0 hover:bg-gray-50">
                    <td class="p-3 font-medium">{{ $act->title }}</td>
                    <td class="p-3 text-gray-500">{{ $act->user->name ?? '—' }}</td>
                    <td class="p-3">
                        @if($act->category)
                        <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $act->category }}</span>
                        @else —@endif
                    </td>
                    <td class="p-3">{{ $act->date->format('M d, Y') }}</td>
                    <td class="p-3 text-xs text-gray-500">{{ $act->basis_grading ?? '—' }}</td>
                    <td class="p-3 text-xs text-gray-500">{{ $act->term ?? '—' }} / {{ $act->school_year ?? '—' }}</td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded-full text-xs font-bold
                            {{ $act->status=='pending'  ?'bg-yellow-100 text-yellow-700':'' }}
                            {{ $act->status=='approved' ?'bg-green-100 text-green-700':'' }}
                            {{ $act->status=='rejected' ?'bg-red-100 text-red-700':'' }}">
                            {{ ucfirst($act->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="p-6 text-center text-gray-400">No activities yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $activities->links() }}</div>
</div>
</x-app-layout>
