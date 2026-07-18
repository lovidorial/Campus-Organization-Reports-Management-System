<x-app-layout>
<style>
    .stat-card {
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
</style>
    <div class="min-h-[calc(100vh-3rem)] bg-white py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Top bar -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900">Dashboard</h1>
                    <p class="text-sm text-slate-500 mt-1">Campus Organization Reports Management System</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $currentTerm }} • {{ $currentSY }}</p>
                </div>
                <div class="relative">
                    <button id="adminProfileToggle" class="flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 shadow-sm hover:shadow-md">
                        <img src="{{ auth()->user()->profile_photo_path ? asset('storage/'.auth()->user()->profile_photo_path) : asset('images/osdw.logo.jpg') }}" class="h-10 w-10 rounded-full object-cover" alt="Profile"/>
                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"/></svg>
                    </button>
                    <div id="adminProfileMenu" class="hidden absolute right-0 mt-3 w-44 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Logout</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                    </div>
                </div>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                @php
                    $cards = [
                        ['label' => 'Pending GPOAs', 'count' => $pendingByDoc['gpoa'], 'color' => 'blue', 'link' => route('admin.workflows.index'), 'action' => 'Review Documents'],
                        ['label' => 'Communication Letters', 'count' => $pendingByDoc['communication_letter'], 'color' => 'green', 'link' => route('admin.workflows.index'), 'action' => 'Review'],
                        ['label' => 'Summary Reports', 'count' => $pendingByDoc['summary_report'], 'color' => 'yellow', 'link' => route('admin.workflows.index'), 'action' => 'Review'],
                        ['label' => 'Approved Reports', 'count' => $approvalsToday, 'color' => 'purple', 'link' => route('admin.workflows.index'), 'action' => 'View'],
                        ['label' => 'Organizations', 'count' => $stats['organizations'], 'color' => 'orange', 'link' => route('admin.organizations.index'), 'action' => 'Manage Organizations'],
                    ];
                @endphp

                @foreach($cards as $card)
                <div class="rounded-lg border border-slate-100 bg-white p-5 shadow-sm flex flex-col justify-between" style="min-height:110px;">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-2xl font-bold text-slate-900">{{ $card['count'] }}</p>
                            <p class="text-sm text-slate-600 mt-1">{{ $card['label'] }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center"
                             style="background: {{ $card['color'] == 'blue' ? 'rgba(59,130,246,0.08)' : ($card['color']=='green' ? 'rgba(16,185,129,0.08)' : ($card['color']=='yellow' ? 'rgba(245,158,11,0.08)' : ($card['color']=='purple' ? 'rgba(139,92,246,0.08)' : 'rgba(245,162,0,0.08)')) ) }}; color: {{ $card['color']=='blue' ? '#3b82f6' : ($card['color']=='green' ? '#10b981' : ($card['color']=='yellow' ? '#f59e0b' : ($card['color']=='purple' ? '#8b5cf6' : '#f5a623')) ) }};">
                            <!-- simple icon -->
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M3 12h18M3 17h18"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <a href="{{ $card['link'] }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">{{ $card['action'] }} →</a>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Recent Submissions -->
            <div class="rounded-lg border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Recent Submissions</h2>
                        <p class="text-sm text-slate-500">Latest document submissions</p>
                    </div>
                    <a href="{{ route('admin.workflows.index') }}" class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">View All Submissions</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <input type="text" placeholder="Search organization..." class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm" />
                    <select class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm">
                        <option>Semesters</option>
                        <option value="1st">1st Term</option>
                        <option value="2nd">2nd Term</option>
                    </select>
                    <select class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm">
                        <option>School Years</option>
                        @foreach($academicYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                    <select class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm">
                        <option>Status</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 text-xs uppercase">
                                <th class="py-3 px-4">Document Type</th>
                                <th class="py-3 px-4">Organization</th>
                                <th class="py-3 px-4">Submitted</th>
                                <th class="py-3 px-4">Reviewer</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($recentSubmissions as $submission)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-4 px-4">{{ ucfirst(str_replace('_',' ',$submission->document_type)) }}</td>
                                <td class="py-4 px-4">@if($submission->workflow && $submission->workflow->user){{ $submission->workflow->user->org_name ?? $submission->workflow->user->name }}@else — @endif</td>
                                <td class="py-4 px-4">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : '—' }}</td>
                                <td class="py-4 px-4">{{ $submission->reviewer?->name ?? '—' }}</td>
                                <td class="py-4 px-4">{{ ucfirst(str_replace('_',' ',$submission->status)) }}</td>
                                <td class="py-4 px-4 text-center"><a href="{{ route('admin.workflows.submissions.document', $submission->id) }}" class="text-slate-700 hover:text-slate-900">View</a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-12 px-4 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="h-12 w-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 11l3 3L22 4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">No recent submissions</p>
                                            <p class="text-sm text-slate-500">You have no recent submissions to display.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const profileToggle = document.getElementById('adminProfileToggle');
        const profileMenu = document.getElementById('adminProfileMenu');
        if(profileToggle && profileMenu){
            profileToggle.addEventListener('click', function(e){ e.stopPropagation(); profileMenu.classList.toggle('hidden'); });
            document.addEventListener('click', function(e){ if(!profileMenu.contains(e.target) && !profileToggle.contains(e.target)) profileMenu.classList.add('hidden'); });
        }
    });
    </script>
    @endpush
    </x-app-layout>
