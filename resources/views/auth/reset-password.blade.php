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
                <div class="login-panel login-panel-left">
                    <span class="sr-only">OSDW – Cagayan State University – Office of Student Development and Welfare – Orgtrack</span>
                </div>

                <div class="login-panel login-panel-right">
                    <a href="{{ route('login') }}" class="login-back-link">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to sign in
                    </a>

                    <div class="login-header">
                        <h1 class="login-heading">Choose a new <span>password</span></h1>
                        <p class="login-subtext">Use a strong password you haven’t used before.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">
                            <svg class="alert-icon" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
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
                            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" placeholder="Email address" class="form-input {{ $errors->has('email') ? 'form-input--error' : '' }}">
                            @error('email')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group student-group">
                            <label for="password" class="sr-only">New password</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="New password" class="form-input {{ $errors->has('password') ? 'form-input--error' : '' }}">
                            @error('password')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group student-group">
                            <label for="password_confirmation" class="sr-only">Confirm new password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm new password" class="form-input {{ $errors->has('password_confirmation') ? 'form-input--error' : '' }}">
                            @error('password_confirmation')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <button type="submit" class="login-button login-button--block"><span>Reset Password</span><svg class="button-icon" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7"/></svg></button>
                    </form>

                    <div class="login-help">
                        <a href="{{ route('password.request') }}">Request another reset link</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>