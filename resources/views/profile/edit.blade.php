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

            </div>

            <!-- Delete account UI removed -->
        </div>
    </div>
</x-app-layout>
