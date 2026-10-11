<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'OrgTrack') }} - Choose a new password</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
        <link rel="stylesheet" href="{{ asset('css/password-reset.css') }}">
        <script src="{{ asset('js/password-reset.js') }}" defer></script>
    </head>
    <body>
        <main class="login-page">
            <div class="login-card">
                <div class="login-panel login-panel-left">
                    <span class="sr-only">OSDW – Cagayan State University – Office of Student Development and Welfare – OrgTrack</span>
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
                            <div class="password-input-wrapper">
                                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="New password" class="form-input {{ $errors->has('password') ? 'form-input--error' : '' }}">
                                <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" title="Show password">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div id="password-strength" class="password-strength" aria-live="polite" aria-atomic="true" hidden>
                                <div class="password-strength__bar" aria-hidden="true">
                                    <span id="password-strength-fill" class="password-strength__fill"></span>
                                </div>
                                <div id="password-strength-label" class="password-strength__label">Password strength:</div>
                            </div>
                            <ul id="password-requirements" class="password-requirements" aria-live="polite">
                                <li class="password-requirement" data-rule="length"><span class="password-requirement__icon" aria-hidden="true">✓</span><span>At least 8 characters</span></li>
                                <li class="password-requirement" data-rule="uppercase"><span class="password-requirement__icon" aria-hidden="true">✓</span><span>Uppercase letter</span></li>
                                <li class="password-requirement" data-rule="lowercase"><span class="password-requirement__icon" aria-hidden="true">✓</span><span>Lowercase letter</span></li>
                                <li class="password-requirement" data-rule="number"><span class="password-requirement__icon" aria-hidden="true">✓</span><span>Number</span></li>
                                <li class="password-requirement" data-rule="symbol"><span class="password-requirement__icon" aria-hidden="true">✓</span><span>Symbol</span></li>
                            </ul>
                            @error('password')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group student-group">
                            <label for="password_confirmation" class="sr-only">Confirm new password</label>
                            <div class="password-input-wrapper">
                                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm new password" class="form-input {{ $errors->has('password_confirmation') ? 'form-input--error' : '' }}">
                                <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show password" title="Show password">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div id="password-match-status" class="password-match-status" aria-live="polite">Passwords match: No</div>
                            @error('password_confirmation')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <button id="reset-password-button" type="submit" class="login-button login-button--block" disabled>
                            <span class="button-text">Reset Password</span>
                            <span class="button-spinner" aria-hidden="true"></span>
                            <svg class="button-icon" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7"/></svg>
                        </button>
                    </form>

                    <div class="login-help">
                        <a href="{{ route('password.request') }}">Request another reset link</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>