<x-app-layout>
    <div class="space-y-4">
        <header>
            <h1 class="text-xl font-bold text-gray-900">Activity Logs</h1>
            <p class="mt-1 text-sm text-gray-500">Audit events recorded by the system.</p>
        </header>

        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="actor" class="mb-1 block text-xs font-semibold text-gray-600">Actor</label>
                <input id="actor" name="actor" value="{{ $filters['actor'] }}" placeholder="Name, email, or ID" class="w-full rounded border-gray-300 text-sm">
            </div>
            <div>
                <label for="action" class="mb-1 block text-xs font-semibold text-gray-600">Action</label>
                <input id="action" name="action" value="{{ $filters['action'] }}" placeholder="Event or description" class="w-full rounded border-gray-300 text-sm">
            </div>
            <div>
                <label for="subject" class="mb-1 block text-xs font-semibold text-gray-600">Subject</label>
                <input id="subject" name="subject" value="{{ $filters['subject'] }}" placeholder="Type or ID" class="w-full rounded border-gray-300 text-sm">
            </div>
            <div>
                <label for="date_from" class="mb-1 block text-xs font-semibold text-gray-600">From</label>
                <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full rounded border-gray-300 text-sm">
            </div>
            <div>
                <label for="date_to" class="mb-1 block text-xs font-semibold text-gray-600">To</label>
                <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full rounded border-gray-300 text-sm">
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="rounded bg-amber-700 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-800">Filter</button>
                <a href="{{ route('admin.activity-logs.index') }}" class="rounded border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                        <tr><th scope="col" class="px-4 py-2.5">Actor</th><th scope="col" class="px-4 py-2.5">Action</th><th scope="col" class="px-4 py-2.5">Subject</th><th scope="col" class="px-4 py-2.5">Timestamp</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            @php
                                $actor = $log->causer?->name ?? $log->causer?->email;
                                if (!$actor && $log->causer_type) {
                                    $actor = class_basename($log->causer_type) . ' #' . $log->causer_id;
                                }
                                $subject = $log->subject_type
                                    ? class_basename($log->subject_type) . ' #' . $log->subject_id
                                    : '—';
                            @endphp
                            <tr>
                                <td class="px-4 py-2.5 text-gray-900">{{ $actor ?? 'System' }}</td>
                                <td class="px-4 py-2.5">
                                    @if($log->event)<span class="mr-1 inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-semibold text-gray-700">{{ $log->event }}</span>@endif
                                    <span class="text-gray-700">{{ $log->description }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-600">{{ $subject }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-gray-600">{{ $log->created_at?->format('M d, Y g:i:s A') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No activity log entries match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3">{{ $logs->links() }}</div>
        </section>
    </div>
</x-app-layout>