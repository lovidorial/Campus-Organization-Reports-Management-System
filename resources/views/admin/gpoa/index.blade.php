<x-app-layout>
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">GPOA Monitoring</h2>
    <p class="text-sm text-gray-500">Browse organizational plans and planned activities</p>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-lg border bg-white p-4"><p class="text-xs uppercase text-gray-500">GPOAs</p><p class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</p></div>
</div>

<form method="GET" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
    <input type="search" name="search" aria-label="Search organizations or GPOAs" placeholder="Search organization, term, year..." value="{{ request('search') }}" class="min-w-0 rounded-md border-slate-300 px-3 py-2 text-sm">
    <select name="school_year" aria-label="Academic year" class="rounded-md border-slate-300 px-3 py-2 text-sm"><option value="">All academic years</option>@foreach($schoolYears as $schoolYear)<option value="{{ $schoolYear }}" @selected(request('school_year') === $schoolYear)>{{ $schoolYear }}</option>@endforeach</select>
    <select name="term" aria-label="Semester" class="rounded-md border-slate-300 px-3 py-2 text-sm"><option value="">All semesters</option>@foreach($terms as $term)<option value="{{ $term }}" @selected(request('term') === $term)>{{ $term }}</option>@endforeach</select>
    <select name="college" aria-label="College" class="rounded-md border-slate-300 px-3 py-2 text-sm"><option value="">All colleges</option>@foreach($colleges as $college)<option value="{{ $college }}" @selected(request('college') === $college)>{{ $college }}</option>@endforeach</select>
    <select name="classification" aria-label="Organization classification" class="rounded-md border-slate-300 px-3 py-2 text-sm"><option value="">All classifications</option>@foreach($classifications as $classification)<option value="{{ $classification }}" @selected(request('classification') === $classification)>{{ $classification }}</option>@endforeach</select>
    <select name="organization" aria-label="Organization" class="rounded-md border-slate-300 px-3 py-2 text-sm"><option value="">All organizations</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((string) request('organization') === (string) $organization->id)>{{ $organization->name }}</option>@endforeach</select>
    <div class="flex gap-2 sm:col-span-2 lg:col-span-6 lg:justify-end">
        <button type="submit" class="rounded-md bg-orange-700 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-800">Apply filters</button>
        <a href="{{ route('admin.gpoa.index') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
    </div>
</form>

<div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
    <table class="w-full text-sm min-w-[900px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Organization</th>
                <th class="p-3 text-left">Term / SY</th>
                <th class="p-3 text-left">College</th>
                <th class="p-3 text-left">Planned Activities</th>
                <th class="p-3 text-left">Status</th>
                <th class="p-3 text-left">Approved Document</th>
                <th class="p-3 text-left">Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gpoas as $gpoa)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3">
                    <div>{{ $gpoa->user->org_name ?? $gpoa->user->name }}</div>
                    <div class="mt-1 text-xs text-slate-500">Submitted by {{ $gpoa->user->name }}</div>
                </td>
                <td class="p-3">{{ $gpoa->term }}<br><span class="text-xs text-gray-500">{{ $gpoa->school_year }}</span></td>
                <td class="p-3">{{ $gpoa->college ?? '—' }}</td>
                <td class="p-3">{{ $gpoa->activities_count }}</td>
                <td class="p-3"><span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">Approved</span></td>
                <td class="p-3">
                    @if($gpoa->document_path)
                        <a href="{{ route('admin.gpoa.document', $gpoa) }}" data-file-viewer data-title="Approved GPOA Document" class="text-xs font-semibold text-emerald-700 hover:underline">View document</a>
                    @else
                        —
                    @endif
                </td>
                <td class="p-3"><a href="{{ route('admin.gpoa.show', $gpoa) }}" class="text-xs font-semibold text-sky-700 hover:underline">View details</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="p-8 text-center text-gray-400">No GPOAs found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $gpoas->links() }}</div>
</x-app-layout>
