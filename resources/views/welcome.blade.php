<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORMS | Campus Student Organization Narrative Accomplishment and Summary Reports</title>

    {{-- Google Fonts: Poppins, Raleway, Oswald, Source Sans 3 --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Raleway:wght@600;700;800&family=Oswald:wght@400;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('css/welcomeblade.css') }}">
</head>
<body>

    {{-- ========================================================
         NAVBAR  —  matches screenshot: brand left, links, auth right
    ======================================================== --}}
    <nav class="site-navbar">
        <div class="navbar-inner">

            <div class="navbar-brand-wrap">
                <a href="{{ url('/') }}" class="nav-brand">
                    <img src="{{ asset('images/corms-logo.png.jpg') }}" alt="" class="nav-logo-img">
                    <span class="nav-brand-text">CORMS</span>
                </a>
            </div>

            <div class="nav-auth">
                <a href="{{ route('public.activities') }}" class="nav-auth-link">
                     Browse Activities
                </a>
                @auth
                    <div class="nav-user" id="userDropdownWrap">
                        <button class="user-btn" id="userBtn">
                            <i class="fas fa-user-circle"></i>
                            <span>{{ Auth::user()->username ?? Auth::user()->name }}</span>
                            <i class="fas fa-chevron-down caret"></i>
                        </button>
                        <div class="user-dropdown" id="userDropdownMenu">
                            <a href="{{ url('/dashboard') }}"><i class="fas fa-home"></i> Dashboard</a>
                            <a href="{{ route('user.activities') }}"><i class="fas fa-calendar"></i> My Activities</a>
                            <hr>
                            <a href="{{ route('logout') }}" class="danger"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-auth-link">Login</a>
                @endauth
            </div>

            <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    {{-- ========================================================
         HERO — Improved with better heading and spacing
    ======================================================== --}}
    <section class="hero hero-with-bg" style="background-image: url('{{ asset('images/hero-bg.jpg') }}');">
        <div class="hero-container">
            <div class="hero-content">
                
                <h1 class="hero-heading">
                    Manage Student<br>Organization Activities<br>with Ease
                </h1>
                <p class="hero-sub">
                    A comprehensive platform designed to streamline submission, tracking, and approval of student organization activities. Simplify reporting, enhance transparency, and monitor progress in real-time.
                </p>
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
                        <a href="{{ route('login') }}" class="btn-outline-cta">
                            <i class="fas fa-arrow-right"></i> Learn More
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
    <section class="section-block section-benefits">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Use CORMS?</h2>
                <p class="section-subtitle">Streamlined management for student organizations</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <h5 class="benefit-title">Easy Submission</h5>
                        <p class="benefit-text">Quickly submit activities and reports without hassle. User-friendly forms guide you through each step.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <h5 class="benefit-title">Real-Time Tracking</h5>
                        <p class="benefit-text">Monitor approval status instantly. Stay updated on every submission with transparent feedback.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="benefit-item">
                        <h5 class="benefit-title">Secure & Organized</h5>
                        <p class="benefit-text">All documents and data stored safely. Organized archive for easy access and management.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================================================
         FEATURE / STATS SECTION
    ======================================================== --}}
    <section class="section-block section-light">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feat-card">
                        <h5 class="feat-title">Browse Activities</h5>
                        <p class="feat-desc">
                            Discover various activities organized by student organizations on campus.
                        </p>
                        @guest
                            <a href="{{ route('public.activities') }}" class="btn-feat btn-feat-blue">Browse Activities</a>
                        @endguest
                        @auth
                            <a href="{{ route('public.activities') }}" class="btn-feat btn-feat-blue">Browse Activities</a>
                        @endauth
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feat-card">
                        <h5 class="feat-title">Learn More</h5>
                        <p class="feat-desc">
                            CORMS simplifies how student organizations submit, track, and manage activities. Our platform provides transparency, real-time updates, and streamlined workflows for seamless collaboration.
                        </p>
                        <a href="#" class="btn-feat btn-feat-teal" data-bs-toggle="modal" data-bs-target="#learnMoreModal">Learn More</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feat-card">
                        <h5 class="feat-title">Track Status</h5>
                        <p class="feat-desc">
                            Monitor the approval status of your submitted activities in real-time.
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
    <footer class="site-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="footer-brand"><i class="fas fa-university me-2"></i>CORMS</div>
                    <p class="footer-desc">
                        Campus Student Organization Activities Tracking System.<br>
                        A comprehensive platform for monitoring and managing student organization activities.
                    </p>
                </div>
                <div class="col-md-6 text-md-end mb-3">
                    <p class="footer-contact-title">Contact Us</p>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>
                        <a href="mailto:activities@campus.edu" class="footer-link">activities@campus.edu</a>
                    </p>
                    <p class="mb-0"><i class="fas fa-phone me-2"></i>
                        <a href="tel:+1234567890" class="footer-link">+1 234 567 890</a>
                    </p>
                </div>
            </div>
            <hr class="footer-hr">
            <p class="footer-copy">
                &copy; {{ date('Y') }} Campus Student Organization Activities Tracking System. All Rights Reserved.
            </p>
        </div>
    </footer>

    {{-- Learn More Modal --}}
    <div class="modal fade" id="learnMoreModal" tabindex="-1" aria-labelledby="learnMoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="learnMoreModalLabel">About CORMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- System Overview -->
                    <div class="mb-4">
                        <h6 class="mb-3" style="color: #1a5f7a; font-weight: 600;">What is CORMS?</h6>
                        <p style="color: #555; line-height: 1.6;">
                            <strong>Campus Organization Reporting Management System (CORMS)</strong> is a comprehensive digital platform designed to streamline the management of student organization activities. Our mission is to simplify the process of submitting, tracking, and approving organizational reports while maintaining transparency and accountability.
                        </p>
                    </div>

                    <!-- Key Features -->
                    <div class="mb-4">
                        <h6 class="mb-3" style="color: #1a5f7a; font-weight: 600;">Key Features</h6>
                        <ul style="color: #555; line-height: 1.8;">
                            <li><strong>Easy Submission:</strong> User-friendly forms for submitting activity reports and documentation</li>
                            <li><strong>Real-Time Tracking:</strong> Monitor approval status instantly with transparent feedback</li>
                            <li><strong>Secure Storage:</strong> All documents and data are safely organized and easily accessible</li>
                            <li><strong>Organization Management:</strong> Browse and discover various activities organized by student groups</li>
                            <li><strong>Workflow Automation:</strong> Streamlined approval processes for efficient management</li>
                        </ul>
                    </div>

                    <!-- System Benefits -->
                    <div class="mb-4">
                        <h6 class="mb-3" style="color: #1a5f7a; font-weight: 600;">Why Choose CORMS?</h6>
                        <p style="color: #555; line-height: 1.6;">
                            CORMS eliminates paperwork, reduces administrative burden, and ensures all student organization activities are properly documented and tracked. The system promotes transparency, enhances collaboration, and provides real-time insights into organizational activities and performance.
                        </p>
                    </div>

                    <!-- Developers -->
                    <div class="border-top pt-4">
                        <h6 class="mb-3" style="color: #1a5f7a; font-weight: 600;">Development Team</h6>
                        <p style="color: #666; font-size: 0.95rem; margin-bottom: 1.5rem;">
                            CORMS was developed by a dedicated team of student developers and IT professionals committed to improving campus life through technology.
                        </p>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; border-left: 4px solid #1a5f7a;">
                                <p style="margin: 0; color: #333; font-weight: 500; margin-bottom: 0.25rem;">Project Lead</p>
                                <p style="margin: 0; color: #666; font-size: 0.9rem;">IT Department</p>
                            </div>
                            <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; border-left: 4px solid #1a5f7a;">
                                <p style="margin: 0; color: #333; font-weight: 500; margin-bottom: 0.25rem;">Development</p>
                                <p style="margin: 0; color: #666; font-size: 0.9rem;">Campus Software Team</p>
                            </div>
                            <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; border-left: 4px solid #1a5f7a;">
                                <p style="margin: 0; color: #333; font-weight: 500; margin-bottom: 0.25rem;">UI/UX Design</p>
                                <p style="margin: 0; color: #666; font-size: 0.9rem;">Design Team</p>
                            </div>
                            <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; border-left: 4px solid #1a5f7a;">
                                <p style="margin: 0; color: #333; font-weight: 500; margin-bottom: 0.25rem;">Quality Assurance</p>
                                <p style="margin: 0; color: #666; font-size: 0.9rem;">Testing & QA Team</p>
                            </div>
                        </div>
                        
                        <p style="color: #999; font-size: 0.85rem; margin-top: 1.5rem; text-align: center;">
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
    <script src="{{ asset('js/auth.js') }}"></script>

</body>
</html>