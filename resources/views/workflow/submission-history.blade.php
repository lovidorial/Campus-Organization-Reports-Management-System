<x-app-layout>
    <div class="space-y-4">
        <header>
            <h1 class="text-xl font-bold text-gray-900">Submission History</h1>
            <p class="mt-1 text-sm text-gray-500">Document submissions across your organization’s workflow cycles.</p>
        </header>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            @if($submissions->count())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">Document</th>
                                <th scope="col" class="px-4 py-2.5">Workflow</th>
                                <th scope="col" class="px-4 py-2.5">Version</th>
                                <th scope="col" class="px-4 py-2.5">Status</th>
                                <th scope="col" class="px-4 py-2.5">Submitted</th>
                                <th scope="col" class="px-4 py-2.5">Last updated</th>
                                <th scope="col" class="px-4 py-2.5">Reviewer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($submissions as $submission)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $submission->documentLabel() }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $submission->workflow->term }} / {{ $submission->workflow->school_year }}</td>
                                    <td class="px-4 py-3 text-gray-600">v{{ $submission->version }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $submission->statusClasses() }}">
                                            {{ ucfirst(str_replace('_', ' ', $submission->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                        {{ ($submission->submitted_at ?? $submission->created_at)?->format('M d, Y g:i A') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $submission->updated_at?->format('M d, Y g:i A') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $submission->reviewer?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $submissions->links() }}
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