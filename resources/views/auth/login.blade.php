<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Login CSS -->
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="login-page">
            <div class="login-card">
                <div class="login-panel login-panel-left">
                    <div class="login-logo">
                        <img src="{{ asset('images/orgTracklogo.png') }}" alt="OSDW Orgtrack logo" loading="eager" decoding="async">
                    </div>
                    <h1 class="sr-only">OSDW</h1>
                    <p class="login-subtitle">Cagayan State University</p>
                    <div class="login-badge">OFFICE OF STUDENT DEVELOPMENT AND WELFARE</div>
                    <div class="login-description">
                        <img class="osdw-seal" src="{{ asset('images/osdw.logo.jpg') }}" alt="OSDW seal" onerror="this.style.display='none'">
                        <span class="description-divider" aria-hidden="true"></span>
                        <p>Orgtrack- Campus Student Organization Narrative &amp; Summary Reports — manage, monitor, and celebrate student activities.</p>
                    </div>
                </div>

                <div class="login-panel login-panel-right">
                    <a href="{{ route('welcome') }}" class="login-back-link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back
                    </a>

                    <div class="login-header">
                        <h1 class="login-heading">Welcome <span>Back</span></h1>
                        <p class="login-subtext">Sign in to your account</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-error">
                            <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <div>
                                <strong>Login Failed</strong>
                                <ul class="error-list">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success">
                            <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="login-form">
                        @csrf

                        <div>
                            <div class="form-group student-group">
                                <label for="email" class="sr-only">Email address</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="Email address"
                                    class="form-input {{ $errors->has('email') ? 'form-input--error' : '' }}"
                                />
                                @error('email')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group student-group">
                                <label for="password" class="sr-only">Password</label>
                                <div class="password-input-wrapper">
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Password"
                                        class="form-input {{ $errors->has('password') ? 'form-input--error' : '' }}"
                                    />
                                    <button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Toggle password visibility">
                                        <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="eye-icon eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                            <line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-actions student-group">
                                 <div class="remember-me-wrapper">
                                     <input id="remember_me" type="checkbox" name="remember" class="form-checkbox">
                                     <label for="remember_me" class="remember-label">Remember me</label>
                                 </div>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="forgot-password-link student">Forgot password?</a>
                                @endif
                            </div>

                            <div class="captcha-block">
                                <div class="captcha-prompt">
                                    <span id="loginCaptchaCode" class="captcha-code" aria-label="Captcha code">{{ $captcha }}</span>
                                    <button type="button" id="refreshCaptcha" class="captcha-refresh">Refresh code</button>
                                </div>
                                <label for="captcha" class="sr-only">Captcha code</label>
                                <input
                                    id="captcha"
                                    type="text"
                                    name="captcha"
                                    required
                                    autocomplete="off"
                                    autocapitalize="characters"
                                    placeholder="Enter the code above"
                                    class="form-input captcha-input {{ $errors->has('captcha') ? 'form-input--error' : '' }}"
                                />
                                @error('captcha')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="login-button student-submit-button">
                                <span>Sign In</span>
                                <svg class="button-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </button>
                        </div>

                    </form>

                    <div class="login-help">
                        <span>Having trouble?</span> <a href="{{ route('password.request') }}">Reset your password</a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function togglePassword(button) {
                const wrapper = button.closest('.password-input-wrapper');
                if (!wrapper) {
                    return;
                }
                const passwordInput = wrapper.querySelector('input[type="password"], input[type="text"]');
                const eyeOpen = wrapper.querySelector('.eye-open');
                const eyeClosed = wrapper.querySelector('.eye-closed');

                if (!passwordInput) {
                    return;
                }

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    if (eyeOpen) eyeOpen.style.display = 'none';
                    if (eyeClosed) eyeClosed.style.display = 'block';
                } else {
                    passwordInput.type = 'password';
                    if (eyeOpen) eyeOpen.style.display = 'block';
                    if (eyeClosed) eyeClosed.style.display = 'none';
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.animation = 'slideIn 0.4s ease-out';
                });

                const refreshButton = document.getElementById('refreshCaptcha');
                const captchaCode = document.getElementById('loginCaptchaCode');
                const captchaInput = document.getElementById('captcha');
                refreshButton?.addEventListener('click', async function() {
                    refreshButton.disabled = true;
                    try {
                        const response = await fetch(@json(route('login.captcha.refresh')), {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!response.ok) throw new Error('Captcha refresh failed.');
                        const data = await response.json();
                        captchaCode.textContent = data.captcha;
                        captchaInput.value = '';
                        captchaInput.focus();
                    } finally {
                        refreshButton.disabled = false;
                    }
                });
            });
        </script>
    </body>
</html>
