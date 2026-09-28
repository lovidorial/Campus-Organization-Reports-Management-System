<x-app-layout>
    @php
        $orgName = $gpoa->user->org_name ?? $gpoa->user->name;
        $submittedDate = $gpoa->created_at->format('M d, Y');
    @endphp

    <div class="min-h-screen bg-slate-100" x-data="{ approveModalOpen: false, rejectModalOpen: false }">
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
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-amber-800">
                            STATUS: {{ ucfirst($gpoa->status) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm font-medium text-slate-500">
                        <span class="uppercase tracking-[0.12em] text-slate-400">Submitted</span>
                        <span class="font-semibold text-slate-700">{{ $submittedDate }}</span>
                    </div>
                </div>
            </div>

            @if($gpoa->status === 'pending')
                <div class="mb-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        @click="approveModalOpen = true"
                        class="inline-flex items-center justify-center rounded-xl bg-green-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 hover:shadow-md"
                    >
                        Approve &amp; Store GPOA
                    </button>

                    <button
                        type="button"
                        @click="rejectModalOpen = true"
                        class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 hover:shadow-md"
                    >
                        Reject
                    </button>
                </div>
            @endif

            @if($gpoa->modificationRequests->isNotEmpty())
                <section class="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <h3 class="font-bold text-slate-900">Activity Modification Requests</h3>
                    <div class="mt-3 space-y-3">
                        @foreach($gpoa->modificationRequests as $modificationRequest)
                            <div class="rounded-lg border border-amber-200 bg-white p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-800">{{ ucfirst($modificationRequest->type) }}{{ $modificationRequest->activity ? ': ' . $modificationRequest->activity->title : '' }}</span>
                                    <span class="text-xs font-semibold uppercase text-slate-500">{{ $modificationRequest->status }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ $modificationRequest->remarks }}</p>
                                @if($modificationRequest->status === 'pending')
                                    <div class="mt-3 flex gap-2">
                                        <form method="POST" action="{{ route('admin.gpoa-modification-requests.approve', $modificationRequest) }}">@csrf<button class="rounded bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Approve</button></form>
                                        <form method="POST" action="{{ route('admin.gpoa-modification-requests.reject', $modificationRequest) }}">@csrf<button class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white">Reject</button></form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($gpoa->approved_at)
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    Approved by {{ $gpoa->approver->name ?? 'Admin' }} on {{ $gpoa->approved_at->format('M d, Y g:i A') }}
                    @if($gpoa->stored_at)
                        — Stored {{ $gpoa->stored_at->format('M d, Y') }}
                    @endif
                </div>
            @endif

            @if($gpoa->reject_reason)
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <span class="font-semibold">Rejection reason:</span> {{ $gpoa->reject_reason }}
                </div>
            @endif

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-slate-800">Legacy Planned Activities</h2>
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
                                $activityStatus = $activity->activity_request_id
                                    ? ($activity->activityRequest?->status ?? 'pending')
                                    : 'not_requested';

                                $statusConfig = [
                                    'not_requested' => ['label' => 'Not Yet Requested', 'class' => 'bg-gray-100 text-gray-600 border-gray-200'],
                                    'pending' => ['label' => 'Awaiting Approval', 'class' => 'bg-amber-100 text-amber-700 border-amber-200'],
                                    'approved' => ['label' => 'Ongoing', 'class' => 'bg-blue-100 text-blue-700 border-blue-200'],
                                    'in_progress' => ['label' => 'Ongoing', 'class' => 'bg-blue-100 text-blue-700 border-blue-200'],
                                    'awaiting_report' => ['label' => 'Ongoing', 'class' => 'bg-blue-100 text-blue-700 border-blue-200'],
                                    'report_submitted' => ['label' => '✓ Finished', 'class' => 'bg-green-100 text-green-700 border-green-200'],
                                    'closed' => ['label' => '✓ Finished', 'class' => 'bg-green-100 text-green-700 border-green-200'],
                                    'rejected' => ['label' => 'Rejected', 'class' => 'bg-red-100 text-red-700 border-red-200'],
                                ];

                                $statusMeta = $statusConfig[$activityStatus] ?? $statusConfig['not_requested'];
                            @endphp
                            <tr class="border-b border-slate-200 align-top">
                                <td class="p-3 font-medium text-slate-800">{{ $activity->title }}</td>
                                <td class="p-3 text-slate-700">{{ $activity->category ?? '—' }}</td>
                                <td class="p-3 text-slate-700">{{ $activity->activity_level ?? '—' }}</td>
                                <td class="p-3 text-slate-700">{{ $activity->date ? $activity->date->format('M d, Y') : '—' }}</td>
                                <td class="p-3 text-slate-700">₱ {{ number_format((float) ($activity->estimated_budget ?? 0), 2) }}</td>
                                <td class="p-3 text-slate-700">{{ $statusMeta['label'] }}</td>
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
                                        $sdgText = collect($sdgs)
                                            ->map(fn($id) => '<span class="inline-block rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">' . e($id) . '</span>')
                                            ->join(' ');
                                    @endphp

                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <h4 class="mb-3 border-b border-slate-200 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Classification</h4>
                                            <dl class="divide-y divide-slate-100">
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 py-2 first:pt-0 last:pb-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">SDGs Addressed</dt>
                                                    <dd class="break-words text-sm text-slate-700">{!! $sdgText ?: '—' !!}</dd>
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
                                                <div class="grid grid-cols-[minmax(7rem,35%)_1fr] gap-4 border-t border-slate-100 py-2 last:pb-0">
                                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preceding Activity</dt>
                                                    <dd class="break-words text-sm text-slate-700">{{ $activity->preceding_activity ?? 'None — first activity' }}</dd>
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

        <div
            x-show="approveModalOpen"
            x-transition
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
            style="display: none;"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex justify-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.2 16.7 19 6.9"/>
                        </svg>
                    </div>
                </div>

                <h3 class="mt-5 text-center text-2xl font-bold text-slate-900">Approve this GPOA?</h3>

                <p class="mt-3 text-center text-sm leading-6 text-slate-600">
                    Verifying and storing this GPOA will let {{ $orgName }} submit activity requests for {{ $gpoa->term }} / SY {{ $gpoa->school_year }}.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                    <button
                        type="button"
                        @click="approveModalOpen = false"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300"
                    >
                        Cancel
                    </button>

                    <form action="{{ route('admin.gpoa.approve', $gpoa) }}" method="POST" class="w-full sm:w-auto">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-900"
                        >
                            Confirm &amp; Approve
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div
            x-show="rejectModalOpen"
            x-transition
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
            style="display: none;"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-xl font-bold text-slate-900">Reject GPOA</h3>

                <form action="{{ route('admin.gpoa.reject', $gpoa) }}" method="POST" class="mt-4">
                    @csrf
                    <textarea
                        name="reject_reason"
                        rows="4"
                        placeholder="Reason for rejection..."
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    ></textarea>

                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="rejectModalOpen = false"
                            class="inline-flex items-center justify-center rounded-xl bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-300"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                        >
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
