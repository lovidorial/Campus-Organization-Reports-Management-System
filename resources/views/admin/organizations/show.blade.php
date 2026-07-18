<x-app-layout>
<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 text-sm">← Back</a>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">{{ $organization->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">This page shows the organization account details and the secretary access profile for the selected organization.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.organizations.edit', $organization) }}" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Edit</a>
            <form action="{{ route('admin.organizations.reset-password', $organization) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset Password</button>
            </form>
            @if($organization->is_active)
            <form action="{{ route('admin.organizations.deactivate', $organization) }}" method="POST" class="inline" onsubmit="return confirm('Deactivate this organization account?');">
                @csrf
                <button type="submit" class="rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700">Deactivate</button>
            </form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col items-center gap-4 text-center">
                @if($organization->logo_path)
                    <img src="{{ asset('storage/'.$organization->logo_path) }}" alt="{{ $organization->name }} logo" class="h-28 w-28 rounded-3xl object-cover border border-slate-200" />
                @else
                    <div class="flex h-28 w-28 items-center justify-center rounded-3xl bg-orange-100 text-4xl font-bold text-orange-700 uppercase">{{ Illuminate\Support\Str::limit($organization->name, 2, '') }}</div>
                @endif
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Organization</p>
                    <p class="mt-2 text-xl font-semibold text-slate-900">{{ $organization->name }}</p>
                </div>
            </div>
            <div class="mt-8 space-y-4">
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p>
                    <p class="mt-2 font-semibold text-slate-900">
                        @if(! $secretary)
                            Pending Secretary
                        @elseif($organization->is_active)
                            Active
                        @else
                            Inactive
                        @endif
                    </p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Activities Submitted</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Registered</p>
                    <p class="mt-2 text-slate-900">{{ $organization->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Organization Details</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Organization Type</p>
                        <p class="mt-2 font-medium text-slate-900">{{ $organization->type ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">College</p>
                        <p class="mt-2 font-medium text-slate-900">{{ $organization->college ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Description</p>
                        <p class="mt-2 text-slate-600">{{ $organization->description ?? 'No description added.' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Secretary Account</h2>
                @if($secretary)
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Secretary Name</p>
                        <p class="mt-2 font-medium text-slate-900">{{ $secretary->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Student Number</p>
                        <p class="mt-2 font-medium text-slate-900">{{ $secretary->student_number ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">School Email</p>
                        <p class="mt-2 font-medium text-slate-900">{{ $secretary->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Username</p>
                        <p class="mt-2 font-medium text-slate-900">{{ $secretary->username ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Last Login</p>
                        <p class="mt-2 font-medium text-slate-900">{{ optional($secretary->last_login_at)->format('M d, Y h:i A') ?? 'Not available' }}</p>
                    </div>
                </div>
                @else
                <p class="text-sm text-slate-500">No secretary account is linked to this organization yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="text-lg font-semibold text-slate-900">Recent Activities Submitted</h2>
            <span class="text-sm text-slate-500">{{ $activities->total() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-sm text-slate-600">
                <thead class="bg-slate-50 border-t border-b border-slate-200">
                    <tr>
                        <th class="p-4 text-left font-semibold text-slate-500">Title</th>
                        <th class="p-4 text-left font-semibold text-slate-500">Submitted By</th>
                        <th class="p-4 text-left font-semibold text-slate-500">Category</th>
                        <th class="p-4 text-left font-semibold text-slate-500">Date</th>
                        <th class="p-4 text-left font-semibold text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $act)
                    <tr class="border-b last:border-0 hover:bg-slate-50">
                        <td class="p-4 font-medium text-slate-900">{{ $act->title }}</td>
                        <td class="p-4 text-slate-500">{{ $act->user->name ?? '—' }}</td>
                        <td class="p-4">
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $act->category ?? '—' }}</span>
                        </td>
                        <td class="p-4">{{ optional($act->date)->format('M d, Y') ?? '—' }}</td>
                        <td class="p-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $act->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($act->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">{{ ucfirst($act->status ?? 'unknown') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="p-6 text-center text-slate-400">No activities submitted yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $activities->links() }}</div>
    </div>
</div>
</x-app-layout>
