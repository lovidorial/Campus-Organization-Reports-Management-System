<x-app-layout>

<!-- User Profile Banner -->
<div class="rounded-xl p-6 mb-6 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-4" style="background: linear-gradient(135deg, #f5a623 0%, #e89600 100%)">
    <div class="flex items-center gap-4">
        @if(auth()->user()->profile_photo_path)
            <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}"
                 class="w-16 h-16 rounded-full object-cover border-3 border-white shadow"/>
        @else
            <div class="w-16 h-16 rounded-full bg-white text-orange-500 flex items-center justify-center text-2xl font-bold shadow" style="color: #f5a623;">
                {{ substr(auth()->user()->name,0,1) }}
            </div>
        @endif
        <div>
            <h2 class="text-xl font-bold">Welcome, {{ auth()->user()->name }}!</h2>
            @if(auth()->user()->position || auth()->user()->org_name || (auth()->user()->organization))
            <p class="text-sky-200 text-sm">
                {{ auth()->user()->position ?? 'Member' }}
                @if(auth()->user()->organization)
                    — {{ auth()->user()->organization->name }}
                @elseif(auth()->user()->org_name)
                    — {{ auth()->user()->org_name }}
                @endif
            </p>
            @endif
        </div>
    </div>
    <div class="flex flex-wrap gap-2 text-sm">
        @if(auth()->user()->term)
        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full"> {{ auth()->user()->term }}</span>
        @endif
        @if(auth()->user()->school_year)
        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full"> SY {{ auth()->user()->school_year }}</span>
        @endif
        @if(auth()->user()->college)
        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full"> {{ auth()->user()->college }}</span>
        @endif
    </div>
</div>

@if(auth()->user()->isAdmin())
<!-- Admin quick stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Total</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Pending</p>
        <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Approved</p>
        <p class="text-3xl font-bold text-green-500 mt-1">{{ $stats['approved'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Rejected</p>
        <p class="text-3xl font-bold text-red-500 mt-1">{{ $stats['rejected'] }}</p>
    </div>
</div>
@endif

<!-- Recent Activities -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">
            {{ auth()->user()->isAdmin() ? 'Recent Submissions' : 'My Recent Activities' }}
        </h3>
        @if(!auth()->user()->isAdmin())
        <a href="{{ route('gpoa.create') }}"
           class="px-3 py-1.5 text-white text-xs rounded-lg font-semibold transition"
           style="background-color: #f5a623; " onmouseover="this.style.backgroundColor='#e89600'" onmouseout="this.style.backgroundColor='#f5a623'">
            + Submit Activity
        </a>
        @endif
    </div>

    @if($activities->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[500px]">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-2 text-gray-500 font-semibold">Title</th>
                    <th class="text-left py-2 text-gray-500 font-semibold">Category</th>
                    <th class="text-left py-2 text-gray-500 font-semibold">Date</th>
                    <th class="text-left py-2 text-gray-500 font-semibold">Term / SY</th>
                    <th class="text-left py-2 text-gray-500 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities->take(5) as $activity)
                <tr class="border-b last:border-0 hover:bg-gray-50">
                    <td class="py-3 font-medium max-w-[180px] truncate" title="{{ $activity->title }}">{{ $activity->title }}</td>
                    <td class="py-3">
                        @if($activity->category)
                        <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $activity->category }}</span>
                        @else <span class="text-gray-300 text-xs">—</span> @endif
                    </td>
                    <td class="py-3">{{ $activity->date->format('M d, Y') }}</td>
                    <td class="py-3 text-xs text-gray-500">{{ $activity->term ?? '—' }} / {{ $activity->school_year ?? '—' }}</td>
                    <td class="py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-bold
                            {{ $activity->status == 'pending'  ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $activity->status == 'approved' ? 'bg-green-100 text-green-700'  : '' }}
                            {{ $activity->status == 'rejected' ? 'bg-red-100 text-red-700'      : '' }}">
                            {{ ucfirst($activity->status) }}
                        </span>
                        @if($activity->status == 'rejected' && $activity->reject_reason)
                        <p class="text-xs text-red-500 mt-0.5"> {{ Str::limit($activity->reject_reason, 40) }}</p>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(!auth()->user()->isAdmin())
    <div class="mt-3">
        <a href="{{ route('user.activities') }}" class="text-sm font-medium transition" style="color: #f5a623;">View all activities →</a>
    </div>
    @endif
    @else
    <div class="py-10 text-center">
        <p class="text-gray-400 mb-3">No activities submitted yet.</p>
        @if(!auth()->user()->isAdmin())
        <a href="{{ route('gpoa.create') }}"
           class="px-4 py-2 text-white rounded-lg text-sm font-semibold transition"
           style="background-color: #f5a623;" onmouseover="this.style.backgroundColor='#e89600'" onmouseout="this.style.backgroundColor='#f5a623'">
            Submit Your First Activity
        </a>
        @endif
    </div>
    @endif
</div>
</x-app-layout>
