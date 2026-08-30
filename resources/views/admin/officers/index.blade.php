<x-app-layout>
    @php
        $activeTab = $tab ?? 'current';
        $queryParams = request()->query();
        $isAdminView = (bool) ($isAdminView ?? request()->routeIs('admin.*'));
        $tabRouteNames = [
            'current' => $isAdminView ? 'admin.officers.index' : 'organization.officers.index',
            'previous' => $isAdminView ? 'admin.officers.history' : 'organization.officers.history',
            'members' => 'organization.members.index',
        ];
        $archiveRoutePrefix = $isAdminView ? 'admin.officers.archive' : 'organization.officers.archive';
        $restoreRoutePrefix = $isAdminView ? 'admin.officers.restore' : 'organization.officers.restore';

        $yearGroups = collect();
        if (!empty($history)) {
            $yearGroups = collect($history)->mapWithKeys(function ($officersForPeriod, $periodKey) {
                $officers = collect($officersForPeriod);
                foreach ($officers as $officer) {
                    $schoolYear = $officer->school_year ?? 'Unknown SY';
                    $term = $officer->term ?? 'Unknown Term';
                    $grouped[$schoolYear][$term][] = $officer;
                }
                return [];
            })->toArray();

            $yearGroups = collect();
            foreach ($history as $periodKey => $officersForPeriod) {
                foreach ($officersForPeriod as $officer) {
                    $schoolYear = $officer->school_year ?? 'Unknown SY';
                    $term = $officer->term ?? 'Unknown Term';
                    $yearGroups->put($schoolYear, $yearGroups->get($schoolYear, collect()));
                    $yearGroups[$schoolYear] = $yearGroups->get($schoolYear, collect())->put($term, ($yearGroups->get($schoolYear, collect())->get($term, collect()))->push($officer));
                }
            }

            $yearGroups = $yearGroups
                ->map(function ($terms) {
                    return $terms->sortKeys();
                })
                ->sortKeysDesc();
        }
    @endphp

    <div class="mx-auto max-w-7xl py-8">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-600">Officer Directory</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900">Org Chart</h2>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ $filterRoute ?? route($tabRouteNames[$activeTab]) }}" class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                @if($organizations->isNotEmpty())
                    <select name="organization_id" class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <option value="">All Organizations</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->organization_id }}" {{ (string) ($selectedOrganization ?? request('organization_id')) === (string) $organization->organization_id ? 'selected' : '' }}>
                                {{ $organization->org_name ?? 'Unknown Organization' }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="organization_id" value="{{ request('organization_id') }}">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500">Organization filter unavailable</div>
                @endif

                <input type="text" name="term" value="{{ $selectedTerm ?? request('term') }}" placeholder="Term" class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                <input type="text" name="school_year" value="{{ $selectedSchoolYear ?? request('school_year') }}" placeholder="School Year" class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">Filter</button>
                    <a href="{{ $filterRoute ?? route($tabRouteNames[$activeTab]) }}" class="rounded-2xl bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Reset</a>
                </div>
            </div>
        </form>

        <div class="mb-6 border-b border-slate-200">
            <nav class="flex flex-wrap gap-2" aria-label="Officer Tabs">
                @foreach(['current' => 'Current Secretaries', 'previous' => 'Previous Officers', 'members' => 'Org Chart Members'] as $key => $label)
                    @php
                        $tabQuery = $queryParams;
                        $tabQuery['tab'] = $key;
                    @endphp
                    @if($key === 'members' && $isAdminView)
                        @continue
                    @endif
                    <a href="{{ route($tabRouteNames[$key], $tabQuery) }}" class="rounded-t-2xl border px-4 py-2.5 text-sm font-semibold transition {{ $activeTab === $key ? 'border-slate-200 border-b-white bg-white text-slate-900 shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        @if($activeTab === 'members')
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Org Chart Members</h3>
                        <p class="text-sm text-slate-500">Roster entries maintained by the organization secretary.</p>
                    </div>
                    @if(! $isAdminView)
                        <button type="button" x-data="{}" @click="$dispatch('open-member-modal')" class="rounded-2xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Add Member</button>
                    @endif
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($members as $member)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    @if($member->photo_path)
                                        <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->name }}" class="h-12 w-12 rounded-full object-cover ring-2 ring-white shadow-sm">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-500 text-sm font-bold text-white">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $member->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $member->position }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1 text-sm text-slate-600">
                                @if($member->year_level)
                                    <p><span class="font-medium text-slate-700">Year:</span> {{ $member->year_level }}</p>
                                @endif
                                @if($member->facebook_url)
                                    <p>
                                        <span class="font-medium text-slate-700">Facebook:</span>
                                        @if(str_starts_with(strtolower(trim($member->facebook_url)), 'http'))
                                            <a href="{{ $member->facebook_url }}" target="_blank" rel="noopener noreferrer" class="text-sky-700 underline hover:text-sky-800">{{ $member->facebook_url }}</a>
                                        @else
                                            {{ $member->facebook_url }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            @if(! $isAdminView)
                                <div class="mt-4 flex gap-2">
                                    <button type="button" x-data="{}" @click="$dispatch('open-edit-member', {{ json_encode($member->toArray()) }})" class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Edit</button>
                                    <form method="POST" action="{{ route('organization.members.destroy', $member) }}" class="flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100" onclick="return confirm('Remove {{ addslashes($member->name) }} from the org chart?')">Delete</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-400">
                            No org chart members yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div x-data="{ openMemberModal: false, editMemberId: null, form: { name: '', position: '', year_level: '', facebook_url: '' } }" x-on:open-member-modal.window="openMemberModal = true; editMemberId = null; form = { name: '', position: '', year_level: '', facebook_url: '' };" x-on:open-edit-member.window="openMemberModal = true; editMemberId = $event.detail.id ?? null; form = { name: $event.detail.name ?? '', position: $event.detail.position ?? '', year_level: $event.detail.year_level ?? '', facebook_url: $event.detail.facebook_url ?? '' };" class="relative">
                <div x-show="openMemberModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
                    <div class="absolute inset-0 bg-slate-900/40" @click="openMemberModal = false"></div>
                    <div class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-xl font-bold text-slate-900" x-text="editMemberId ? 'Edit Member' : 'Add Member'"></h3>
                            <button type="button" @click="openMemberModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                        </div>

                        <form method="POST" enctype="multipart/form-data" :action="editMemberId ? '{{ route('organization.members.update', ['member' => '__ID__']) }}'.replace('__ID__', editMemberId) : '{{ route('organization.members.store') }}'" class="space-y-4">
                            @csrf
                            <template x-if="editMemberId">
                                <input type="hidden" name="_method" value="PATCH">
                            </template>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Profile Photo</label>
                                <input type="file" name="photo" accept="image/*" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                                <input type="text" name="name" x-model="form.name" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Position</label>
                                <input type="text" name="position" x-model="form.position" required class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Year Level</label>
                                    <input type="text" name="year_level" x-model="form.year_level" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Facebook Account</label>
                                    <input type="text" name="facebook_url" x-model="form.facebook_url" placeholder="Facebook profile link or name" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="openMemberModal = false" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                                <button type="submit" class="rounded-2xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700" x-text="editMemberId ? 'Update Member' : 'Save Member'"></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @elseif($activeTab === 'current')
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse($officers as $officer)
                    <div x-data="{ open: false }" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-center">
                            @if($officer->profile_photo_path)
                                <img src="{{ $officer->avatar_url ?? asset('images/osdw.logo.jpg') }}" alt="{{ $officer->name }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-white shadow-sm">
                            @else
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-indigo-500 text-xl font-bold text-white shadow-sm">
                                    {{ strtoupper(substr($officer->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 text-center">
                            <h3 class="text-lg font-bold text-slate-900">{{ $officer->name }}</h3>
                            <p class="mt-1 text-sm font-semibold text-sky-700">{{ $officer->position ?? 'Officer' }}</p>
                        </div>

                        <div class="mt-4 flex justify-center">
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ $officer->term ?? '—' }} · SY {{ $officer->school_year ?? '—' }}
                            </span>
                        </div>

                        <div class="mt-5 space-y-1 text-center text-sm text-slate-600">
                            <p class="font-medium text-slate-800">{{ $officer->org_name ?? $officer->organization?->name ?? '—' }}</p>
                            <p>{{ $officer->college ?? '—' }}</p>
                        </div>

                        @if(! $isAdminView)
                            <div class="mt-6">
                                <form method="POST" action="{{ route('organization.officers.archive', $officer) }}" onsubmit="return confirm('This will end {{ addslashes($officer->name) }}\'s term as {{ addslashes($officer->position ?? 'Officer') }} for {{ addslashes($officer->term ?? '—') }} / {{ addslashes($officer->school_year ?? '—') }} and move them to Previous Officers. They will no longer be able to log in. Continue?')">
                                    @csrf
                                    <input type="hidden" name="archived_reason" value="Term ended">
                                    <button type="submit" class="w-full rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                        End Term
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-400">
                        No active secretaries found.
                    </div>
                @endforelse
            </div>
        @else
            <div class="space-y-4">
                @forelse($yearGroups as $schoolYear => $terms)
                    <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                            <div class="flex items-center gap-3">
                                <span class="text-lg text-slate-500" x-text="open ? '▾' : '▸'">▾</span>
                                <span class="text-base font-semibold text-slate-900">SY {{ $schoolYear }}</span>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                {{ collect($terms)->flatten(1)->count() }} archived
                            </span>
                        </button>

                        <div x-show="open" class="border-t border-slate-200 bg-slate-50/50 px-4 py-4" x-transition>
                            @foreach($terms as $term => $officers)
                                <div class="mb-4 last:mb-0 rounded-2xl border border-slate-200 bg-white p-3">
                                    <div class="mb-3 px-1">
                                        <h4 class="text-sm font-bold uppercase tracking-[0.12em] text-slate-500">{{ $term }}</h4>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach($officers as $officer)
                                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-slate-500 to-slate-700 text-sm font-bold text-white">
                                                        {{ strtoupper(substr($officer->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-slate-900">{{ $officer->name }}</p>
                                                        <p class="text-xs text-slate-500">{{ $officer->position ?? 'Officer' }} • {{ $officer->org_name ?? $officer->organization?->name ?? '—' }}</p>
                                                    </div>
                                                </div>

                                                <form method="POST" action="{{ route($restoreRoutePrefix, $officer) }}" class="shrink-0">
                                                    @csrf
                                                    <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                                        Restore
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-400">
                        No previous officers found.
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>
