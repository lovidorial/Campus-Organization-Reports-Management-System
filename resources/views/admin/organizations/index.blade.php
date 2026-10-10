<x-app-layout>
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-start sm:gap-4">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-slate-900">Organization Accounts</h1>
            <p class="mt-3 text-sm text-slate-600 max-w-2xl">Manage organization accounts, secretary access, and activity submission status from a single dashboard.</p>
        </div>
        <a href="{{ route('admin.organizations.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-3xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:flex-shrink-0 sm:whitespace-nowrap">+ Add Organization</a>
    </div>

    @php
        $tabQuery = request()->except('page');
        $activeTabQuery = array_merge($tabQuery, ['tab' => 'active']);
        $inactiveTabQuery = array_merge($tabQuery, ['tab' => 'inactive']);
    @endphp
    <nav class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1" aria-label="Organization account status">
        <a href="{{ route('admin.organizations.index', $activeTabQuery) }}" @if($tab === 'active') aria-current="page" @endif class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-semibold {{ $tab === 'active' ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            Active <span class="rounded-full px-2 py-0.5 text-xs {{ $tab === 'active' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $summary['active'] }}</span>
        </a>
        <a href="{{ route('admin.organizations.index', $inactiveTabQuery) }}" @if($tab === 'inactive') aria-current="page" @endif class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-semibold {{ $tab === 'inactive' ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            Inactive <span class="rounded-full px-2 py-0.5 text-xs {{ $tab === 'inactive' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $summary['inactive'] }}</span>
        </a>
    </nav>

    <!-- Information Card -->
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-900 uppercase tracking-[0.2em] mb-2">Note</p>
            <p class="text-sm text-slate-600 leading-relaxed">Each registered student organization is assigned one system account. Only the organization&rsquo;s Secretary is authorized to access and manage organization activities within the system.</p>
        </div>
    </div>

    <!-- Summary Cards Grid -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach([['Total', $summary['total'], 'text-slate-900'], ['Active', $summary['active'], 'text-emerald-600'], ['Inactive', $summary['inactive'], 'text-rose-600']] as [$label, $count, $color])
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-3xl font-bold {{ $color }}">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <!-- Filter Section -->
    <form method="GET" action="{{ route('admin.organizations.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(14rem,1fr)_minmax(10rem,0.7fr)_minmax(10rem,0.7fr)_auto] xl:items-end">
            <div>
                <label for="organization-search" class="mb-1 block text-xs font-semibold text-slate-600">Search</label>
                <input id="organization-search" type="search" name="search" value="{{ $search }}" placeholder="Organization, name or email" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            </div>
            <div>
                <label for="organization-college" class="mb-1 block text-xs font-semibold text-slate-600">College</label>
                <select id="organization-college" name="college" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700">
                    <option value="">All colleges</option>
                    @foreach($collegeOptions as $option)<option value="{{ $option }}" @selected($college === $option)>{{ $option }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="organization-type" class="mb-1 block text-xs font-semibold text-slate-600">Type</label>
                <select id="organization-type" name="type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700">
                    <option value="">All types</option>
                    @foreach($typeOptions as $option)<option value="{{ $option }}" @selected($type === $option)>{{ $option }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">Apply filters</button>
        </div>
    </form>

    <!-- Organizations Table -->
    @if($tab === 'active')
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
                                @if($org->logo_url)
                                    <img src="{{ $org->logo_url }}" alt="{{ $org->name }} logo" class="h-16 w-16 rounded-lg object-cover border border-slate-200 flex-shrink-0" />
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
                            @if($org->members->isNotEmpty())
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
                            @if($org->account_term_ended)
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-700">Inactive</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">Active</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center align-middle">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.organizations.show', $org) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-50 text-slate-600 hover:bg-slate-200 transition sm:h-8 sm:w-8" title="View" aria-label="View">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                <a href="{{ route('admin.organizations.edit', $org) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-50 text-slate-600 hover:bg-slate-200 transition sm:h-8 sm:w-8" title="Edit" aria-label="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h6m3 0a2 2 0 012 2v6m0 3v1a2 2 0 01-2 2H9m6-3l-5 5m0 0l-5-5m5 5V5" /></svg>
                                </a>
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
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Organization</th><th class="px-4 py-3">College</th><th class="px-4 py-3">Term</th><th class="px-4 py-3">School Year</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($inactiveAccounts as $account)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $account->name }}</td><td class="px-4 py-3 text-slate-700">{{ $account->email }}</td><td class="px-4 py-3">{{ $account->organization?->name ?? $account->org_name ?? '—' }}</td><td class="px-4 py-3">{{ $account->college ?? $account->organization?->college ?? '—' }}</td><td class="px-4 py-3">{{ $account->term ?? '—' }}</td><td class="px-4 py-3">{{ $account->school_year ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No inactive accounts yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $inactiveAccounts->links() }}</div>
    @endif
</div>
</x-app-layout>
