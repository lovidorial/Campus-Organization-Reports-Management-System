<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)] items-center">
            <div class="relative mx-auto w-40">
                <div class="relative h-40 w-40 overflow-hidden rounded-full bg-slate-100 shadow-sm">
                    @if($user->profile_photo_path)
                        <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="Profile" class="h-full w-full object-cover" />
                    @else
                        <div class="flex h-full w-full items-center justify-center text-4xl font-semibold text-slate-500">{{ Illuminate\Support\Str::upper(substr($user->name, 0, 2)) }}</div>
                    @endif
                </div>
                <label for="photo" class="absolute left-1/2 top-1/2 inline-flex -translate-x-1/2 -translate-y-1/2 items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 cursor-pointer">
                    Change Photo
                    <input id="photo" name="photo" type="file" class="sr-only" />
                </label>
                <x-input-error class="mt-3 text-left text-sm text-rose-600" :messages="$errors->get('photo')" />
            </div>

            <div class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="mt-2 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-300 focus:ring-0" required autofocus autocomplete="name" />
                    <x-input-error class="mt-2 text-sm text-rose-600" :messages="$errors->get('name')" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-2 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-slate-300 focus:ring-0" required autocomplete="username" />
                    <x-input-error class="mt-2 text-sm text-rose-600" :messages="$errors->get('email')" />
                </div>

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="rounded-3xl bg-amber-50 p-4 text-sm text-slate-700">
                        <p>Your email address is unverified.</p>
                        <button form="send-verification" class="mt-3 inline-flex rounded-full bg-white px-4 py-2 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-100">Click here to re-send the verification email.</button>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-3 font-medium text-sm text-emerald-600">A new verification link has been sent to your email address.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-3xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Save</button>
        </div>

        @if (session('status') === 'profile-updated')
            <p class="mt-4 text-sm text-emerald-600">Saved.</p>
        @endif
    </form>
</section>
