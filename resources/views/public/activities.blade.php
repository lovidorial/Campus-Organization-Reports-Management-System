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

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fc 0%, #eff2f7 100%);
            min-height: 100vh;
            color: #1a1a2e;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #f5a623 0%, #e89600 100%);
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            padding: 16px 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: white !important;
            letter-spacing: -0.5px;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin: 0 12px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;    
            transform: translateY(-2px);
        }

        .btn-login {
            background: black;
            color: #e89600;
            font-weight: 700;
            padding: 8px 24px;
            border-radius: 2px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #df810f;
            transform: translateY(-2px);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(160deg, #f5a623 0%, #e97a00 100%);
            color: white;
            padding: 60px 0;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            margin-bottom: 40px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.95;
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
            font-size: 0.95rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 16px;
            flex-grow: 1;
        }

        .activity-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1e8e3e;
            color: white;
            padding: 5px 14px;
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
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 166, 35, 0.3);
            text-decoration: none;
            color: white;
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
                grid-template-columns: 1fr;
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
            <a class="navbar-brand" href="{{ url('/') }}">
                 CORMS
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
                            <a class="nav-link btn btn-login ms-3" href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link btn btn-login ms-3" href="{{ route('login') }}">Login</a>
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

        {{-- Activities Grid --}}
        @if($activities->count() > 0)
            <div class="activities-grid">
                @forelse($activities as $activity)
                    <div class="activity-card">
                        <div class="activity-header">
                            <h3 class="activity-title">{{ $activity->title }}</h3>
                            <p class="activity-org">
                                <i class="fas fa-building"></i> 
                                @if($activity->user)
                                    {{ $activity->user->org_name ?? $activity->user->name }}
                                @else
                                    {{ $activity->organization ?? 'General' }}
                                @endif
                            </p>
                        </div>
                        <div class="activity-body">
                            <span class="activity-status">
                                <i class="fas fa-check-circle"></i> Approved
                            </span>
                            <div class="activity-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>{{ $activity->date->format('M d, Y') }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>{{ $activity->venue ?? 'Location TBA' }}</span>
                                </div>
                            </div>

                            @if($activity->description)
                                <p class="activity-description">{{ Str::limit($activity->description, 120) }}</p>
                            @endif
                        </div>
                        <div class="activity-footer">
                            <button class="btn-view" onclick="showActivityDetails(this)" 
                                data-id="{{ $activity->id }}"
                                data-title="{{ $activity->title }}"
                                data-organization="{{ $activity->user ? ($activity->user->org_name ?? $activity->user->name) : ($activity->organization ?? 'General') }}"
                                data-date="{{ $activity->date->format('M d, Y') }}"
                                data-venue="{{ $activity->venue ?? 'Location TBA' }}"
                                data-category="{{ $activity->category ?? 'N/A' }}"
                                data-participants="{{ $activity->participants_count ?? '0' }}"
                                data-basis="{{ $activity->basis_grading ?? 'N/A' }}"
                                data-term="{{ $activity->term ?? 'N/A' }}"
                                data-sy="{{ $activity->school_year ?? 'N/A' }}"
                                data-description="{{ $activity->description ?? '' }}">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Activities Found</h3>
                        <p>There are no approved activities to display at the moment.</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($activities->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $activities->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Activities Found</h3>
                <p>There are no approved activities to display at the moment.</p>
            </div>
        @endif
    </div>

    {{-- Activity Details Modal --}}
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="modalTitle">Activity Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div style="background: linear-gradient(135deg, #f5a623 0%, #e89600 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 id="detailTitle" style="margin: 0 0 10px 0; font-size: 1.5rem; font-weight: 700;"></h3>
                        <p id="detailOrg" style="margin: 0; font-size: 0.95rem; opacity: 0.9;"><i class="fas fa-building"></i></p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Activity Date</h6>
                            <p id="detailDate" style="margin: 0; color: #333; font-weight: 600;"><i class="fas fa-calendar"></i></p>
                        </div>
                        <div>
                            <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Venue</h6>
                            <p id="detailVenue" style="margin: 0; color: #333; font-weight: 600;"><i class="fas fa-map-marker-alt"></i></p>
                        </div>
                        <div>
                            <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Category</h6>
                            <p id="detailCategory" style="margin: 0; color: #333; font-weight: 600;"></p>
                        </div>
                        <div>
                            <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Participants</h6>
                            <p id="detailParticipants" style="margin: 0; color: #333; font-weight: 600;"><i class="fas fa-users"></i></p>
                        </div>
                        <div>
                            <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Term</h6>
                            <p id="detailTerm" style="margin: 0; color: #333; font-weight: 600;"></p>
                        </div>
                        <div>
                            <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">School Year</h6>
                            <p id="detailSY" style="margin: 0; color: #333; font-weight: 600;"></p>
                        </div>
                        <div>
                            <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Basis for Grading</h6>
                            <p id="detailBasis" style="margin: 0; color: #333; font-weight: 600;"></p>
                        </div>
                    </div>

                    <div id="descriptionSection" style="display: none;">
                        <h6 style="color: #f5a623; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; font-size: 0.75rem;">Description</h6>
                        <p id="detailDescription" style="margin: 0; color: #555; line-height: 1.6;"></p>
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
            // Get all data attributes from the button
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

            // Populate modal elements
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('detailTitle').textContent = title;
            document.getElementById('detailOrg').innerHTML = `<i class="fas fa-building"></i> ${organization}`;
            document.getElementById('detailDate').innerHTML = `<i class="fas fa-calendar"></i> ${date}`;
            document.getElementById('detailVenue').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${venue}`;
            document.getElementById('detailCategory').textContent = category;
            document.getElementById('detailParticipants').innerHTML = `<i class="fas fa-users"></i> ${participants}`;
            document.getElementById('detailTerm').textContent = term;
            document.getElementById('detailSY').textContent = sy;
            document.getElementById('detailBasis').textContent = basis;

            // Show description section only if description exists
            const descriptionSection = document.getElementById('descriptionSection');
            if (description && description.trim()) {
                descriptionSection.style.display = 'block';
                document.getElementById('detailDescription').textContent = description;
            } else {
                descriptionSection.style.display = 'none';
            }

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('activityModal'));
            modal.show();
        }
    </script>
</body>
</html>
