<x-app-layout>
    @php
        $orgName = $gpoa->user->org_name ?? $gpoa->user->name;
        $submittedDate = $gpoa->created_at->format('M d, Y');
    @endphp

    <div class="min-h-screen bg-slate-100" x-data="{ approveModalOpen: false, rejectModalOpen: false }">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-slate-800">Legacy Planned Activities</h2>
                        <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-700">
                            {{ $gpoa->activities->count() }}
                        </span>
                    </div>
                </div>

                <div class="p-5">
                    @if($gpoa->activities->isEmpty())
                        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center">
                            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 7.5h7M8.5 12h7m-7 4.5h4.5M6 5.5A2.5 2.5 0 0 1 8.5 3h7A2.5 2.5 0 0 1 18 5.5v13A2.5 2.5 0 0 1 15.5 21h-7A2.5 2.5 0 0 1 6 18.5v-13Z"/>
                                </svg>
                            </div>
                            <p class="text-base font-medium text-slate-700">No legacy activities recorded</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($gpoa->activities as $activity)
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

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">{{ $activity->title }}</p>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ $activity->date ? $activity->date->format('M d, Y') : '—' }} • {{ $activity->venue ?? '—' }}
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">
                                            {{ $statusMeta['label'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
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
