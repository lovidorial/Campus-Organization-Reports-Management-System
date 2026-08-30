<x-app-layout>
    @php
        $selectedOrganization = $prefill['organization_id'] ?? old('organization_id');
        $selectedOrg = $organizations->firstWhere('id', $selectedOrganization);
    @endphp

    <div class="mx-auto max-w-4xl py-8">
        <div class="mb-6 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-sky-600">Officer Directory</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900">Onboard Replacement</h2>
            </div>
            <a href="{{ route('admin.officers.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Officers</a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.officers.replacement.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $prefill['name'] ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $prefill['email'] ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </div>

                <div>
                    <label for="organization_id" class="mb-1 block text-sm font-medium text-slate-700">Organization</label>
                    <select id="organization_id" name="organization_id" required class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <option value="">Select organization</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" {{ old('organization_id', $prefill['organization_id'] ?? '') == $organization->id ? 'selected' : '' }}>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="position" class="mb-1 block text-sm font-medium text-slate-700">Position</label>
                    <input id="position" name="position" type="text" value="{{ old('position', $prefill['position'] ?? '') }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </div>

                <div>
                    <label for="term" class="mb-1 block text-sm font-medium text-slate-700">Term</label>
                    <input id="term" name="term" type="text" value="{{ old('term', $prefill['term'] ?? '') }}" placeholder="1st Term" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </div>

                <div>
                    <label for="school_year" class="mb-1 block text-sm font-medium text-slate-700">School Year</label>
                    <input id="school_year" name="school_year" type="text" value="{{ old('school_year', $prefill['school_year'] ?? '') }}" placeholder="2025-2026" class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </div>

                <input type="hidden" name="org_name" value="{{ old('org_name', $prefill['org_name'] ?? ($selectedOrg?->name ?? '')) }}">
                <input type="hidden" name="org_type" value="{{ old('org_type', $prefill['org_type'] ?? ($selectedOrg?->type ?? '')) }}">
                <input type="hidden" name="college" value="{{ old('college', $prefill['college'] ?? ($selectedOrg?->college ?? '')) }}">
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.officers.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-2xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">Create Officer Account</button>
            </div>
        </form>
    </div>
</x-app-layout>
