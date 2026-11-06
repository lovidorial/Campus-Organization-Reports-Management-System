<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrgTrack | Campus Student Organization Narrative Accomplishment and Summary Reports</title>
    <meta name="description" content="OrgTrack is CSU Aparri's platform for student organization GPOA submissions, activity monitoring, communication letters, narrative reports, and OSDW review.">
    <meta property="og:title" content="OrgTrack | CSU Aparri Student Organization Reports">
    <meta property="og:description" content="Plan, submit, and track student organization activities with OrgTrack at Cagayan State University - Aparri.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/hero-bg.jpg') }}">
    <link rel="preload" as="image" href="{{ asset('images/hero-bg.jpg') }}" fetchpriority="high">

    {{-- Google Fonts: Sora + Inter --}}
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('css/welcomeblade.css') }}">
</head>
<body>

    {{-- ========================================================
         NAVBAR  —  header navigation layout update
    ======================================================== --}}
    <nav class="site-navbar" aria-label="Main navigation" x-data="{ mobileMenuOpen: false }">
        <div class="navbar-inner">
            <a href="{{ url('/') }}" class="nav-brand">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="OrgTrack logo" class="nav-logo-img">
                <span class="nav-brand-text">OrgTrack</span>
            </a>

            <ul class="nav-links">
                <li><a href="#top" class="nav-link is-active">Home</a></li>
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                <li><a href="#organizations" class="nav-link">Organization Chart</a></li>
            </ul>

            <div class="nav-auth">
                <button type="button"
                        class="hamburger"
                        aria-label="Toggle navigation"
                        :aria-expanded="mobileMenuOpen"
                        @click="mobileMenuOpen = !mobileMenuOpen">
                    <span :class="{ 'is-open': mobileMenuOpen }"></span>
                    <span :class="{ 'is-open': mobileMenuOpen }"></span>
                    <span :class="{ 'is-open': mobileMenuOpen }"></span>
                </button>

                @guest
                    <a href="{{ route('login') }}" class="nav-auth-link">Login</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="nav-auth-link">Dashboard</a>
                @endguest
            </div>
        </div>

        <div class="mobile-nav-panel"
             x-show="mobileMenuOpen"
             x-transition:enter="mobile-nav-enter"
             x-transition:enter-start="mobile-nav-enter-start"
             x-transition:enter-end="mobile-nav-enter-end"
             x-transition:leave="mobile-nav-leave"
             x-transition:leave-start="mobile-nav-leave-start"
             x-transition:leave-end="mobile-nav-leave-end"
             @click.outside="mobileMenuOpen = false"
             x-cloak
             style="display: none;">
            <div class="mobile-nav-header">
                <span>Menu</span>
                <button type="button" class="mobile-nav-close" aria-label="Close menu" @click="mobileMenuOpen = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <ul class="mobile-nav-links">
                <li class="mobile-nav-item"><a href="#top" class="mobile-nav-link" @click="mobileMenuOpen = false">Home</a></li>
                <li class="mobile-nav-item"><a href="#features" class="mobile-nav-link" @click="mobileMenuOpen = false">Features</a></li>
                <li class="mobile-nav-item"><a href="#about" class="mobile-nav-link" @click="mobileMenuOpen = false">About</a></li>
                <li class="mobile-nav-item"><a href="#how-it-works" class="mobile-nav-link" @click="mobileMenuOpen = false">How It Works</a></li>
                <li class="mobile-nav-item"><a href="#organizations" class="mobile-nav-link" @click="mobileMenuOpen = false">Organization Chart</a></li>
                <li class="mobile-nav-item mobile-auth-item">
                    @guest
                        <a href="{{ route('login') }}" class="nav-auth-link mobile-auth-link" @click="mobileMenuOpen = false">Login</a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="nav-auth-link mobile-auth-link" @click="mobileMenuOpen = false">Dashboard</a>
                    @endguest
                </li>
            </ul>
        </div>
    </nav>

    {{-- ========================================================
         HERO — heading
    ======================================================== --}}
    <section id="top" class="hero hero-with-bg">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-heading">Manage Student Organizations.<br>Simplify Every Activity.</h1>
                <p class="hero-sub">OrgTrack provides a centralized platform for student organizations to manage their GPOA, monitor planned activities, submit communication letters, and track narrative report submissions.</p>
                <div class="hero-btns">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary-cta">
                            <i class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i> Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary-cta">
                            <i class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i> Get Started
                        </a>
                    @endauth
                    <a href="#how-it-works" class="btn-outline-cta">
                        <i class="fas fa-compass" aria-hidden="true"></i> How It Works
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Flash messages --}}
    <div class="container mt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    {{-- ========================================================
         BENEFITS SECTION
    ======================================================== --}}
    <section id="features" class="section-block section-benefits">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Use OrgTrack?</h2>
                <p class="section-subtitle">Everything student organizations need to monitor and manage their activities in one centralized platform.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-file-arrow-up"></i></div>
                        <h5 class="benefit-title">Easy Submission</h5>
                        <p class="benefit-text">Submit GPOA, activity requests, communication letters, and narrative reports through organized digital workflows.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-chart-line"></i></div>
                        <h5 class="benefit-title">Real-Time Monitoring</h5>
                        <p class="benefit-text">Monitor activity implementation, submission status, and report progress.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved fa-shield-alt"></i></div>
                        <h5 class="benefit-title">Secure Document Management</h5>
                        <p class="benefit-text">Keep important organizational documents protected and accessible through authorized application features.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-building-columns"></i></div>
                        <h5 class="benefit-title">Centralized Organization Records</h5>
                        <p class="benefit-text">Keep organizational information, officer records, and activity history organized.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-clipboard-check"></i></div>
                        <h5 class="benefit-title">Transparent Monitoring</h5>
                        <p class="benefit-text">Track document submissions and administrative feedback through clear status information.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-file-arrow-down"></i></div>
                        <h5 class="benefit-title">Reporting and Analytics</h5>
                        <p class="benefit-text">Provide organized activity summaries and monitoring reports for OSDW.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="section-block about-orgtrack-section">
        <div class="container">
            <div class="about-orgtrack-layout">
                <div class="about-orgtrack-copy">
                    <p class="about-eyebrow">CSU Aparri · OSDW</p>
                    <h2 class="section-title">About OrgTrack</h2>
                    <p class="about-orgtrack-description">OrgTrack is a centralized monitoring and tracking platform developed to support the Office of Student Development and Welfare in monitoring student organization activities and documentation. It provides a structured environment for managing General Plans of Action, activity requests, communication letters, narrative reports, and organizational monitoring records.</p>
                </div>
                <div class="about-highlights" aria-label="OrgTrack principles">
                    <article class="about-highlight">
                        <span class="about-highlight-icon" aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                        <div><h3>Purpose</h3><p>Improve the monitoring of student organization activities.</p></div>
                    </article>
                    <article class="about-highlight">
                        <span class="about-highlight-icon" aria-hidden="true"><i class="fa-solid fa-list-check"></i></span>
                        <div><h3>Transparency</h3><p>Provide accessible submission and monitoring information.</p></div>
                    </article>
                    <article class="about-highlight">
                        <span class="about-highlight-icon" aria-hidden="true"><i class="fa-solid fa-folder-open"></i></span>
                        <div><h3>Organization</h3><p>Centralize important organizational records and reports.</p></div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="section-block section-light how-it-works-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">How OrgTrack Works</h2>
                <p class="section-subtitle">From GPOA submission to activity monitoring and reporting.</p>
            </div>
            <div class="workflow-steps">
                <article class="workflow-step">
                    <span class="workflow-number">01</span>
                    <h3>Set Document Deadlines</h3>
                    <p>OSDW establishes submission deadlines for GPOA, activity requests, and narrative reports.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">02</span>
                    <h3>Submit GPOA</h3>
                    <p>Organizations submit their GPOA PDF and encode or import their planned activities.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">03</span>
                    <h3>Submit Activity Request</h3>
                    <p>Organizations provide activity schedules, venues, objectives, budgets, SDGs, program flow, and signed communication letters.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">04</span>
                    <h3>Conduct Activities</h3>
                    <p>Organizations implement their planned activities and document their implementation.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">05</span>
                    <h3>Submit Narrative Report</h3>
                    <p>Organizations submit narrative reports, supporting photographs, and optional attendance sheets.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">06</span>
                    <h3>OSDW Monitoring and Assessment</h3>
                    <p>OSDW reviews narrative reports, requests revisions when necessary, and records activity compliance results.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="organizations" class="section-block section-light organization-directory-section" data-org-directory>
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Recognized Student Organizations</h2>
                <p class="section-subtitle">Explore the student organizations of Cagayan State University–Aparri Campus.</p>
            </div>
            <div class="directory-tools" role="search">
                <label class="directory-search">
                    <span class="visually-hidden">Search organizations by name</span>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="organizationSearch" placeholder="Search organizations" autocomplete="off">
                </label>
                <label class="directory-filter">
                    <span class="visually-hidden">Filter organizations by category</span>
                    <select id="organizationCategory">
                        <option value="all">All categories</option>
                        <option value="major">Major Organizations</option>
                        <option value="minor">Minor Organizations</option>
                        <option value="specialized">Specialized Organizations</option>
                    </select>
                </label>
                <p class="directory-count" id="organizationCount" aria-live="polite">{{ $organizations->count() }} organization{{ $organizations->count() === 1 ? '' : 's' }}</p>
            </div>

            <div class="organization-directory-grid" id="organizationDirectoryGrid">
                @forelse($organizations as $organization)
                    @php
                        $organizationCategory = match ($organization->type) {
                            'Major Student Organization' => 'major',
                            'Minor Student Organization' => 'minor',
                            'Specialized Student Organization' => 'specialized',
                            default => 'other',
                        };
                        $organizationCategoryLabel = match ($organizationCategory) {
                            'major' => 'Major Organization',
                            'minor' => 'Minor Organization',
                            'specialized' => 'Specialized Organization',
                            default => $organization->type ?: 'Student Organization',
                        };
                    @endphp
                    <article class="organization-directory-card" data-organization-card data-org-name="{{ strtolower($organization->name) }}" data-org-category="{{ $organizationCategory }}">
                        <div class="organization-card-topline">
                            @if($organization->logo_url)
                                <img src="{{ $organization->logo_url }}" alt="{{ $organization->name }} logo" class="organization-card-logo" loading="lazy" decoding="async">
                            @else
                                <span class="organization-card-logo organization-card-logo-fallback" aria-hidden="true"><i class="fa-solid fa-people-group"></i></span>
                            @endif
                            <span class="organization-card-category">{{ $organizationCategoryLabel }}</span>
                        </div>
                        <h3>{{ $organization->name }}</h3>
                        <p class="organization-card-college"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>{{ $organization->college ?: 'CSU Aparri' }}</p>
                        <a href="{{ route('public.orgchart', ['organization' => $organization->id]) }}" class="organization-card-link">
                            View organization details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </article>
                @empty
                    <div class="directory-empty-state" id="organizationEmptyState">
                        <i class="fa-solid fa-building-circle-exclamation" aria-hidden="true"></i>
                        <h3>No recognized organizations to display</h3>
                        <p>Organization listings will appear here when active records are available.</p>
                    </div>
                @endforelse
            </div>
            @if($organizations->isNotEmpty())
                <div class="directory-empty-state" id="organizationNoResults" hidden>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <h3>No matching organizations</h3>
                    <p>Try a different search term or category.</p>
                </div>
            @endif
            <div class="directory-footer-action">
                <a href="{{ route('public.orgchart') }}" class="btn-directory-all">Explore the full organization chart <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </section>

    <section id="explore" class="section-block section-explore">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Get Started with OrgTrack</h2>
                <p class="section-subtitle">Discover the platform and access your organization's monitoring records.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feat-card">
                        <h5 class="feat-title">Explore OrgTrack</h5>
                        <p class="feat-desc">Learn how OrgTrack helps student organizations organize their activities and documentation.</p>
                        <a href="#" class="btn-feat btn-feat-teal" data-bs-toggle="modal" data-bs-target="#learnMoreModal">Learn More</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feat-card">
                        <h5 class="feat-title">Access Your Account</h5>
                        <p class="feat-desc">Sign in to manage your organization's GPOA, activity requests, and reports.</p>
                        @guest
                            <a href="{{ route('login') }}" class="btn-feat btn-feat-amber">Sign In</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn-feat btn-feat-amber">Open Dashboard</a>
                        @endguest
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feat-card">
                        <h5 class="feat-title">Track Your Activities</h5>
                        <p class="feat-desc">Access your organization's activity monitoring records and track submission progress.</p>
                        @guest
                            <a href="{{ route('login') }}" class="btn-feat btn-feat-amber">Access OrgTrack</a>
                        @endguest
                        @auth
                            <a href="{{ route('activity-monitor.index') }}" class="btn-feat btn-feat-amber">Access OrgTrack</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================================================
         FOOTER
    ======================================================== --}}
    <footer id="contact" class="site-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-brand"><img src="{{ asset('images/orgTracklogo.png') }}" alt="" loading="lazy"><span>OrgTrack</span></div>
                    <p class="footer-desc">
                        Campus Student Organization Narrative & Summary Reports.<br>
                        A centralized platform for student organization submissions, activity tracking, and OSDW review at CSU Aparri.
                    </p>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <p class="footer-contact-title">Explore</p>
                    <ul class="footer-nav-links">
                        <li><a href="#top" class="footer-link">Home</a></li>
                        <li><a href="#features" class="footer-link">Features</a></li>
                        <li><a href="#about" class="footer-link">About</a></li>
                        <li><a href="#how-it-works" class="footer-link">How It Works</a></li>
                        <li><a href="#organizations" class="footer-link">Organization Chart</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <p class="footer-contact-title">Contact Us</p>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>
                        <a href="mailto:osdwcsuaparri@gmail.com" class="footer-link">osdwcsuaparri@gmail.com</a>
                    </p>
                    <p class="mb-1 footer-campus-name">Cagayan State University–Aparri Campus</p>
                    <p class="mb-0"><i class="fa-brands fa-facebook"></i>
                        <a href="https://www.facebook.com/CSUAparri-OSDW" class="footer-link" target="_blank">CSUAparri-OSDW</a>
                    </p>
                </div>
            </div>
            <hr class="footer-hr">
            <div class="footer-bottom">
                <p class="footer-copy">&copy; {{ date('Y') }} OrgTrack - Campus Student Organization Narrative and Summary Reports. All Rights Reserved.</p>
                <a href="{{ route('terms.accept') }}" class="footer-link">Terms of Use</a>
            </div>
        </div>
    </footer>

    {{-- About OrgTrack Modal --}}
    <div class="modal fade" id="learnMoreModal" tabindex="-1" aria-labelledby="learnMoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="learnMoreModalLabel">About OrgTrack</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body about-modal-body">
                    <div class="about-modal-section">
                        <h6 class="about-section-title">What is OrgTrack?</h6>
                        <p class="about-copy">OrgTrack is a centralized monitoring and tracking platform that supports the Office of Student Development and Welfare (OSDW) at Cagayan State University–Aparri. It helps organize General Plans of Action, planned activities, activity requests, communication letters, narrative reports, and monitoring records.</p>
                    </div>

                    <div class="about-modal-section">
                        <h6 class="about-section-title">Key Features</h6>
                        <ul class="about-feature-list">
                            <li><strong>GPOA and Activities:</strong> Submit a GPOA and encode or import planned activities.</li>
                            <li><strong>Activity Requests:</strong> Record activity details and submit signed communication letters.</li>
                            <li><strong>Narrative Reports:</strong> Submit reports, supporting photographs, and optional attendance sheets.</li>
                            <li><strong>Monitoring:</strong> Track activity implementation, document status, and report progress.</li>
                            <li><strong>OSDW Assessment:</strong> Review reports, request revisions when necessary, and record compliance results.</li>
                            <li><strong>Organized Records:</strong> Access documents through the application's authorized features.</li>
                        </ul>
                    </div>

                    <div class="about-modal-section">
                        <h6 class="about-section-title">Why OrgTrack</h6>
                        <p class="about-copy">OrgTrack provides a structured place for organizations and OSDW to follow activity documentation and monitoring information.</p>
                    </div>

                    <div class="about-modal-section about-team-section">
                        <h6 class="about-section-title">Development Team</h6>
                        <p class="about-team-intro">OrgTrack was developed by student developers of CSU - Aparri.</p>

                        <div class="development-team-grid">
                            <div class="development-team-card">
                                <p class="development-team-role">Project Leader</p>
                                <p class="development-team-name">Unciano Jade</p>
                            </div>
                            <div class="development-team-card">
                                <p class="development-team-role">Developer</p>
                                <p class="development-team-name">Lovidorial Christian Paolo</p>
                            </div>
                            <div class="development-team-card">
                                <p class="development-team-role">UI/UX Designer</p>
                                <p class="development-team-name">Villena Adrian</p>
                            </div>
                        </div>

                        <p class="about-team-credit">
                            &copy; {{ date('Y') }} - Developed with care for campus community
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sectionIds = ['top', 'features', 'about', 'how-it-works', 'organizations'];
            const sections = sectionIds.map((id) => document.getElementById(id)).filter(Boolean);
            const updateActiveLinks = (activeId) => {
                document.querySelectorAll('.nav-link[href^="#"], .mobile-nav-link[href^="#"]').forEach((link) => {
                    link.classList.toggle('is-active', link.getAttribute('href') === `#${activeId}`);
                });
            };

            if (!('IntersectionObserver' in window)) return;

            const observer = new IntersectionObserver((entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

                if (visible) updateActiveLinks(visible.target.id);
            }, {
                rootMargin: '-76px 0px -55% 0px',
                threshold: [0, .15, .35, .6],
            });

            sections.forEach((section) => observer.observe(section));
        });

        document.addEventListener('DOMContentLoaded', function () {
            const search = document.getElementById('organizationSearch');
            const category = document.getElementById('organizationCategory');
            const count = document.getElementById('organizationCount');
            const noResults = document.getElementById('organizationNoResults');
            const cards = [...document.querySelectorAll('[data-organization-card]')];

            const filterOrganizations = () => {
                const query = search.value.trim().toLocaleLowerCase();
                const selectedCategory = category.value;
                let visibleCount = 0;

                cards.forEach((card) => {
                    const matchesName = card.dataset.orgName.includes(query);
                    const matchesCategory = selectedCategory === 'all' || card.dataset.orgCategory === selectedCategory;
                    const visible = matchesName && matchesCategory;
                    card.hidden = !visible;
                    if (visible) visibleCount++;
                });

                count.textContent = `${visibleCount} organization${visibleCount === 1 ? '' : 's'}`;
                noResults.hidden = visibleCount > 0;
            };

            if (search && category && count && noResults) {
                search.addEventListener('input', filterOrganizations);
                category.addEventListener('change', filterOrganizations);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const revealItems = document.querySelectorAll('.benefit-item, .about-highlight, .workflow-step, .organization-directory-card, .feat-card');
            document.documentElement.classList.add('reveal-ready');
            const revealObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.12 });

            revealItems.forEach((item) => revealObserver.observe(item));
        });
    </script>

</body>
</html>