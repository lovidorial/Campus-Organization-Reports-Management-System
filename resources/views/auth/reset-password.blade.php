<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Orgtrack') }} - Choose a new password</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="login-page">
            <div class="login-card">
                <section class="login-panel login-panel-left">
                    <div class="deco-circle deco-circle-1"></div>
                    <div class="deco-circle deco-circle-2"></div>
                    <div class="deco-circle deco-circle-3"></div>
                    <div class="login-logo"><img src="{{ asset('images/osdw.logo.jpg') }}" alt="OSDW Logo" onerror="this.style.display='none'"></div>
                    <h1 class="login-title">OSDW</h1>
                    <p class="login-subtitle">Cagayan State University</p>
                    <div class="login-badge">OFFICE OF STUDENT DEVELOPMENT AND WELFARE</div>
                    <p class="login-description">Choose a new password for your Orgtrack account.</p>
                </section>

                <section class="login-panel login-panel-right">
                    <a href="{{ route('login') }}" class="login-back-link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Back to sign in
                    </a>

                    <header class="login-header">
                        <h1 class="login-heading">Choose a new password</h1>
                        <p class="login-subtext">Use a strong password you haven’t used before.</p>
                    </header>

                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">
                            <div>
                                <strong>Password reset could not be completed.</strong>
                                <ul class="error-list">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.store') }}" class="login-form">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="form-group student-group">
                            <label for="email" class="sr-only">Email address</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" placeholder="Email address" class="form-input !pl-4 {{ $errors->has('email') ? 'form-input--error' : '' }}">
                            @error('email')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group student-group">
                            <label for="password" class="sr-only">New password</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="New password" class="form-input !pl-4 {{ $errors->has('password') ? 'form-input--error' : '' }}">
                            @error('password')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group student-group">
                            <label for="password_confirmation" class="sr-only">Confirm new password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm new password" class="form-input !pl-4 {{ $errors->has('password_confirmation') ? 'form-input--error' : '' }}">
                            @error('password_confirmation')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <button type="submit" class="login-button w-full">Reset Password</button>
                    </form>
                    <div class="login-help"><a href="{{ route('password.request') }}">Request another reset link</a></div>
                </section>
            </div>
        </main>
    </body>
</html>