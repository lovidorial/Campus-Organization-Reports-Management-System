<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Schedule | Orgtrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/welcomeblade.css') }}">
    <style>
        .schedule-page { min-height: 100vh; background: var(--off-white); }
        .schedule-nav-inner { max-width: 1200px; margin: 0 auto; padding: 0 24px; min-height: 68px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .schedule-nav-links { display: flex; align-items: center; justify-content: flex-end; gap: 5px; }
        .schedule-nav-links .nav-link { border-radius: 999px; color: var(--text-mid); font-size: 0.84rem; font-weight: 600; padding: 9px 11px; text-decoration: none; white-space: nowrap; }
        .schedule-nav-links .nav-link:hover { background: rgba(240, 165, 0, 0.08); color: var(--gold-dark); }
        .schedule-nav-links .nav-link.is-active { color: var(--navy); background: rgba(240, 165, 0, 0.12); }
        .schedule-nav-links .nav-auth-link { display: inline-flex !important; padding: 9px 16px; font-size: 0.82rem; white-space: nowrap; }
        .schedule-hero { min-height: 255px; display: flex; align-items: center; padding: 42px 24px; color: var(--white); background-color: var(--navy); background-image: linear-gradient(to right, rgba(26, 43, 109, 0.80) 0%, rgba(26, 43, 109, 0.40) 60%, rgba(26, 43, 109, 0.15) 100%), url("{{ asset('images/hero-bg.jpg') }}"); background-position: center 42%; background-size: cover; }
        .schedule-hero-inner { width: 100%; max-width: 1100px; margin: 0 auto; }
        .schedule-eyebrow { display: inline-block; margin-bottom: 12px; padding: 5px 12px; border: 1px solid rgba(245, 166, 35, 0.45); border-radius: 999px; background: rgba(245, 166, 35, 0.22); color: #ffc555; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; }
        .schedule-title { margin: 0; color: var(--white); font-family: 'Sora', sans-serif; font-size: clamp(2.2rem, 6vw, 3.4rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.5px; text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5); }
        .schedule-subtitle { max-width: 620px; margin: 12px 0 0; color: rgba(255, 255, 255, 0.95); font-size: 1.1rem; line-height: 1.7; text-shadow: 0 1px 6px rgba(0, 0, 0, 0.4); }
        .schedule-content { max-width: 1100px; margin: -24px auto 0; padding: 0 24px 56px; position: relative; }
        .schedule-panel { overflow: hidden; border: 1px solid var(--border); border-top: 4px solid var(--gold); border-radius: 12px; background: var(--white); box-shadow: 0 14px 36px rgba(26, 43, 109, 0.09); }
        .schedule-panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 22px 16px; }
        .schedule-panel-title { margin: 0; color: var(--navy); font-family: 'Sora', sans-serif; font-size: 1.1rem; font-weight: 700; }
        .schedule-count { flex: 0 0 auto; padding: 5px 10px; border-radius: 999px; background: #fff4d6; color: #8a5b00; font-size: 0.75rem; font-weight: 700; }
        .schedule-table-wrap { overflow-x: auto; }
        .schedule-table { width: 100%; border-collapse: collapse; text-align: left; }
        .schedule-table th { padding: 11px 16px; border-top: 1px solid var(--off-white); border-bottom: 1px solid var(--border); background: var(--off-white); color: var(--text-mid); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
        .schedule-table td { padding: 14px 16px; border-bottom: 1px solid var(--off-white); color: var(--text-dark); font-size: 0.88rem; vertical-align: middle; }
        .schedule-table tbody tr:last-child td { border-bottom: 0; }
        .schedule-table tbody tr:hover { background: #fffbf2; }
        .schedule-date { min-width: 135px; color: var(--navy) !important; font-weight: 700; white-space: nowrap; }
        .schedule-time { min-width: 112px; color: var(--text-mid) !important; white-space: nowrap; }
        .schedule-activity { color: var(--navy) !important; font-weight: 700; }
        .schedule-category { display: inline-block; padding: 4px 9px; border: 1px solid #f1d899; border-radius: 999px; background: #fff8e7; color: #765000; font-size: 0.75rem; font-weight: 600; }
        .schedule-venue { color: var(--text-mid) !important; }
        .schedule-empty { padding: 42px 20px !important; text-align: center; }
        .schedule-empty-mark { width: 42px; height: 4px; margin: 0 auto 14px; border-radius: 99px; background: linear-gradient(90deg, #f0a500, #d4900a); }
        .schedule-empty-title { margin: 0; color: var(--navy); font-family: 'Sora', sans-serif; font-size: 1rem; font-weight: 700; }
        .schedule-empty-copy { margin: 6px 0 0; color: var(--text-muted); font-size: 0.85rem; }
        .schedule-browse { display: inline-flex; margin-top: 17px; padding: 9px 15px; border-radius: 7px; background: var(--navy); color: var(--white); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
        .schedule-browse:hover { background: var(--navy-light); color: var(--white); }
        .schedule-footer { max-width: 1100px; margin: 0 auto; padding: 0 24px 24px; color: var(--text-muted); font-size: 0.75rem; }
        @media (max-width: 700px) {
            .schedule-nav-inner { min-height: 60px; padding: 0 12px; gap: 4px; }
            .schedule-nav-inner .nav-logo-img { width: 42px; height: 38px; }
            .schedule-nav-inner .nav-brand { gap: 4px; margin-right: 0; }
            .schedule-nav-inner .nav-brand-text { font-size: 0.88rem; letter-spacing: 0.04em; }
            .schedule-nav-links { gap: 2px; }
            .schedule-nav-links .browse-link { display: none; }
            .schedule-nav-links .nav-link { padding: 8px 9px; font-size: 0.72rem; }
            .schedule-nav-links .nav-auth-link { padding: 8px 11px; font-size: 0.72rem; }
            .schedule-hero { min-height: 215px; padding: 34px 18px; }
            .schedule-content { margin-top: -18px; padding: 0 12px 40px; }
            .schedule-panel-heading { padding: 16px; }
            .schedule-table, .schedule-table tbody, .schedule-table tr, .schedule-table td { display: block; width: 100%; }
            .schedule-table { min-width: 0; }
            .schedule-table thead { display: none; }
            .schedule-table tbody { display: grid; gap: 9px; padding: 10px; }
            .schedule-table tbody tr { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-content: start; border: 1px solid #dde1eb; border-left: 3px solid #f0a500; border-radius: 8px; background: #fff; padding: 8px 10px; }
            .schedule-table tbody tr:hover { background: #fffbf2; }
            .schedule-table td { min-width: 0; padding: 6px 4px; border: 0; white-space: normal; overflow-wrap: anywhere; }
            .schedule-table td::before { display: block; margin-bottom: 2px; color: #9898bb; content: ''; font-size: 0.62rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
            .schedule-table .schedule-date::before { content: 'Date'; }
            .schedule-table .schedule-time::before { content: 'Time'; }
            .schedule-table td:nth-child(4)::before { content: 'Category'; }
            .schedule-table td:nth-child(5)::before { content: 'Venue'; }
            .schedule-table .schedule-activity { grid-column: 1 / -1; grid-row: 1; font-family: 'Sora', sans-serif; font-size: 0.93rem; }
            .schedule-table .schedule-activity::before { content: 'Activity'; }
            .schedule-table tbody tr:has(.schedule-empty) { display: block; border: 0; padding: 0; }
            .schedule-table .schedule-empty { padding: 30px 14px !important; text-align: center; }
            .schedule-table .schedule-empty::before { content: none; }
            .schedule-footer { padding: 0 16px 20px; }
        }
    </style>
</head>
<body class="schedule-page">
    <header class="site-navbar">
        <div class="schedule-nav-inner">
            <a href="{{ url('/') }}" class="nav-brand">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="Orgtrack logo" class="nav-logo-img">
                <span class="nav-brand-text">Orgtrack</span>
            </a>
            <nav class="schedule-nav-links">
                <a href="{{ url('/') }}" class="nav-link">Home</a>
                <a href="{{ route('public.activities') }}" class="nav-link browse-link">Browse Activities</a>
                <a href="{{ route('public.schedule') }}" class="nav-link is-active">Upcoming Schedule</a>
                @guest
                    <a href="{{ route('login') }}" class="nav-auth-link">Login</a>
                @else
                    <a href="{{ route('dashboard') }}" class="nav-auth-link">Dashboard</a>
                @endguest
            </nav>
        </div>
    </header>

    <section class="schedule-hero">
        <div class="schedule-hero-inner">
            <span class="schedule-eyebrow">Orgtrack activity calendar</span>
            <h1 class="schedule-title">Upcoming Schedule</h1>
            <p class="schedule-subtitle">See approved activities open to the campus community, with dates, times, categories, and venues.</p>
        </div>
    </section>

    <main class="schedule-content">
        <section class="schedule-panel" aria-labelledby="schedule-list-title">
            <div class="schedule-panel-heading">
                <h2 id="schedule-list-title" class="schedule-panel-title">Scheduled Activities</h2>
                <span class="schedule-count">{{ $activities->count() }} {{ \Illuminate\Support\Str::plural('activity', $activities->count()) }}</span>
            </div>
            <div class="schedule-table-wrap">
                <table class="schedule-table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Activity</th>
                        <th>Category</th>
                        <th>Venue</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td class="schedule-date">
                                {{ $activity->date?->format('M d, Y') ?? '—' }}
                                @if($activity->end_date && $activity->end_date->ne($activity->date))
                                    <span>– {{ $activity->end_date->format('M d, Y') }}</span>
                                @endif
                            </td>
                            <td class="schedule-time">
                                {{ $activity->start_time ? substr((string) $activity->start_time, 0, 5) : '—' }}
                                @if($activity->end_time)
                                    – {{ substr((string) $activity->end_time, 0, 5) }}
                                @endif
                            </td>
                            <td class="schedule-activity">{{ $activity->title }}</td>
                            <td><span class="schedule-category">{{ $activity->category ?? '—' }}</span></td>
                            <td class="schedule-venue">{{ $activity->venue ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="schedule-empty">
                                <div class="schedule-empty-mark"></div>
                                <p class="schedule-empty-title">No upcoming activities yet</p>
                                <p class="schedule-empty-copy">Check back soon or browse the campus activity highlights.</p>
                                <a href="{{ route('public.activities') }}" class="schedule-browse">Browse Activities</a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <footer class="schedule-footer">Orgtrack · Campus Student Organization Activities</footer>
</body>
</html>