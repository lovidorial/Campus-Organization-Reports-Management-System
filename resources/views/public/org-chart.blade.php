<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Org Chart | Orgtrack</title>

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8f9fc 0%, #eff2f7 100%);
            color: #1a1a2e;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(221, 225, 235, 0.3);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 12px 0 !important;
        }

        .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Sora', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: #1a2b6d !important;
            letter-spacing: 0.14em;
            margin-right: 24px;
            text-decoration: none;
        }

        .navbar-brand img {
            height: 48px;
            width: 72px;
            object-fit: contain;
            filter: drop-shadow(0 2px 3px rgba(219, 116, 12, 0.2));
        }

        .navbar-brand-wordmark {
            text-transform: uppercase;
            line-height: 1;
            background: linear-gradient(180deg, #ffc04d 0%, #ff8a1f 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
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

        .page-header {
            position: relative;
            min-height: 300px;
            overflow: hidden;
            padding: 100px 48px 80px;
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

        .page-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(26, 43, 109, 0.80) 0%, rgba(26, 43, 109, 0.40) 60%, rgba(26, 43, 109, 0.15) 100%);
            z-index: 1;
        }

        .page-header .container {
            position: relative;
            z-index: 2;
            max-width: 1100px;
        }

        .page-header h1 {
            font-family: 'Sora', sans-serif;
            font-size: clamp(2.2rem, 6vw, 3.4rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 12px rgba(0,0,0,0.5);
        }

        .page-header p {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.95);
            max-width: 700px;
            margin: 0 auto;
        }

        .filter-card {
            background: white;
            padding: 1.25rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            margin-bottom: 2rem;
        }

        .org-card {
            background: #fff;
            border: 1px solid #edf1f7;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .org-card .accordion-button {
            background: linear-gradient(135deg, #f8faff 0%, #eef4ff 100%);
            color: #1a2b6d;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1.15rem 1.25rem;
        }

        .org-card .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #f0f7ff 0%, #e8f0ff 100%);
            color: #1a2b6d;
            box-shadow: none;
        }

        .org-card .accordion-collapse {
            border-top: 1px solid #edf1f7;
        }

        .member-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1rem;
            min-height: 100%;
            box-shadow: 0 5px 15px rgba(15,23,42,0.03);
        }

        .member-photo {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15);
            background: #eef2ff;
        }

        .member-fallback {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1d4ed8, #7c3aed);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(124, 58, 237, 0.2);
        }

        .member-name {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.15rem;
        }

        .member-position {
            color: #475569;
            font-size: 0.82rem;
            margin-bottom: 0rem;
        }

        .member-meta {
            margin-top: 0.9rem;
            font-size: 0.9rem;
            color: #475569;
        }

        .member-meta span {
            font-weight: 600;
            color: #1e293b;
        }

        .facebook-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #0f6ee8;
            text-decoration: none;
            font-weight: 600;
        }

        .facebook-link:hover {
            text-decoration: underline;
            color: #0a4fb3;
        }

        .no-members {
            padding: 1.5rem;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            margin: 0.5rem 0 1rem;
        }

        @media (max-width: 768px) {
            .page-header { background-attachment: scroll; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="Orgtrack logo">
                <span class="navbar-brand-wordmark">Orgtrack</span>
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
                            <a class="btn btn-login ms-3" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
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

    <div class="page-header">
        <div class="container">
            <h1>Org Chart</h1>
            <p>Meet the student leaders and officers behind each active campus organization.</p>
        </div>
    </div>

    <main class="container pb-5">
        <div class="filter-card">
            <form method="GET" action="{{ route('public.orgchart') }}" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label for="organization" class="form-label fw-semibold text-dark">Organization</label>
                    <select id="organization" name="organization" class="form-select">
                        <option value="">All Organizations</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" {{ (string) $selectedOrganization === (string) $organization->id ? 'selected' : '' }}>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        @forelse($organizations as $organization)
            <div class="accordion org-card" id="org-accordion-{{ $organization->id }}">
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $selectedOrganization && (string) $organization->id === (string) $selectedOrganization ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#org-collapse-{{ $organization->id }}" aria-expanded="{{ $selectedOrganization && (string) $organization->id === (string) $selectedOrganization ? 'true' : 'false' }}">
                            {{ $organization->name }}
                            <span class="badge bg-light text-dark ms-2">{{ $organization->organizationMembers->count() }} members</span>
                        </button>
                    </h2>
                    <div id="org-collapse-{{ $organization->id }}" class="accordion-collapse collapse {{ $selectedOrganization && (string) $organization->id === (string) $selectedOrganization ? 'show' : '' }}" data-bs-parent="#org-accordion-{{ $organization->id }}">
                        <div class="accordion-body">
                            @if($organization->organizationMembers->isNotEmpty())
                                <div class="row g-4">
                                    @foreach($organization->organizationMembers as $member)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="member-card h-100">
                                                <div class="d-flex align-items-center gap-3">
                                                    @if($member->photo_path)
                                                        <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->name }}" class="member-photo">
                                                    @else
                                                        <div class="member-fallback">
                                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="member-name">{{ $member->name }}</div>
                                                        <div class="member-position">{{ $member->position }}</div>
                                                    </div>
                                                </div>

                                                <div class="member-meta">
                                                    @if($member->year_level)
                                                        <div><span>Year:</span> {{ $member->year_level }}</div>
                                                    @endif
                                                    @if($member->facebook_url)
                                                        <div class="mt-2">
                                                            <a href="{{ $member->facebook_url }}" target="_blank" rel="noopener noreferrer" class="facebook-link">
                                                                <i class="fab fa-facebook-f"></i>
                                                                Facebook
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="no-members">No org chart members yet.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-light border text-center py-4">
                No active organizations found.
            </div>
        @endforelse
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
