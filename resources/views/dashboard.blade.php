<x-app-layout>

<div class="rounded-xl p-6 mb-6 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-4" style="background: linear-gradient(135deg, #f5a623 0%, #e89600 100%)">
    <div class="flex items-center gap-4">
        @if(auth()->user()->profile_photo_path)
            <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}"
                 class="w-16 h-16 rounded-full object-cover border-3 border-white shadow"/>
        @else
            <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-2xl font-bold shadow" style="color: #f5a623;">
                {{ substr(auth()->user()->name,0,1) }}
            </div>
        @endif
        <div>
            <h2 class="text-xl font-bold">Welcome, {{ auth()->user()->name }}!</h2>
            @if(auth()->user()->org_name || auth()->user()->organization)
            <p class="text-sky-200 text-sm">
                {{ auth()->user()->position ?? 'Member' }}
                — {{ auth()->user()->organization->name ?? auth()->user()->org_name }}
            </p>
            @endif
        </div>
    </div>
    <div class="flex flex-wrap gap-2 text-sm">
        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full">{{ $term }}</span>
        <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full">SY {{ $schoolYear }}</span>
    </div>
</div>

<!-- GPOA Status Banner -->
@if($hasApprovedGpoa)
<div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex flex-wrap justify-between items-center gap-2">
    <span>✓ GPOA approved for {{ $term }} / SY {{ $schoolYear }}. You may submit activity requests.</span>
    <a href="{{ route('activity-requests.create') }}" class="px-3 py-1 bg-green-600 text-white rounded text-sm font-semibold hover:bg-green-700">Request Activity</a>
</div>
@else
<div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg flex flex-wrap justify-between items-center gap-2">
    <span>Submit and get your GPOA approved before requesting activities.</span>
    <a href="{{ route('gpoa.create') }}" class="px-3 py-1 bg-yellow-600 text-white rounded text-sm font-semibold hover:bg-yellow-700">Submit GPOA</a>
</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Requests</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Pending</p>
        <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Active</p>
        <p class="text-3xl font-bold text-green-500 mt-1">{{ $stats['approved'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <p class="text-xs text-gray-500 uppercase font-semibold">Rejected</p>
        <p class="text-3xl font-bold text-red-500 mt-1">{{ $stats['rejected'] }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Recent Activity Requests</h3>
        <a href="{{ route('activity-requests.index') }}" class="text-sm font-medium" style="color: #f5a623;">View all →</a>
    </div>

    @if($activities->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[500px]">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-2 text-gray-500 font-semibold">Title</th>
                    <th class="text-left py-2 text-gray-500 font-semibold">Date</th>
                    <th class="text-left py-2 text-gray-500 font-semibold">Venue</th>
                    <th class="text-left py-2 text-gray-500 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities->take(5) as $activity)
                <tr class="border-b last:border-0 hover:bg-gray-50">
                    <td class="py-3 font-medium">{{ $activity->title }}</td>
                    <td class="py-3">{{ $activity->date->format('M d, Y') }}</td>
                    <td class="py-3">{{ $activity->venue }}</td>
                    <td class="py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-gray-100">
                            {{ str_replace('_', ' ', ucfirst($activity->status)) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="py-10 text-center">
        <p class="text-gray-400 mb-3">No activity requests yet.</p>
        @if($hasApprovedGpoa)
        <a href="{{ route('activity-requests.create') }}" class="px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background-color: #f5a623;">Request Your First Activity</a>
        @else
        <a href="{{ route('gpoa.create') }}" class="px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background-color: #f5a623;">Submit GPOA First</a>
        @endif
    </div>
    @endif
</div>
</x-app-layout>
