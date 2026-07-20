<x-app-layout>
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-slate-900">Organization Accounts</h1>
            <p class="mt-3 text-sm text-slate-600 max-w-2xl">Manage organization accounts, secretary access, and activity submission status from a single dashboard.</p>
        </div>
        <a href="{{ route('admin.organizations.create') }}" class="flex-shrink-0 inline-flex items-center justify-center gap-2 rounded-3xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition whitespace-nowrap">+ Add Organization</a>
    </div>

    <!-- Information Card -->
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-900 uppercase tracking-[0.2em] mb-2">Note</p>
            <p class="text-sm text-slate-600 leading-relaxed">Each registered student organization is assigned one system account. Only the organization&rsquo;s Secretary is authorized to access and manage organization activities within the system.</p>
        </div>
    </div>

    <!-- Summary Cards Grid -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <!-- Total Organizations -->
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">
            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Total Organizations</p>
            <p class="mt-2 text-xs text-slate-500">All registered organizations</p>
            <p class="mt-4 text-3xl font-bold text-slate-900">{{ $summary['total'] ?? 0 }}</p>
        </div>

        <!-- Active Accounts -->
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">
            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Active</p>
            <p class="mt-2 text-xs text-slate-500">Active organization accounts</p>
            <p class="mt-4 text-3xl font-bold text-emerald-600">{{ $summary['active'] ?? 0 }}</p>
        </div>

        <!-- Inactive Accounts -->
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">
            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Inactive</p>
            <p class="mt-2 text-xs text-slate-500">Inactive organization accounts</p>
            <p class="mt-4 text-3xl font-bold text-rose-600">{{ $summary['inactive'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-4">
            <!-- Search Field -->
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search organizations..."
                       class="w-full rounded-3xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm text-slate-700 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100" />
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z" /></svg>
                </span>
            </div>

            <!-- College Dropdown -->
            <select name="college" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                <option value="">All Colleges</option>
            </select>

            <!-- Type Dropdown -->
            <select name="type" onchange="this.form.submit()" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                <option value="">All Types</option>
                <option value="Student Council" {{ request('type')=='Student Council' ? 'selected' : '' }}>Student Council</option>
                <option value="Academic Org" {{ request('type')=='Academic Org' ? 'selected' : '' }}>Academic Org</option>
                <option value="Cultural Org" {{ request('type')=='Cultural Org' ? 'selected' : '' }}>Cultural Org</option>
                <option value="Sports Org" {{ request('type')=='Sports Org' ? 'selected' : '' }}>Sports Org</option>
                <option value="Religious Org" {{ request('type')=='Religious Org' ? 'selected' : '' }}>Religious Org</option>
                <option value="Publication" {{ request('type')=='Publication' ? 'selected' : '' }}>Publication</option>
                <option value="Other" {{ request('type')=='Other' ? 'selected' : '' }}>Other</option>
            </select>

            <!-- Status Dropdown -->
            <select name="status" onchange="this.form.submit()" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                <option value="">All Status</option>
                <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
    </div>

    <!-- Organizations Table -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-4 py-3 text-left font-semibold text-slate-700 sticky top-0 z-10">Organization</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700 sticky top-0 z-10">Secretary</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700 sticky top-0 z-10">Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700 sticky top-0 z-10">College</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700 sticky top-0 z-10">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700 sticky top-0 z-10">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($organizations as $org)
                    <tr class="group hover:bg-slate-50 transition-all duration-150">
                        <td class="px-4 py-3 align-middle">
                            <div class="flex items-center gap-4">
                                @if($org->logo_path)
                                    <img src="{{ asset('storage/'.$org->logo_path) }}" alt="{{ $org->name }} logo" class="h-16 w-16 rounded-lg object-cover border border-slate-200 flex-shrink-0" />
                                @else
                                    <div class="h-16 w-16 flex items-center justify-center rounded-lg bg-orange-100 text-sm font-bold text-orange-700 uppercase flex-shrink-0">{{ Illuminate\Support\Str::limit($org->name, 2, '') }}</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-lg font-semibold text-slate-900 truncate">{{ $org->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500 truncate">{{ $org->description ? Illuminate\Support\Str::limit($org->description, 70) : 'No description' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-middle w-48">
                            @if($org->members->count())
                                <div class="flex flex-col justify-center h-full">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ $org->members->first()->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500 truncate">{{ $org->members->first()->email }}</p>
                                </div>
                            @else
                                <div class="flex items-center h-full">
                                    <span class="text-xs text-slate-500">No secretary assigned</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{{ $org->type ?? 'General' }}</span>
                        </td>
                        <td class="px-4 py-3 align-middle text-slate-700">{{ $org->college ?? '—' }}</td>
                        <td class="px-4 py-3 align-middle">
                            @if(! $org->members->count())
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">Pending</span>
                            @elseif($org->is_active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-700">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center align-middle">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.organizations.show', $org) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-slate-600 hover:bg-slate-200 transition" title="View" aria-label="View">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                <a href="{{ route('admin.organizations.edit', $org) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-slate-600 hover:bg-slate-200 transition" title="Edit" aria-label="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h6m3 0a2 2 0 012 2v6m0 3v1a2 2 0 01-2 2H9m6-3l-5 5m0 0l-5-5m5 5V5" /></svg>
                                </a>
                                @if($org->is_active)
                                <form action="{{ route('admin.organizations.deactivate', $org) }}" method="POST" onsubmit="return confirm('Deactivate this organization account?');" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-slate-600 hover:bg-slate-200 transition" title="Deactivate" aria-label="Deactivate">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" /></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="px-6 py-16 text-center">
                                <div class="mx-auto max-w-md">
                                    <h3 class="text-lg font-semibold text-slate-900">No organizations found.</h3>
                                    <p class="mt-3 text-sm text-slate-600 leading-relaxed">Create an organization account to manage secretary access and submissions.</p>
                                    <a href="{{ route('admin.organizations.create') }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-3xl  bg-orange-600 px-6 py-3 text-sm font-semibold text-white hover:bg-orange-700 transition">+ Add Organization</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $organizations->links() }}
    </div>
</div>
</x-app-layout>
