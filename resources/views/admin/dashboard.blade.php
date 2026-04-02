<x-app-layout>
<style>
.stat-card { transition: transform .15s; }
.stat-card:hover { transform: translateY(-2px); }
</style>

<!-- Header Row: Term / SY / SC Pres -->
<div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-3">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Admin Dashboard</h2>
        <p class="text-sm text-gray-500">CORMS — Co-Curricular Organization Activity Tracking System</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
    <div class="stat-card bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Total</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Activities</p>
    </div>
    <div class="stat-card bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Approved</p>
        <p class="text-3xl font-bold text-green-500 mt-1">{{ $stats['approved'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Activities</p>
    </div>
    <div class="stat-card bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Pending</p>
        <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['pending'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Activities</p>
    </div>
    <div class="stat-card bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Rejected</p>
        <p class="text-3xl font-bold text-red-500 mt-1">{{ $stats['rejected'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Activities</p>
    </div>
    <div class="stat-card bg-white p-5 rounded-xl shadow-sm border border-gray-200">
        <p class="text-xs text-gray-500 uppercase font-semibold">Organizations</p>
        <p class="text-3xl font-bold text-purple-500 mt-1">{{ $stats['organizations'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Registered</p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-700 mb-3">Top Organizations by Activities</h3>
        <canvas id="orgChart" height="90"></canvas>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-700 mb-3">Activities by Category</h3>
        @if($byCategory->count())
            <canvas id="catChart" height="160"></canvas>
        @else
            <p class="text-gray-400 text-sm py-10 text-center">No category data yet.</p>
        @endif
    </div>
</div>

<!-- Monthly Trend -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-8">
    <h3 class="font-bold text-gray-700 mb-3">Monthly Activity Submissions (Last 6 Months)</h3>
    <canvas id="trendChart" height="60"></canvas>
</div>

<!-- Recent Submissions -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-8">
    <div class="flex justify-between items-center mb-3">
        <h3 class="font-bold text-gray-700">Recent Submissions</h3>
        <a href="{{ route('admin.activities') }}" class="text-sm font-medium transition" style="color: #f5a623;">View All →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-2 text-left text-gray-500">Title</th>
                    <th class="p-2 text-left text-gray-500">Organization</th>
                    <th class="p-2 text-left text-gray-500">Date</th>
                    <th class="p-2 text-left text-gray-500">Venue</th>
                    <th class="p-2 text-left text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivities as $act)
                <tr class="border-b last:border-0 hover:bg-gray-50">
                    <td class="p-2 font-medium">{{ $act->title }}</td>
                    <td class="p-2">
                        @if($act->user)
                            {{ $act->user->org_name ?? $act->user->name }}
                        @else
                            {{ $act->organization ?? '—' }}
                        @endif
                    </td>
                    <td class="p-2">{{ $act->date->format('M d, Y') }}</td>
                    <td class="p-2">{{ $act->venue }}</td>
                    <td class="p-2">
                        <span class="px-2 py-1 rounded-full text-xs font-bold
                            {{ $act->status == 'pending'  ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $act->status == 'approved' ? 'bg-green-100 text-green-700'  : '' }}
                            {{ $act->status == 'rejected' ? 'bg-red-100 text-red-700'      : '' }}">
                            {{ ucfirst($act->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="p-4 text-center text-gray-400">No submissions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <a href="{{ route('admin.activities') }}" class="flex items-center gap-3 text-white rounded-xl p-5 transition" style="background-color: #f5a623;" onmouseover="this.style.backgroundColor='#e89600'" onmouseout="this.style.backgroundColor='#f5a623'">
        <span class="text-2xl"></span>
        <div><p class="font-bold">Activity Monitoring</p><p class="text-xs opacity-80">Review, approve or reject</p></div>
    </a>
    <a href="{{ route('admin.organizations.index') }}" class="flex items-center gap-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl p-5 transition">
        <span class="text-2xl"></span>
        <div><p class="font-bold">Organizations</p><p class="text-xs opacity-80">Manage org list & structure</p></div>
    </a>
    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 bg-slate-700 hover:bg-slate-800 text-white rounded-xl p-5 transition">
        <span class="text-2xl"></span>
        <div><p class="font-bold">Manage Users</p><p class="text-xs opacity-80">Edit members & profiles</p></div>
    </a>
</div>

@push('scripts')
<script>
function initializeCharts() {
    if (typeof Chart === 'undefined') {
        setTimeout(initializeCharts, 100);
        return;
    }
    
    const orgLabels = @json($topOrgs->pluck('name'));
    const orgCounts = @json($topOrgs->pluck('activities_count'));
    new Chart(document.getElementById('orgChart').getContext('2d'), {
        type: 'bar',
        data: { labels: orgLabels, datasets: [{ label: 'Activities Submitted', data: orgCounts, backgroundColor: 'rgba(14,165,233,0.7)', borderRadius: 6 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    @if($byCategory->count())
    new Chart(document.getElementById('catChart').getContext('2d'), {
        type: 'doughnut',
        data: { labels: @json($byCategory->pluck('category')), datasets: [{ data: @json($byCategory->pluck('count')), backgroundColor: ['#0ea5e9','#22c55e','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'] }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
    @endif
    new Chart(document.getElementById('trendChart').getContext('2d'), {
        type: 'line',
        data: { labels: @json($monthlyTrend->pluck('month')), datasets: [{ label: 'Submissions', data: @json($monthlyTrend->pluck('count')), borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.1)', fill: true, tension: 0.4, pointBackgroundColor: '#0ea5e9' }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
}

document.addEventListener('DOMContentLoaded', initializeCharts);
</script>
@endpush
</x-app-layout>
