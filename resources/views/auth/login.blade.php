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
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Login CSS -->
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="login-page">
            <div class="login-card">
                <div class="login-panel login-panel-left">
                    <div class="login-logo">
                        <img src="{{ asset('images/osdw.logo.jpg') }}" alt="OSDW Logo" onerror="this.style.display='none'">
                    </div>
                    <h1 class="login-title">OSDW</h1>
                    <p class="login-subtitle">Cagayan State University</p>
                    <div class="login-badge">Office of Student Development and Welfare</div>
                    <p class="login-description">
                        Campus Student Organization Activities Tracking System — manage, monitor, and celebrate student activities.
                    </p>
                </div>

                <div class="login-panel login-panel-right">
                    <a href="{{ route('welcome') }}" class="login-back-link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back
                    </a>

                    <div class="login-header">
                        <h1 class="login-heading">Welcome Back</h1>
                        <p class="login-subtext">Please log in to your account.</p>
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

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Email Address"
                                class="form-input {{ $errors->has('email') ? 'form-input--error' : '' }}"
                            />
                            @error('email')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
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
                                <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-actions">
                            <label class="form-checkbox">
                                <input
                                    id="remember_me"
                                    type="checkbox"
                                    name="remember"
                                    class="checkbox-input"
                                >
                                <span class="checkbox-label">Remember me</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="forgot-password-link">Forgot Password?</a>
                            @endif
                        </div>

                        <button type="submit" class="login-button">
                            <span>Login</span>
                            <svg class="button-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </form>

                    <div class="signup-text">
                        Don't have an account? <a href="{{ route('register') }}">Sign Up here</a>
                    </div>

                    <div class="login-help">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <span>Having trouble? <a href="{{ route('password.request') }}">Reset your password</a></span>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function togglePassword() {
                const passwordInput = document.getElementById('password');
                const eyeIcon = document.querySelector('.password-toggle .eye-icon');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.style.opacity = '1';
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.style.opacity = '0.5';
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.animation = 'slideIn 0.4s ease-out';
                });
            });
        </script>
    </body>
</html>
