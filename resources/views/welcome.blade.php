<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orgtrack | Campus Student Organization Narrative Accomplishment and Summary Reports</title>

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
    <nav class="site-navbar" x-data="{ mobileMenuOpen: false, orgChartMobileOpen: false }">
        <div class="navbar-inner">
            <a href="{{ url('/') }}" class="nav-brand">
                <img src="{{ asset('images/orgTracklogo.png') }}" alt="Orgtrack logo" class="nav-logo-img">
                <span class="nav-brand-text">Orgtrack</span>
            </a>

            <ul class="nav-links">
                <li><a href="#top" class="nav-link is-active">Home</a></li>
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#learnMoreModal">About</a></li>
                <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Org Chart
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('public.orgchart') }}">View Full Org Chart</a></li>
                        @foreach(\App\Models\Organization::where('is_active', true)->orderBy('name')->get() as $organization)
                            <li><a class="dropdown-item" href="{{ route('public.orgchart', ['organization' => $organization->id]) }}">{{ $organization->name }}</a></li>
                        @endforeach
                    </ul>
                </li>
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
                <li class="mobile-nav-item"><a href="#" class="mobile-nav-link" data-bs-toggle="modal" data-bs-target="#learnMoreModal" @click="mobileMenuOpen = false">About</a></li>
                <li class="mobile-nav-item"><a href="#how-it-works" class="mobile-nav-link" @click="mobileMenuOpen = false">How It Works</a></li>
                <li class="mobile-nav-item mobile-nav-dropdown">
                    <button type="button" class="mobile-nav-link mobile-dropdown-toggle" @click="orgChartMobileOpen = !orgChartMobileOpen">
                        <span>Org Chart</span>
                        <i class="fas fa-chevron-down" :class="{ 'rotated': orgChartMobileOpen }"></i>
                    </button>
                    <ul class="mobile-submenu" x-show="orgChartMobileOpen" x-transition style="display: none;">
                        <li><a href="{{ route('public.orgchart') }}" @click="mobileMenuOpen = false">View Full Org Chart</a></li>
                        @foreach(\App\Models\Organization::where('is_active', true)->orderBy('name')->get() as $organization)
                            <li><a href="{{ route('public.orgchart', ['organization' => $organization->id]) }}" @click="mobileMenuOpen = false">{{ $organization->name }}</a></li>
                        @endforeach
                    </ul>
                </li>
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
    <section id="top" class="hero hero-with-bg" style="background-image: url('{{ asset('images/hero-bg.jpg') }}');">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-heading">Manage Student Organizations.<br>Simplify Every Activity.</h1>
                <p class="hero-sub">Orgtrack helps student organizations plan, submit and track activity reports under their approved GPOA, while giving OSDW a centralized view of every organization's progress.</p>
                <div class="hero-btns">
                    @auth
                        <a href="{{ route('user.submit') }}" class="btn-primary-cta">
                            <i class="fas fa-plus-circle"></i> Submit Activity
                        </a>
                        <a href="{{ route('user.activities') }}" class="btn-outline-cta">
                            <i class="fas fa-calendar"></i> My Activities
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary-cta">
                            <i class="fas fa-sign-in-alt"></i> Get Started
                        </a>
                        <a href="#how-it-works" class="btn-outline-cta">
                            <i class="fas fa-compass" aria-hidden="true"></i> How It Works
                        </a>
                    @endauth
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
                <h2 class="section-title">Why Use Orgtrack?</h2>
                <p class="section-subtitle">Streamlined management for student organizations</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-file-arrow-up"></i></div>
                        <h5 class="benefit-title">Easy Submission</h5>
                        <p class="benefit-text">Quickly submit activities and signed PDF narrative reports without hassle. User-friendly forms guide you through each step.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-chart-line"></i></div>
                        <h5 class="benefit-title">Real-Time Tracking</h5>
                        <p class="benefit-text">Monitor activity progress instantly. Stay updated on every submission with transparent feedback.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <div class="benefit-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved fa-shield-alt"></i></div>
                        <h5 class="benefit-title">Secure & Organized</h5>
                        <p class="benefit-text">Narrative Reports are stored as verified, signed PDF files-organized and easily accessible.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="section-block section-light how-it-works-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">How Orgtrack Works</h2>
                <p class="section-subtitle">A clear path from submission to completion</p>
            </div>
            <div class="workflow-steps">
                <article class="workflow-step">
                    <span class="workflow-number">01</span>
                    <h3>Submit GPOA</h3>
                    <p>Upload your signed, approved GPOA together with your planned activities.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">02</span>
                    <h3>Request Activity</h3>
                    <p>For each activity, file a request and upload the communication letter.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">03</span>
                    <h3>Submit Report</h3>
                    <p>After the activity, upload the narrative report, photos and attendance sheet.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">04</span>
                    <h3>OSDW Review</h3>
                    <p>OSDW approves the report or sends it back for revision.</p>
                </article>
                <article class="workflow-step">
                    <span class="workflow-number">05</span>
                    <h3>Completed</h3>
                    <p>Once OSDW approves the report, the activity is marked Completed in your records.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="explore" class="section-block">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Get Started</h2>
                <p class="section-subtitle">Learn about Orgtrack or follow your submissions</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="feat-card">
                        <h5 class="feat-title">Learn More</h5>
                        <p class="feat-desc">
                            Orgtrack simplifies how student organizations submit, track, and manage activities. Our platform provides verified reporting, and real-time updates, and streamlined workflows for seamless collaboration.
                        </p>
                        <a href="#" class="btn-feat btn-feat-teal" data-bs-toggle="modal" data-bs-target="#learnMoreModal">Learn More</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feat-card">
                        <h5 class="feat-title">Track Status</h5>
                        <p class="feat-desc">
                            Monitor activity progress for your submitted activities in real-time.
                        </p>
                        @guest
                            <a href="{{ route('login') }}" class="btn-feat btn-feat-amber">Sign In</a>
                        @endguest
                        @auth
                            <a href="{{ route('user.activities') }}" class="btn-feat btn-feat-amber">View Status</a>
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
                <div class="col-md-6 mb-3">
                    <div class="footer-brand"><span style="color:#f5a623; font-weight:700;">Orgtrack</span></div>
                    <p class="footer-desc">
                        Campus Student Organization Narrative & Summary Reports.<br>
                        A comprehensive platform for monitoring and managing student organization activities.
                    </p>
                </div>
                <div class="col-md-6 text-md-end mb-3">
                    <p class="footer-contact-title">Contact Us</p>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>
                        <a href="mailto:osdwcsuaparri@gmail.com" class="footer-link">osdwcsuaparri@gmail.com</a>
                    </p>
                    <p class="mb-0"><i class="fa-brands fa-facebook"></i>
                        <a href="https://www.facebook.com/CSUAparri-OSDW" class="footer-link" target="_blank">CSUAparri-OSDW</a>
                    </p>
                </div>
            </div>
            <hr class="footer-hr">
            <p class="footer-copy">
                &copy; Orgtrack - Campus Student Organization Narrative and Summary Reports. All Rights Reserved.
            </p>
        </div>
    </footer>

    {{-- About Orgtrack Modal --}}
    <div class="modal fade" id="learnMoreModal" tabindex="-1" aria-labelledby="learnMoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="learnMoreModalLabel">About Orgtrack</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body about-modal-body">
                    <div class="about-modal-section">
                        <h6 class="about-section-title">What is Orgtrack?</h6>
                        <p class="about-copy">Orgtrack is the Campus Student Organization Narrative and Summary Reports system of the Office of Student Development and Welfare (OSDW) at Cagayan State University - Aparri. It helps student organizations submit their approved GPOA, request activities and submit narrative reports, and gives OSDW one place to review reports and monitor every organization's progress.</p>
                    </div>

                    <div class="about-modal-section">
                        <h6 class="about-section-title">Key Features</h6>
                        <ul class="about-feature-list">
                            <li><strong>GPOA Submission:</strong> Upload your signed, approved GPOA together with your list of planned activities.</li>
                            <li><strong>Activity Requests:</strong> File a request for each planned activity and upload the communication letter.</li>
                            <li><strong>Narrative Reports:</strong> Submit a narrative report with photos and the attendance sheet, either generated in the system or uploaded as a signed PDF.</li>
                            <li><strong>Report Review:</strong> OSDW approves reports or sends them back with feedback for revision.</li>
                            <li><strong>Status Tracking:</strong> See at a glance whether each activity is pending, ongoing or completed.</li>
                            <li><strong>Secure Records:</strong> Documents are stored securely and visible only to the organization and OSDW.</li>
                        </ul>
                    </div>

                    <div class="about-modal-section">
                        <h6 class="about-section-title">Why Orgtrack</h6>
                        <p class="about-copy">Orgtrack replaces scattered paper submissions with one organized record. Organizations always know where each submission stands, and OSDW can review and follow up from a single dashboard.</p>
                    </div>

                    <div class="about-modal-section about-team-section">
                        <h6 class="about-section-title">Development Team</h6>
                        <p class="about-team-intro">Orgtrack was developed by student developers of CSU - Aparri.</p>

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @vite(['resources/js/app.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sectionIds = ['top', 'features', 'how-it-works'];
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
    </script>

</body>
</html>