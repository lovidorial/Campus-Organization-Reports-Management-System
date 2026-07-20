<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-6">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900">Profile Information</h1>
                        <p class="mt-1 text-sm text-slate-500">Update your account's profile information and email address.</p>
                    </div>
                </div>
                <div class="mt-6">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-6">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-slate-900">Update Password</h2>
                        <p class="mt-1 text-sm text-slate-500">Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                    @include('profile.partials.update-password-form')
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-6">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-slate-900">Data Backup</h2>
                        <p class="mt-1 text-sm text-slate-500">Schedule automatic data backups and manage your backup locations.</p>
                    </div>
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Backup Location</label>
                            <div class="mt-2 flex items-center gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 16v-2a4 4 0 00-4-4h-1.26A8 8 0 004 10v2"/><path d="M16 16v2a4 4 0 01-4 4H6a4 4 0 01-4-4V15"/><path d="M16 16h-1"/><path d="M14 12h6"/></svg>
                                <span class="truncate">Cloud Storage - AWS S3</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Encryption</label>
                            <div class="mt-2 flex items-center gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                <span class="font-medium text-slate-900">Encrypted</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 pt-3">
                            <button type="button" class="rounded-3xl border border-slate-200 bg-slate-100 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">View History</button>
                            <button type="button" class="rounded-3xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">Run Backup Now</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete account UI removed -->
        </div>
    </div>
</x-app-layout>
