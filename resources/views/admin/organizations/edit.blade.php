<x-app-layout>
<div class="max-w-6xl mx-auto py-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 text-sm">← Back</a>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Edit Organization</h1>
            <p class="mt-2 text-sm text-slate-500 max-w-2xl">Update the organization account, logo, and secretary assignment in a single place.</p>
        </div>
    </div>

    <form action="{{ route('admin.organizations.update', $organization) }}" method="POST" enctype="multipart/form-data" class="space-y-6 bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
        @csrf @method('PATCH')

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-5">Organization Information</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">Organization Name *</label>
                        <input id="name" name="name" value="{{ old('name', $organization->name) }}" required
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100" />
                        @error('name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-semibold text-slate-700 mb-2">Organization Type *</label>
                        <select id="type" name="type" required
                                class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                            <option value="">Select type</option>
                            @foreach(['Student Council','Academic Org','Cultural Org','Sports Org','Religious Org','Publication','Other'] as $t)
                            <option value="{{ $t }}" {{ old('type',$organization->type)==$t?'selected':'' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="college" class="block text-sm font-semibold text-slate-700 mb-2">College / Unit</label>
                        <select id="college" name="college"
                                class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                            <option value="">Select college</option>
                            @foreach(['CTED','CCJE','CHM','CFAS','CBEA','CIT','CICS','University-Wide'] as $c)
                            <option value="{{ $c }}" {{ old('college',$organization->college)==$c?'selected':'' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="logo" class="block text-sm font-semibold text-slate-700 mb-2">Organization Logo</label>
                        <input id="logo" type="file" name="logo" accept="image/*"
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100" />
                        @error('logo')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="sc_president" class="block text-sm font-semibold text-slate-700 mb-2">Secretary / SC Head</label>
                        <input id="sc_president" name="sc_president" value="{{ old('sc_president', $organization->sc_president) }}"
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="term" class="block text-sm font-semibold text-slate-700 mb-2">Term</label>
                            <select id="term" name="term"
                                    class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                                <option value="">Select term</option>
                                <option value="1st Term" {{ old('term',$organization->term)=='1st Term'?'selected':'' }}>1st Term</option>
                                <option value="2nd Term" {{ old('term',$organization->term)=='2nd Term'?'selected':'' }}>2nd Term</option>
                            </select>
                        </div>
                        <div>
                            <label for="school_year" class="block text-sm font-semibold text-slate-700 mb-2">School Year</label>
                            <input id="school_year" name="school_year" value="{{ old('school_year', $organization->school_year) }}"
                                   class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                                   placeholder="e.g. 2025-2026" />
                        </div>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">{{ old('description', $organization->description) }}</textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', $organization->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                        <label for="is_active" class="text-sm font-semibold text-slate-700">Active Organization</label>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-5">Secretary Account</h2>
                <p class="text-sm text-slate-500">Secretary accounts are managed at the user level. To update the linked user, edit their profile from the Users list.</p>
                <div class="mt-6 space-y-4">
                    @if($organization->members->count())
                        @foreach($organization->members as $member)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $member->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $member->email }}</p>
                                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-400">Position</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $member->position ?? 'Secretary' }}</p>
                            </div>
                        @endforeach
                    @else
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">No secretary account linked yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <button type="submit" class="inline-flex items-center justify-center rounded-3xl bg-orange-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">Update Organization</button>
            <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center justify-center rounded-3xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
