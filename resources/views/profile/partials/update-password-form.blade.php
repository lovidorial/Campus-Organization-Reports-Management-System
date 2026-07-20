<section>
    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-semibold text-slate-700">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="mt-2 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-300 focus:ring-0" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2 text-sm text-rose-600" />
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-semibold text-slate-700">New Password</label>
            <input id="update_password_password" name="password" type="password" class="mt-2 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-300 focus:ring-0" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2 text-sm text-rose-600" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-300 focus:ring-0" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2 text-sm text-rose-600" />
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="rounded-3xl bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-medium text-slate-900">Password requirements</p>
                <ul class="mt-3 space-y-2">
                    <li>• Must be at least 8 characters</li>
                    <li>• Include 1 special character</li>
                </ul>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" class="rounded-3xl border border-slate-200 bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200">Cancel</button>
                <button type="submit" class="rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Save Password</button>
            </div>
        </div>

        @if (session('status') === 'password-updated')
            <p class="text-sm text-emerald-600">Saved.</p>
        @endif
    </form>
</section>
