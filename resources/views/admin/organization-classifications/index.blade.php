<x-app-layout>
<div class="space-y-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Organization Classifications</h1>
            <p class="mt-2 text-sm text-slate-500">Manage the OSDW reference list used for organization auto-classification.</p>
        </div>
        <a href="{{ route('admin.organization-classifications.create') }}" class="inline-flex items-center gap-2 rounded-3xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">+ Add Classification</a>
    </div>

    @if(session('success'))
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm text-slate-700">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-semibold">Organization Name</th>
                    <th class="px-4 py-3 font-semibold">Aliases</th>
                    <th class="px-4 py-3 font-semibold">Classification</th>
                    <th class="px-4 py-3 font-semibold">College / Area</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($classifications as $classification)
                    <tr>
                        <td class="px-4 py-4 font-medium text-slate-900">{{ $classification->org_name }}</td>
                        <td class="px-4 py-4">{{ implode(', ', $classification->aliases ?? []) }}</td>
                        <td class="px-4 py-4">{{ $classification->classification }}</td>
                        <td class="px-4 py-4">{{ $classification->college_area }}</td>
                        <td class="px-4 py-4 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('admin.organization-classifications.edit', $classification) }}" class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                <form action="{{ route('admin.organization-classifications.destroy', $classification) }}" method="POST" onsubmit="return confirm('Delete this classification?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No classifications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $classifications->links() }}</div>
    </div>
</div>
</x-app-layout>
