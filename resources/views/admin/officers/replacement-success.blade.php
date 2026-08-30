<x-app-layout>
    <div class="mx-auto max-w-2xl py-12">
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700">Replacement Onboarded</p>
            <h2 class="mt-3 text-3xl font-bold text-slate-900">Officer account created successfully</h2>
            <p class="mt-3 text-slate-600">The account for {{ session('replacement_name') }} has been created with an active officer profile.</p>

            <div class="mt-6 rounded-2xl border border-emerald-200 bg-white p-4">
                <p class="text-sm font-medium text-slate-600">Temporary password</p>
                <div class="mt-2 flex items-center justify-between gap-3 rounded-xl bg-slate-100 px-3 py-2">
                    <span class="font-mono text-lg font-semibold tracking-wide text-slate-900">{{ session('replacement_password') }}</span>
                    <button type="button" onclick="navigator.clipboard?.writeText('{{ session('replacement_password') }}')" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">Copy</button>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('admin.officers.index') }}" class="rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Return to officers</a>
                <a href="{{ route('admin.officers.replacement.create') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Create another</a>
            </div>
        </div>
    </div>
</x-app-layout>
