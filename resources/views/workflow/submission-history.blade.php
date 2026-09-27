<x-app-layout>
    <div class="space-y-5">
        <header class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Submission History</h1>
                <p class="mt-1 text-sm text-gray-500">Complete record of all document versions</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-amber-700 hover:underline">Back to dashboard</a>
        </header>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 md:gap-4">
            @foreach([
                ['label' => 'Requests', 'value' => $stats['total'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
                ['label' => 'Pending', 'value' => $stats['pending'], 'color' => 'text-amber-600', 'bg' => 'bg-amber-50'],
                ['label' => 'Active', 'value' => $stats['approved'], 'color' => 'text-green-600', 'bg' => 'bg-green-50'],
                ['label' => 'Rejected', 'value' => $stats['rejected'], 'color' => 'text-red-600', 'bg' => 'bg-red-50'],
            ] as $stat)
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm md:p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $stat['bg'] }}">
                            <span class="text-lg font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</span>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $stat['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            @if($submissionHistory->count())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th scope="col" class="px-3 py-2.5">Document</th>
                                <th scope="col" class="px-3 py-2.5">Version</th>
                                <th scope="col" class="px-3 py-2.5">Status</th>
                                <th scope="col" class="px-3 py-2.5">Submitted</th>
                                <th scope="col" class="px-3 py-2.5">Approved</th>
                                <th scope="col" class="px-3 py-2.5">Reviewer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($submissionHistory as $sub)
                                <tr class="hover:bg-orange-50/30">
                                    <td class="px-3 py-3 font-medium text-gray-800">{{ $sub->documentLabel() }}</td>
                                    <td class="px-3 py-3 text-gray-600">v{{ $sub->version }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $sub->statusClasses() }}">
                                            {{ ucfirst(str_replace('_', ' ', $sub->status)) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-500">{{ $sub->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-500">{{ $sub->approved_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-3 py-3 text-xs text-gray-600">{{ $sub->reviewer?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-4 py-10 text-center">
                    <p class="text-sm font-medium text-gray-700">No submissions recorded yet.</p>
                    <a href="{{ route('gpoa.create') }}" class="mt-2 inline-block text-sm font-medium text-amber-700 hover:underline">Start with your GPOA</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>