<x-app-layout>
<div class="max-w-6xl mx-auto py-6">
    <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-6">
        <div>
            <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 text-sm">← Back</a>
            <h2 class="mt-3 text-3xl font-bold text-slate-900">Create Organization Account</h2>
            <p class="mt-2 text-sm text-slate-500 max-w-2xl">Register the organization and create the secretary account in one clean workflow.</p>
        </div>
    </div>

    <form action="{{ route('admin.organizations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900 mb-5">Organization Information</h3>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">Organization Name <span class="text-rose-500">*</span></label>
                        <input id="name" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                               placeholder="e.g. CITE-SC" />
                        @error('name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-semibold text-slate-700 mb-2">Organization Type <span class="text-rose-500">*</span></label>
                        <select id="type" name="type" required
                                class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                            <option value="">Select type</option>
                            <option value="Student Council" {{ old('type')=='Student Council' ? 'selected' : '' }}>Student Council</option>
                            <option value="Academic Org" {{ old('type')=='Academic Org' ? 'selected' : '' }}>Academic Org</option>
                            <option value="Cultural Org" {{ old('type')=='Cultural Org' ? 'selected' : '' }}>Cultural Org</option>
                            <option value="Sports Org" {{ old('type')=='Sports Org' ? 'selected' : '' }}>Sports Org</option>
                            <option value="Religious Org" {{ old('type')=='Religious Org' ? 'selected' : '' }}>Religious Org</option>
                            <option value="Publication" {{ old('type')=='Publication' ? 'selected' : '' }}>Publication</option>
                            <option value="Other" {{ old('type')=='Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('type')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="college" class="block text-sm font-semibold text-slate-700 mb-2">College / Unit</label>
                        <select id="college" name="college"
                                class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                            <option value="">Select college</option>
                            <option value="CTED" {{ old('college')=='CTED' ? 'selected' : '' }}>CTED</option>
                            <option value="CCJE" {{ old('college')=='CCJE' ? 'selected' : '' }}>CCJE</option>
                            <option value="CHM" {{ old('college')=='CHM' ? 'selected' : '' }}>CHM</option>
                            <option value="CFAS" {{ old('college')=='CFAS' ? 'selected' : '' }}>CFAS</option>
                            <option value="CBEA" {{ old('college')=='CBEA' ? 'selected' : '' }}>CBEA</option>
                            <option value="CIT" {{ old('college')=='CIT' ? 'selected' : '' }}>CIT</option>
                            <option value="CICS" {{ old('college')=='CICS' ? 'selected' : '' }}>CICS</option>
                            <option value="University-Wide" {{ old('college')=='University-Wide' ? 'selected' : '' }}>University-Wide</option>
                        </select>
                        @error('college')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="logo" class="block text-sm font-semibold text-slate-700 mb-2">Organization Logo</label>
                        <input id="logo" type="file" name="logo" accept="image/*"
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100" />
                        @error('logo')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="sc_president" class="block text-sm font-semibold text-slate-700 mb-2">Secretary / SC Head</label>
                        <input id="sc_president" name="sc_president" value="{{ old('sc_president') }}"
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                               placeholder="Secretary full name" />
                        @error('sc_president')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="term" class="block text-sm font-semibold text-slate-700 mb-2">Term</label>
                            <select id="term" name="term"
                                    class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                                <option value="">Select term</option>
                                <option value="1st Term" {{ old('term')=='1st Term' ? 'selected' : '' }}>1st Term</option>
                                <option value="2nd Term" {{ old('term')=='2nd Term' ? 'selected' : '' }}>2nd Term</option>
                            </select>
                        </div>
                        <div>
                            <label for="school_year" class="block text-sm font-semibold text-slate-700 mb-2">School Year</label>
                            <input id="school_year" name="school_year" value="{{ old('school_year') }}"
                                   class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                                   placeholder="e.g. 2025-2026" />
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                                  placeholder="Short summary of the organization">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900 mb-5">Secretary Account</h3>
                <div class="space-y-4">
                    <div>
                        <label for="secretary_name" class="block text-sm font-semibold text-slate-700 mb-2">Secretary Name <span class="text-rose-500">*</span></label>
                        <input id="secretary_name" name="secretary_name" value="{{ old('secretary_name') }}" required
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                               placeholder="Full name" />
                        @error('secretary_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="secretary_student_number" class="block text-sm font-semibold text-slate-700 mb-2">Student Number</label>
                        <input id="secretary_student_number" name="secretary_student_number" value="{{ old('secretary_student_number') }}"
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                               placeholder="e.g. 2025-12345" />
                        @error('secretary_student_number')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="secretary_email" class="block text-sm font-semibold text-slate-700 mb-2">School Email <span class="text-rose-500">*</span></label>
                        <input id="secretary_email" name="secretary_email" value="{{ old('secretary_email') }}" required type="email"
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                               placeholder="secretary@school.edu" />
                        @error('secretary_email')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="secretary_username" class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                        <input id="secretary_username" name="secretary_username" value="{{ old('secretary_username') }}"
                               class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                               placeholder="username" />
                        @error('secretary_username')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="secretary_password" class="block text-sm font-semibold text-slate-700 mb-2">Temporary Password <span class="text-rose-500">*</span></label>
                            <input id="secretary_password" name="secretary_password" required type="password"
                                   class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                                   placeholder="Enter temporary password" />
                            @error('secretary_password')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="secretary_password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password <span class="text-rose-500">*</span></label>
                            <input id="secretary_password_confirmation" name="secretary_password_confirmation" required type="password"
                                   class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"
                                   placeholder="Confirm password" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center rounded-3xl bg-orange-600 px-6 py-3 text-sm font-semibold text-black shadow-sm hover:bg-orange-700">Create Account</button>
            <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center justify-center rounded-3xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
