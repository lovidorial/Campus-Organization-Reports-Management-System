<x-app-layout>
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">My GPOA</h2>
        <p class="text-sm text-gray-500">General Plan of Activities — Step 1 of your document workflow</p>
    </div>
    @if(isset($workflow) && $workflow->canSubmitGpoa() && !$workflow->is_locked)
    <a href="{{ route('gpoa.create') }}"
       class="px-4 py-2 text-white rounded-lg text-sm font-semibold hover:opacity-90" style="background:#e89600;">+ Submit GPOA</a>
    @endif
</div>

@if(isset($workflow))
@include('components.workflow-progress', ['workflow' => $workflow, 'progressStages' => $workflow->progressStages()])
@endif

@if($hasApprovedGpoa)
<div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
    Your GPOA for <strong>{{ $term }}</strong> / SY <strong>{{ $schoolYear }}</strong> is approved.
    <a href="{{ route('workflow.communication-letter') }}" class="underline font-semibold ml-1">Proceed to Communication Letter →</a>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
    <table class="w-full text-sm min-w-[600px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Term / SY</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">College</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Activities</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Submitted</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($gpoas as $gpoa)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4">{{ $gpoa->term }}<br><span class="text-xs text-gray-500">{{ $gpoa->school_year }}</span></td>
                <td class="px-4 py-4">{{ $gpoa->college ?? '—' }}</td>
                <td class="px-4 py-4">{{ $gpoa->activities_count }} planned</td>
                <td class="px-4 py-4">
                    @php
                        $colors = [
                            'pending'  => 'bg-yellow-100 text-yellow-700',
                            'approved' => 'bg-green-100 text-green-700',
                            'stored'   => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $colors[$gpoa->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($gpoa->status) }}
                    </span>
                    @if($gpoa->reject_reason)
                    <p class="text-xs text-red-500 mt-1">{{ Str::limit($gpoa->reject_reason, 50) }}</p>
                    @endif
                </td>
                <td class="px-4 py-4 text-xs text-gray-500">{{ $gpoa->created_at->format('M d, Y') }}</td>
                <td class="px-4 py-4 text-center">
                    <a href="{{ route('gpoa.show', $gpoa) }}" class="text-sky-600 hover:underline text-xs font-semibold">View Details</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                    No GPOA submitted yet.
                    <a href="{{ route('gpoa.create') }}" class="text-sky-600 hover:underline ml-1">Submit your GPOA →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $gpoas->links() }}</div>
</x-app-layout>
