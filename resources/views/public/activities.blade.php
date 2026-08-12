<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Activities | CORMS</title>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Raleway:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .hero.hero-with-bg {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        position: relative;
        }

        .hero.hero-with-bg::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient( to right,rgba(26, 43, 109, 0.80) 0%, rgba(26, 43, 109, 0.40) 60%, rgba(26, 43, 109, 0.15) 100%);
        z-index: 1;
}

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fc 0%, #eff2f7 100%);
            min-height: 100vh;
            color: #1a1a2e;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(221, 225, 235, 0.3);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 12px 0 !important;
        }

        .navbar-brand {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1a2b6d !important;
            letter-spacing: 1px;
            margin-right: 24px;
        }

        .nav-link {
            color: gray !important;
            font-weight: 600;
            margin: 0 12px;
            font-size: 0.93rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #ec7e08 !important;
            background: #f8f9fc;
            border-radius: 5px;
            padding: 6px 12px;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff9800 0%, #ffb347 100%);
            color: white !important;
            font-weight: 700;
            padding: 10px 28px;
            border-radius: 999px;
            border: none;
            font-size: 0.95rem;
            box-shadow: 0 10px 28px rgba(255, 152, 0, 0.24);
            transition: all 0.25s ease;
            text-decoration: none;
        }
    
        .btn-login:hover {
            background: linear-gradient(135deg, #ff9f00 0%, #ffb347 100%);
            color: white !important;
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(255, 152, 0, 0.28);
            text-decoration: none;
        }

        /* Page Header */
        .page-header {
            position: relative;
            min-height: 450px;
            overflow: hidden;
            padding: 120px 48px;
            margin-bottom: 40px;
            text-align: center;
            color: white;
            background-image: url('{{ asset('images/hero-bg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .page-header {
                background-attachment: scroll;
            }
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient( to right, rgba(26, 43, 109, 0.80) 0%, rgba(26, 43, 109, 0.40) 60%, rgba(26, 43, 109, 0.15) 100%);
            z-index: 1;
        }

        .page-header .container {
            position: relative;
            z-index: 2;
            max-width: 1100px;
        }

        .page-header h1 {
            font-family: 'Raleway', sans-serif;
            font-size: clamp(2.2rem, 6vw, 3.4rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 12px rgba(0,0,0,0.5);
        }

        .page-header p {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.95);
            max-width: 700px;
            margin: 0 auto 32px;
            line-height: 1.7;
            text-shadow: 0 1px 6px rgba(0,0,0,0.4);
        }

        /* Filter and Search */
        .filter-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }

        .filter-group label {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 6px;
            display: block;
        }
   
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #f5a623;
            box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, #f5a623 0%, #e89600 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-end;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 166, 35, 0.3);
        }

        /* Activities Grid */
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .activity-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #f5a623;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .activity-card{
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .activity-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(0,0,0,0.10);
            border-left-color: #e89600;
        }

        .activity-header {
            padding: 20px;
            background: linear-gradient(135deg, rgba(245, 166, 35, 0.05) 0%, rgba(232, 150, 0, 0.05) 100%);
            border-bottom: 1px solid #f0f0f0;
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        .activity-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0 0 8px 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .activity-org {
            font-size: 0.9rem;
            color: #f5a623;
            font-weight: 600;
        }

        .activity-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .activity-meta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .meta-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.95rem;
            color: #555;
        }

        .meta-item i {
            color: #f5a623;
            width: 18px;
            text-align: center;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .activity-description {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 16px;
            flex-grow: 1;
        }

        .activity-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #16a34a;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
            width: fit-content
           
        }

        .activity-footer {
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 10px;
        }

        .btn-view {
            flex: 1;
            background: linear-gradient(135deg, #f5a623 0%, #e89600 100%);
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: fit-content;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 16px 0 32px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #9a9a9a;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 1.9rem;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1;
        }

        .tabs-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 999px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #555;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .tab-btn:hover {
            border-color: #f5a623;
            color: #e89600;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #f5a623 0%, #e89600 100%);
            border-color: transparent;
            color: white;
            box-shadow: 0 4px 12px rgba(245, 166, 35, 0.3);
        }

        .tab-panel {
            min-height: 80px;
        }

        .tab-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 0.95rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .hidden {
            display: none;
        }

        .activity-image-wrap {
            position: relative;
        }

        .activity-cover {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .photo-count {
            position: absolute;
            right: 12px;
            bottom: 12px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .activity-card.completed-card {
            overflow: hidden;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }

        .gallery-grid img {
            width: 100%;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
        }

        .lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1050;
        }

        .lightbox.active {
            display: flex;
        }

        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 16px;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 166, 35, 0.3);
            text-decoration: none;
            color: white;
        }

        .detail-header {
            position: relative;
            background: linear-gradient(135deg, #f5a623 0%, #e89600 100%);
            color: white;
            padding: 32px 28px 28px;
            border-radius: 20px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .detail-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.24), transparent 35%);
            opacity: 0.35;
            pointer-events: none;
        }

        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.82rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 14px;
            position: relative;
            z-index: 2;
        }

        .detail-title {
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 800;
            margin: 0;
            line-height: 1.05;
            position: relative;
            z-index: 2;
        }

        .detail-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
            position: relative;
            z-index: 2;
        }

        .detail-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            color: white;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.24);
        }

        .detail-badge.badge-approved {
            background: #0d7a42;
            color: #f8fdf7;
        }

        .detail-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }

        .detail-card {
            background: #f5f3ec;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            min-height: 130px;
        }

        .detail-card h6 {
            margin: 0 0 10px;
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            font-weight: 700;
            text-transform: uppercase;
            color: #b98106;
        }

        .detail-card p {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.4;
        }

        .detail-description {
            background: #ffffff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            color: #334155;
            line-height: 1.8;
            font-size: 0.95rem;
            margin-bottom: 0;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .modal-content {
            border-radius: 24px;
            overflow: hidden;
        }

        .btn-close-white {
            filter: invert(1) brightness(1.2);
        }

        @media (max-width: 767px) {
            .detail-row {
                grid-template-columns: 1fr;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            margin: 40px 0;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h3 {
            color: #1a1a2e;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 0;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 40px;
        }

        .pagination .page-link {
            color: #f5a623;
            border-color: #ddd;
        }

        .pagination .page-link:hover {
            background: #f5a623;
            color: white;
        }

        .pagination .page-item.active .page-link {
            background: #f5a623;
            border-color: #f5a623;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #c97a00 0%, #a86200 100%);
            color: white;
            padding: 40px 0 28px;
            margin-top: 60px;
            text-align: center;
            display: flex;
            justify-content: center;
        }

        .footer p {
            margin-bottom: 0;
            opacity: 0.9;
        }
        /* Focus Styles */ 
        .btn-view:focus, .btn-filter:focus { 
         outline: 3px solid rgba(245, 166, 35, 0.5); outline-offset: 2px;
         }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .activities-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .filter-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
              <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                  <img src="{{ asset('images/corms-logo.png.jpg') }}" alt="CORMS" style="height:26px;width:26px;object-fit:cover;border-radius:50%;margin-right:8px;">
                  <span style="color: #f5a623; font-weight:800;">CORMS</span>
              </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('public.activities') }}">Activities</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-login ms-3" href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="btn btn-login ms-3" href="{{ route('login') }}">Login</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    {{-- Page Header --}}
    <div class="page-header">
        <div class="container">
         
            <h1>Activities</h1>
            <p>Discover all approved student organization activities</p>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="container">
        {{-- Filter Section --}}
        <div class="filter-section">
            <form method="GET" action="{{ route('public.activities') }}" class="filter-group">
                <div>
                    <label for="search">Search Activities</label>
                    <input type="text" id="search" name="search" placeholder="Search by title or organization..." value="{{ request('search') }}">
                </div>
                <div>
                    <label for="organization">Organization</label>
                    <select id="organization" name="organization">
                        <option value="">All Organizations</option>
                        @forelse($organizations as $org)
                            <option value="{{ $org->id }}" {{ request('organization') === (string)$org->id ? 'selected' : '' }}>
                                {{ $org->org_name ?? $org->name }}
                            </option>
                        @empty
                            <option disabled>No organizations found</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label for="school_year">School Year</label>
                    <select id="school_year" name="school_year">
                        <option value="">All School Years</option>
                        @forelse($schoolYears as $year)
                            <option value="{{ $year }}" {{ request('school_year') === $year ? 'selected' : '' }}>{{ $year }}</option>
                        @empty
                        @endforelse
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>

        {{-- Activities Stats and Tabs --}}
        <div class="stats-row">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Activities</div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Organizations</div>
                    <div class="stat-value">{{ $stats['organizations'] }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">School Years</div>
                    <div class="stat-value">{{ $stats['school_years'] }}</div>
                </div>
            </div>
        </div>

        <div class="tabs-row">
            <button type="button" class="tab-btn active" data-tab="completed">
                Completed Activities ({{ $completed->count() }})
            </button>
            <button type="button" class="tab-btn" data-tab="ongoing">
                Ongoing Activities ({{ $ongoing->count() }})
            </button>
            <button type="button" class="tab-btn" data-tab="upcoming">
                Upcoming Activities ({{ $upcoming->count() }})
            </button>
        </div>

        <div class="tab-panel" data-panel="completed">
            @if($completed->count())
                <div class="activities-grid">
                    @foreach($completed as $activity)
                        <div class="activity-card completed-card">
                            @if($activity->report && $activity->report->photos->count())
                                <div class="activity-image-wrap">
                                    <img src="{{ Storage::disk('public')->url($activity->report->photos->first()->path) }}" alt="{{ $activity->title }}" class="activity-cover">
                                    @if($activity->report->photos->count() > 1)
                                        <span class="photo-count">+{{ $activity->report->photos->count() - 1 }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="activity-body">
                                <div>
                                    <h3 class="activity-title">{{ $activity->title }}</h3>
                                    <p class="activity-org">{{ $activity->user?->org_name ?? $activity->user?->name ?? 'General' }}</p>
                                </div>
                                <span class="activity-status status-completed"><i class="fas fa-check"></i> Completed</span>
                                <div class="activity-meta">
                                    <div class="meta-item"><i class="fas fa-calendar"></i><span>{{ $activity->date->format('M d, Y') }}</span></div>
                                    <div class="meta-item"><i class="fas fa-map-marker-alt"></i><span>{{ $activity->venue ?? 'Location TBA' }}</span></div>
                                </div>
                                <p class="activity-description">{{ Str::limit($activity->description, 120) }}</p>
                            </div>
                            <div class="activity-footer">
                                <button class="btn-view" onclick="showActivityDetails(this)"
                                    data-title="{{ $activity->title }}"
                                    data-organization="{{ $activity->user?->org_name ?? $activity->user?->name ?? 'General' }}"
                                    data-date="{{ $activity->date->format('M d, Y') }}"
                                    data-venue="{{ $activity->venue ?? 'Location TBA' }}"
                                    data-category="{{ $activity->category ?? 'N/A' }}"
                                    data-participants="{{ $activity->participants_count ?? '0' }}"
                                    data-basis="{{ $activity->basis_grading ?? 'N/A' }}"
                                    data-term="{{ $activity->term ?? 'N/A' }}"
                                    data-sy="{{ $activity->school_year ?? 'N/A' }}"
                                    data-description="{{ $activity->description ?? '' }}"
                                    data-photos='@json($activity->report?->photos->map(fn($photo) => ['url' => Storage::disk('public')->url($photo->path), 'caption' => $photo->caption])->all())'>
                                    <i class="fas fa-eye"></i> View Highlights
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="tab-empty">No completed activities right now.</div>
            @endif
        </div>

        <div class="tab-panel hidden" data-panel="ongoing">
            @if($ongoing->count())
                <div class="activities-grid">
                    @foreach($ongoing as $activity)
                        <div class="activity-card">
                            <div class="activity-header">
                                <h3 class="activity-title">{{ $activity->title }}</h3>
                                <p class="activity-org">{{ $activity->user?->org_name ?? $activity->user?->name ?? 'General' }}</p>
                            </div>
                            <div class="activity-body">
                                <span class="activity-status status-ongoing"><i class="fas fa-hourglass-half"></i> Ongoing</span>
                                <div class="activity-meta">
                                    <div class="meta-item"><i class="fas fa-calendar"></i><span>{{ $activity->date->format('M d, Y') }}</span></div>
                                    <div class="meta-item"><i class="fas fa-map-marker-alt"></i><span>{{ $activity->venue ?? 'Location TBA' }}</span></div>
                                </div>
                                <p class="activity-description">{{ Str::limit($activity->description, 120) }}</p>
                            </div>
                            <div class="activity-footer">
                                <button class="btn-view" onclick="showActivityDetails(this)"
                                    data-title="{{ $activity->title }}"
                                    data-organization="{{ $activity->user?->org_name ?? $activity->user?->name ?? 'General' }}"
                                    data-date="{{ $activity->date->format('M d, Y') }}"
                                    data-venue="{{ $activity->venue ?? 'Location TBA' }}"
                                    data-category="{{ $activity->category ?? 'N/A' }}"
                                    data-participants="{{ $activity->participants_count ?? '0' }}"
                                    data-basis="{{ $activity->basis_grading ?? 'N/A' }}"
                                    data-term="{{ $activity->term ?? 'N/A' }}"
                                    data-sy="{{ $activity->school_year ?? 'N/A' }}"
                                    data-description="{{ $activity->description ?? '' }}"
                                    data-photos='[]'>
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="tab-empty">No ongoing activities right now.</div>
            @endif
        </div>

        <div class="tab-panel hidden" data-panel="upcoming">
            @if($upcoming->count())
                <div class="activities-grid">
                    @foreach($upcoming as $activity)
                        <div class="activity-card">
                            <div class="activity-header">
                                <h3 class="activity-title">{{ $activity->title }}</h3>
                                <p class="activity-org">{{ $activity->user?->org_name ?? $activity->user?->name ?? 'General' }}</p>
                            </div>
                            <div class="activity-body">
                                <span class="activity-status status-upcoming"><i class="fas fa-calendar-alt"></i> Upcoming</span>
                                <div class="activity-meta">
                                    <div class="meta-item"><i class="fas fa-calendar"></i><span>{{ $activity->date->format('M d, Y') }}</span></div>
                                    <div class="meta-item"><i class="fas fa-map-marker-alt"></i><span>{{ $activity->venue ?? 'Location TBA' }}</span></div>
                                </div>
                                <p class="activity-description">{{ Str::limit($activity->description, 120) }}</p>
                            </div>
                            <div class="activity-footer">
                                <button class="btn-view" onclick="showActivityDetails(this)"
                                    data-title="{{ $activity->title }}"
                                    data-organization="{{ $activity->user?->org_name ?? $activity->user?->name ?? 'General' }}"
                                    data-date="{{ $activity->date->format('M d, Y') }}"
                                    data-venue="{{ $activity->venue ?? 'Location TBA' }}"
                                    data-category="{{ $activity->category ?? 'N/A' }}"
                                    data-participants="{{ $activity->participants_count ?? '0' }}"
                                    data-basis="{{ $activity->basis_grading ?? 'N/A' }}"
                                    data-term="{{ $activity->term ?? 'N/A' }}"
                                    data-sy="{{ $activity->school_year ?? 'N/A' }}"
                                    data-description="{{ $activity->description ?? '' }}"
                                    data-photos='[]'>
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="tab-empty">No upcoming activities right now.</div>
            @endif
        </div>
    </div>

    {{-- Activity Details Modal --}}
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="detail-header">
                        <div class="detail-meta">
                            <span id="detailOrg"></span>
                            <span>·</span>
                            <span id="detailCategoryMeta"></span>
                        </div>
                        <h3 class="detail-title" id="detailTitle"></h3>
                        <div class="detail-badges">
                            <span id="detailStatusBadge" class="detail-badge badge-approved"></span>
                            <span id="detailTermBadge" class="detail-badge"></span>
                            <span id="detailSYBadge" class="detail-badge"></span>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-card">
                            <h6>Date & Time</h6>
                            <p id="detailDate"></p>
                        </div>
                        <div class="detail-card">
                            <h6>Venue</h6>
                            <p id="detailVenue"></p>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-card">
                            <h6>Participants</h6>
                            <p id="detailParticipants"></p>
                        </div>
                        <div class="detail-card">
                            <h6>Basis for Grading</h6>
                            <p id="detailBasis"></p>
                        </div>
                    </div>

                    <div id="descriptionSection" style="display: none;">
                        <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Description</h6>
                        <p id="detailDescription" class="detail-description"></p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} Campus Student Organization Activities Tracking System. All Rights Reserved.</p>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showActivityDetails(button) {
            const title = button.getAttribute('data-title');
            const organization = button.getAttribute('data-organization');
            const date = button.getAttribute('data-date');
            const venue = button.getAttribute('data-venue');
            const category = button.getAttribute('data-category');
            const participants = button.getAttribute('data-participants');
            const basis = button.getAttribute('data-basis');
            const term = button.getAttribute('data-term');
            const sy = button.getAttribute('data-sy');
            const description = button.getAttribute('data-description');
            const photos = JSON.parse(button.getAttribute('data-photos') || '[]');

            document.getElementById('detailTitle').textContent = title;
            document.getElementById('detailOrg').textContent = organization;
            document.getElementById('detailCategoryMeta').textContent = category;
            document.getElementById('detailDate').textContent = date;
            document.getElementById('detailVenue').textContent = venue;
            document.getElementById('detailParticipants').textContent = participants;
            document.getElementById('detailBasis').textContent = basis;
            document.getElementById('detailTermBadge').textContent = term;
            document.getElementById('detailSYBadge').textContent = sy;
            document.getElementById('detailStatusBadge').textContent = button.closest('.activity-card.completed-card') ? 'Completed' : button.closest('.activity-card')?.querySelector('.status-ongoing') ? 'Ongoing' : 'Upcoming';

            const descriptionSection = document.getElementById('descriptionSection');
            if (description && description.trim()) {
                descriptionSection.style.display = 'block';
                document.getElementById('detailDescription').textContent = description;
            } else {
                descriptionSection.style.display = 'none';
            }

            const gallery = document.getElementById('detailGallery');
            if (gallery) {
                gallery.innerHTML = '';
                if (photos.length) {
                    photos.forEach(photo => {
                        const image = document.createElement('img');
                        image.src = photo.url;
                        image.alt = photo.caption || title;
                        image.addEventListener('click', () => openLightbox(photo.url));
                        gallery.appendChild(image);
                    });
                }
            }

            const modal = new bootstrap.Modal(document.getElementById('activityModal'));
            modal.show();
        }

        function openLightbox(url) {
            const lightbox = document.getElementById('lightbox');
            const lightboxImage = document.getElementById('lightboxImage');
            lightboxImage.src = url;
            lightbox.classList.add('active');
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.getElementById('lightboxImage').src = '';
        }

        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.add('hidden'));
                button.classList.add('active');
                document.querySelector(`.tab-panel[data-panel="${button.getAttribute('data-tab')}"]`).classList.remove('hidden');
            });
        });
    </script>

    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <div class="lightbox-close">&times;</div>
        <img id="lightboxImage" src="" alt="Activity Photo">
    </div>
</body>
</html>
