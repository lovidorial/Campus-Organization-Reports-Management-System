<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Orgtrack') }} - Reset password</title>
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
                    <div class="login-logo"><img src="{{ asset('images/orgTracklogo.png') }}" alt="OSDW Orgtrack logo" loading="eager" decoding="async"></div>
                    <h1 class="sr-only">OSDW</h1>
                    <p class="login-subtitle">Cagayan State University</p>
                    <div class="login-badge">OFFICE OF STUDENT DEVELOPMENT AND WELFARE</div>
                    <div class="login-description">
                        <img class="osdw-seal" src="{{ asset('images/osdw.logo.jpg') }}" alt="OSDW seal" onerror="this.style.display='none'">
                        <span class="description-divider" aria-hidden="true"></span>
                        <p>Orgtrack- Campus Student Organization Narrative &amp; Summary Reports — manage, monitor, and celebrate student activities.</p>
                    </div>
                </section>

                <section class="login-panel login-panel-right">
                    <a href="{{ route('login') }}" class="login-back-link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Back to sign in
                    </a>

                    <header class="login-header">
                        <h1 class="login-heading">Reset your password</h1>
                        <p class="login-subtext">Enter your account email and we’ll send a reset link.</p>
                    </header>

                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">
                            <div>
                                <strong>Please check the email address.</strong>
                                <ul class="error-list">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="login-form">
                        @csrf
                        <div class="form-group student-group">
                            <label for="email" class="sr-only">Email address</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Email address" class="form-input {{ $errors->has('email') ? 'form-input--error' : '' }}">
                            @error('email')<span class="form-error">{{ $message }}</span>@enderror
                        </div>
                        <button type="submit" class="login-button w-full"><span>Email Password Reset Link</span><svg class="button-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7"/></svg></button>
                    </form>
                    <div class="login-help"><a href="{{ route('login') }}">Return to sign in</a></div>
                </section>
            </div>
        </main>
    </body>
</html>