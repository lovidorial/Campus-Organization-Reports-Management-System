<x-app-layout>
    @php
        $orgName = $gpoa->user->org_name ?? $gpoa->user->name;
        $submittedDate = $gpoa->created_at->format('M d, Y');
    @endphp

    <div class="min-h-screen bg-slate-100">
        <div class="mx-auto max-w-7xl px-0 py-8 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('admin.gpoa.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-amber-700 transition hover:text-amber-800">
                    <span aria-hidden="true">←</span>
                    <span>Back to GPOA Review</span>
                </a>

                <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Review GPOA</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $orgName }} — {{ $gpoa->term }} / SY {{ $gpoa->school_year }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-sky-800">GPOA</span>
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Approved</span>
                        @if($gpoa->document_path)
                            <a href="{{ route('admin.gpoa.document', $gpoa) }}" data-file-viewer data-title="Approved GPOA Document" class="text-xs font-semibold text-emerald-700 underline">View approved document</a>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 text-sm font-medium text-slate-500">
                        <span class="uppercase tracking-[0.12em] text-slate-400">Submitted</span>
                        <span class="font-semibold text-slate-700">{{ $submittedDate }}</span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-slate-800">Planned Activities</h2>
                        <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-700">
                            {{ $gpoa->activities->count() }}
                        </span>
                    </div>
                </div>

                <table class="w-full min-w-[1100px] text-sm">
                    <thead class="border-b border-slate-200 bg-white">
                        <tr>
                            <th class="p-3 text-left font-semibold text-slate-600">Title</th>
                            <th class="p-3 text-left font-semibold text-slate-600">Category</th>
                            <th class="p-3 text-left font-semibold text-slate-600">Activity Level</th>
                            <th class="p-3 text-left font-semibold text-slate-600">Date</th>
                            <th class="p-3 text-left font-semibold text-slate-600">Budget</th>
                            <th class="p-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="p-3 text-center font-semibold text-slate-600">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gpoa->activities as $activity)
                            @php
                                $monitoring = $activity->monitoringStatus();
                                $statusClass = match ($monitoring['status']) {
                                    'Completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Ongoing' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    default => 'bg-gray-100 text-gray-600 border-gray-200',
                                };
                            @endphp
                            <tr class="border-b border-slate-200 align-top">
                                <td class="p-3 font-medium text-slate-800">{{ $activity->title }}</td>
                                <td class="p-3 text-slate-700">{{ $activity->category ?? '—' }}</td>
                                <td class="p-3 text-slate-700">{{ $activity->activity_level ?? '—' }}</td>
                                <td class="p-3 text-slate-700">{{ $activity->date ? $activity->date->format('M d, Y') : '—' }}</td>
                                <td class="p-3 text-slate-700">₱ {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</td>
                                <td class="p-3 text-slate-700"><span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold {{ $statusClass }}">{{ $monitoring['status'] }}</span>@if($monitoring['late'])<span class="ml-1 text-xs font-semibold text-rose-700">Late</span>@endif</td>
                                <td class="p-3 text-center">
                                    <button type="button" onclick="const details = this.closest('tr').nextElementSibling; details.classList.toggle('hidden'); this.querySelector('.toggle-label').textContent = details.classList.contains('hidden') ? 'Show Details' : 'Hide Details'; this.querySelector('svg').classList.toggle('rotate-180');" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                        <span class="toggle-label">Show Details</span>
                                        <svg class="h-4 w-4 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr class="hidden border-b border-slate-200 bg-slate-50">
                                <td colspan="7" class="p-6">
                                    @php
                                        $sdgs = $activity->sdgs ?? [];
                                        if (!is_array($sdgs)) {
                                            $sdgs = json_decode($sdgs, true) ?? [];
                                        }
                                    @endphp

                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <h4 class="mb-3 border-b border-slate-200 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Classification</h4>
                                            <dl class="divide-y divide-slate-100">
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 py-2 first:pt-0 last:pb-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">SDGs Addressed</dt>
                                                    <dd class="break-words text-sm text-slate-700">
                                                        @forelse($sdgs as $sdg)
                                                            <x-sdg-badge :number="$sdg" :show-label="false" />
                                                        @empty
                                                            —
                                                        @endforelse
                                                    </dd>
                                                </div>
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 py-2 last:pb-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Venue</dt>
                                                    <dd class="break-words text-sm text-slate-700">{{ $activity->venue ?? '—' }}</dd>
                                                </div>
                                            </dl>
                                        </section>

                                        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <h4 class="mb-3 border-b border-slate-200 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Planning</h4>
                                            <dl class="divide-y divide-slate-100">
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 py-2 first:pt-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Objectives</dt>
                                                    <dd class="break-words whitespace-pre-wrap text-sm text-slate-700">{{ $activity->objectives ?? '—' }}</dd>
                                                </div>
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 border-t border-slate-100 py-2">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Expected Outcome</dt>
                                                    <dd class="break-words whitespace-pre-wrap text-sm text-slate-700">{{ $activity->expected_outcome ?? '—' }}</dd>
                                                </div>
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 border-t border-slate-100 py-2 last:pb-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Plan / Key Strategy</dt>
                                                    <dd class="break-words whitespace-pre-wrap text-sm text-slate-700">{{ $activity->plan_key_strategy ?? '—' }}</dd>
                                                </div>
                                            </dl>
                                        </section>

                                        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <h4 class="mb-3 border-b border-slate-200 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Logistics</h4>
                                            <dl class="divide-y divide-slate-100">
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 py-2 first:pt-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Target Participants</dt>
                                                    <dd class="break-words text-sm text-slate-700">{{ $activity->target_participants ?? '—' }}</dd>
                                                </div>
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 border-t border-slate-100 py-2">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Persons Involved</dt>
                                                    <dd class="break-words text-sm text-slate-700">{{ $activity->person_in_charge ?? '—' }}</dd>
                                                </div>
                                            </dl>
                                        </section>

                                        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <h4 class="mb-3 border-b border-slate-200 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Resources</h4>
                                            <dl class="divide-y divide-slate-100">
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 py-2 first:pt-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Facilities / Materials</dt>
                                                    <dd class="break-words text-sm text-slate-700">{{ $activity->facilities_materials ?? '—' }}</dd>
                                                </div>
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 border-t border-slate-100 py-2">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Source of Funds</dt>
                                                    <dd class="break-words text-sm text-slate-700">{{ $activity->source_of_funds ?? '—' }}</dd>
                                                </div>
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 border-t border-slate-100 py-2 last:pb-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Remarks</dt>
                                                    <dd class="break-words text-sm text-slate-700">{{ $activity->remarks ?? '—' }}</dd>
                                                </div>
                                            </dl>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-sm text-slate-500">No legacy activities recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
