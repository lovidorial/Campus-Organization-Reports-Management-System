<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1a2b6d">
    <title>Identity confirmation | OrgTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/welcomeblade.css') }}">
    <style>
        body.identity-page { min-height: 100vh; display: flex; flex-direction: column; background: #f8f9fc; }
        .identity-navbar { position: relative; flex: 0 0 auto; }
        .identity-nav-inner { grid-template-columns: auto minmax(0, 1fr) auto; }
        .identity-nav-links { gap: 8px; }
        .identity-nav-links .nav-link.is-active { background: rgba(245, 166, 35, 0.12); color: #1a2b6d; }
        .identity-stage { position: relative; isolation: isolate; display: grid; flex: 1 0 auto; min-height: min(680px, calc(100vh - 170px)); place-items: center; overflow: hidden; padding: 64px 20px; background-color: #1a2b6d; }
        .identity-stage::before, .identity-stage::after { position: absolute; inset: 0; z-index: -1; content: ''; }
        .identity-stage::before { background: url('{{ asset('images/hero-bg.jpg') }}') center / cover no-repeat; }
        .identity-stage::after { background: linear-gradient(to right, rgba(26, 43, 109, 0.80) 0%, rgba(26, 43, 109, 0.40) 60%, rgba(26, 43, 109, 0.15) 100%); }
        .identity-result-card { width: min(100%, 520px); padding: 48px 42px 42px; border: 1px solid rgba(221, 225, 235, 0.9); border-radius: 20px; background: #ffffff; box-shadow: 0 24px 64px rgba(19, 31, 82, 0.28); text-align: center; }
        .identity-state-icon { width: 72px; height: 72px; display: grid; place-items: center; margin: 0 auto 22px; border-radius: 50%; background: rgba(245, 166, 35, 0.14); color: #e89600; }
        .identity-state-icon svg { width: 34px; height: 34px; }
        .identity-state-icon.is-warning { background: #fff3df; color: #d4810a; }
        .identity-state-icon.is-mail { background: rgba(26, 43, 109, 0.08); color: #1a2b6d; }
        .identity-result-heading { margin: 0 0 14px; color: #1a2b6d; font-family: 'Sora', sans-serif; font-size: clamp(1.75rem, 4vw, 2.15rem); font-weight: 800; line-height: 1.2; }
        .identity-result-heading span { color: #e89600; }
        .identity-result-copy { margin: 0 auto 26px; max-width: 390px; color: #555577; font-size: 1rem; line-height: 1.7; }
        .identity-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
        .identity-primary, .identity-ghost { display: inline-flex; min-height: 48px; align-items: center; justify-content: center; gap: 9px; padding: 13px 26px; border-radius: 999px; font-family: 'Inter', sans-serif; font-size: .95rem; font-weight: 700; line-height: 1.3; text-decoration: none; transition: transform 150ms ease, box-shadow 150ms ease, background-color 150ms ease; }
        .identity-primary { border: 2px solid transparent; background: linear-gradient(135deg, #f5a623 0%, #e89600 100%); box-shadow: 0 6px 20px rgba(245, 166, 35, 0.25); color: #ffffff; }
        .identity-primary:hover { transform: translateY(-2px); background: linear-gradient(135deg, #e89600 0%, #d4810a 100%); box-shadow: 0 10px 28px rgba(245, 166, 35, 0.35); color: #ffffff; }
        .identity-ghost { border: 2px solid #dde1eb; background: #ffffff; color: #1a2b6d; }
        .identity-ghost:hover { transform: translateY(-2px); background: #f8f9fc; color: #131f52; }
        .identity-primary:focus-visible, .identity-ghost:focus-visible, .identity-nav-links a:focus-visible, .identity-nav-auth:focus-visible { outline: 3px solid #1a2b6d; outline-offset: 3px; }
        .identity-resend-form { margin: 0; }
        .identity-throttle { margin: 18px 0 0; color: #b42318; font-size: .9rem; line-height: 1.5; }
        .identity-footer { flex: 0 0 auto; padding: 28px 20px 18px; }
        .identity-footer .footer-brand { text-align: center; }
        .identity-footer .footer-desc { text-align: center; }
        .identity-footer .footer-hr { margin: 18px auto 14px; max-width: 1120px; }
        .identity-mobile-menu { display: none; position: relative; }
        .identity-mobile-menu summary { list-style: none; cursor: pointer; }
        .identity-mobile-menu summary::-webkit-details-marker { display: none; }
        .identity-mobile-toggle { display: grid; width: 42px; height: 42px; place-content: center; gap: 5px; border: 1px solid #dde1eb; border-radius: 50%; background: #ffffff; }
        .identity-mobile-toggle span { display: block; width: 18px; height: 2px; border-radius: 2px; background: #1a2b6d; }
        .identity-mobile-links { position: absolute; top: calc(100% + 12px); right: 0; z-index: 5; width: min(280px, calc(100vw - 32px)); padding: 8px; border: 1px solid #dde1eb; border-radius: 14px; background: #ffffff; box-shadow: 0 18px 40px rgba(19, 31, 82, 0.16); list-style: none; }
        .identity-mobile-links a { display: block; padding: 12px 14px; border-radius: 999px; color: #1a1a2e; font-size: .95rem; font-weight: 600; text-decoration: none; }
        .identity-mobile-links a:hover, .identity-mobile-links a:focus-visible { background: rgba(255, 152, 0, 0.08); color: #e89600; }
        .identity-mobile-login { margin-top: 6px; background: linear-gradient(135deg, #ff9800, #ffb347); color: #ffffff !important; text-align: center; }
        @media (max-width: 760px) {
            .identity-navbar .navbar-inner { grid-template-columns: auto 1fr; gap: 12px; }
            .identity-nav-links { display: none; }
            .identity-nav-auth { justify-self: end; }
            .identity-nav-auth > .nav-auth-link { display: none; }
            .identity-mobile-menu { display: block; }
            .identity-stage { min-height: 570px; padding: 42px 16px; }
            .identity-result-card { padding: 38px 24px 32px; border-radius: 16px; }
            .identity-actions { flex-direction: column; align-items: stretch; }
            .identity-primary, .identity-ghost { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .identity-primary, .identity-ghost { transition: none; }
        }
    </style>
</head>
<body class="identity-page">
    @php($resent = session()->has('verification-status'))

    <nav class="site-navbar identity-navbar" aria-label="Main navigation">
        <div class="navbar-inner identity-nav-inner">
            <a href="{{ route('welcome') }}" class="nav-brand" aria-label="OrgTrack home">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="OrgTrack logo" class="nav-logo-img">
                <span class="nav-brand-text">OrgTrack</span>
            </a>
            <ul class="nav-links identity-nav-links">
                <li><a href="{{ route('welcome') }}" class="nav-link is-active">Home</a></li>
                <li><a href="{{ route('welcome') }}#features" class="nav-link">Features</a></li>
                <li><a href="{{ route('welcome') }}#explore" class="nav-link">About</a></li>
                <li><a href="{{ route('welcome') }}#how-it-works" class="nav-link">How It Works</a></li>
                <li><a href="{{ route('public.orgchart') }}" class="nav-link">Org Chart</a></li>
            </ul>
            <div class="nav-auth identity-nav-auth">
                <a href="{{ route('login') }}" class="nav-auth-link">Login</a>
                <details class="identity-mobile-menu">
                    <summary class="identity-mobile-toggle" aria-label="Toggle navigation menu">
                        <span></span><span></span><span></span>
                    </summary>
                    <ul class="identity-mobile-links">
                        <li><a href="{{ route('welcome') }}">Home</a></li>
                        <li><a href="{{ route('welcome') }}#features">Features</a></li>
                        <li><a href="{{ route('welcome') }}#explore">About</a></li>
                        <li><a href="{{ route('welcome') }}#how-it-works">How It Works</a></li>
                        <li><a href="{{ route('public.orgchart') }}">Org Chart</a></li>
                        <li><a class="identity-mobile-login" href="{{ route('login') }}">Login</a></li>
                    </ul>
                </details>
            </div>
        </div>
    </nav>

    <main class="identity-stage">
        <section class="identity-result-card" aria-labelledby="identity-result-heading" aria-live="polite">
            @if ($verified)
                <div class="identity-state-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>
                </div>
                <h1 id="identity-result-heading" class="identity-result-heading">Identity <span>confirmed</span></h1>
                <p class="identity-result-copy">Your identity is confirmed. You can now sign in to OrgTrack.</p>
                <div class="identity-actions">
                    <a href="{{ route('login') }}" class="identity-primary">Go to login <span aria-hidden="true">&rarr;</span></a>
                </div>
            @elseif ($resent)
                <div class="identity-state-icon is-mail" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
                </div>
                <h1 id="identity-result-heading" class="identity-result-heading">Check your <span>email</span></h1>
                <p class="identity-result-copy">If this account can be verified, a confirmation link has been sent. The new link expires in 60 minutes.</p>
                <div class="identity-actions">
                    <a href="{{ route('login') }}" class="identity-primary">Go to login <span aria-hidden="true">&rarr;</span></a>
                </div>
            @else
                <div class="identity-state-icon is-warning" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 3h.01"/></svg>
                </div>
                <h1 id="identity-result-heading" class="identity-result-heading">Link <span>expired</span></h1>
                <p class="identity-result-copy">This confirmation link is invalid or has expired.</p>
                @if (session('verification-error'))
                    <p class="identity-throttle" role="alert">{{ session('verification-error') }}</p>
                @endif
                <div class="identity-actions">
                    @if ($userId)
                        <form method="POST" action="{{ URL::temporarySignedRoute('verification.resend', now()->addMinutes(60), ['id' => $userId]) }}" class="identity-resend-form">
                            @csrf
                            @if (session('verification_email', old('email')))
                                <input type="hidden" name="email" value="{{ session('verification_email', old('email')) }}">
                            @else
                                <label for="resend-email" class="sr-only">Account email</label>
                                <input id="resend-email" type="email" name="email" required autocomplete="email" placeholder="Account email" aria-label="Account email" style="display:block;width:100%;margin:0 0 10px;padding:12px 14px;border:1px solid #dde1eb;border-radius:8px;font:inherit;">
                            @endif
                            <button type="submit" class="identity-primary">Resend confirmation email</button>
                        </form>
                    @endif
                    <a href="{{ route('login') }}" class="identity-ghost">Back to login</a>
                </div>
            @endif
        </section>
    </main>

    <footer class="site-footer identity-footer">
        <div class="container">
            <div class="footer-brand"><span style="color:#f5a623;font-weight:700;">OrgTrack</span></div>
            <p class="footer-desc">Campus Student Organization Narrative &amp; Summary Reports.<br>A comprehensive platform for monitoring and managing student organization activities.</p>
            <hr class="footer-hr">
            <p class="footer-copy">&copy; OrgTrack - Campus Student Organization Narrative and Summary Reports. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
