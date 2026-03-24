<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UA Academy Enrollment Management System - Smart Campus Operations</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ua-blue: #173b63;
            --ua-blue-light: #2a5f8f;
            --ua-teal: #0ea5a4;
            --ua-teal-deep: #0d8584;
            --ua-ink: #13263d;
            --ua-light: #edf3f9;
        }
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at 10% 0%, rgba(14, 165, 164, 0.16), transparent 34%),
                radial-gradient(circle at 92% 0%, rgba(23, 59, 99, 0.14), transparent 28%),
                var(--ua-light);
            scroll-behavior: smooth;
            color: #33465e;
        }
        h1, h2, h3, h4, h5, h6, .brand-text {
            font-family: 'Montserrat', sans-serif;
        }
        
        /* Modern Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(10px);
            padding: 8px 0;
            box-shadow: 0 8px 22px rgba(17, 38, 62, 0.11);
            border-bottom: 1px solid rgba(23, 59, 99, 0.12);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .navbar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .navbar-brand:hover img {
            transform: scale(1.05) rotate(-5deg);
        }
        .navbar-brand .brand-text {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--ua-blue);
            letter-spacing: 1.1px;
            text-transform: uppercase;
        }
        .navbar-brand .brand-text span {
            color: var(--ua-teal);
        }
        .nav-link {
            color: #3f526b !important;
            font-weight: 500;
            font-size: 0.85rem;
            margin: 0 10px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Montserrat', sans-serif;
        }
        .nav-link:hover {
            color: var(--ua-teal) !important;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero-section {
            background:
                linear-gradient(130deg, rgba(23, 59, 99, 0.88), rgba(14, 165, 164, 0.68)),
                url('assets/img/hero.png') no-repeat center center;
            background-size: cover;
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 110px 0 72px;
            position: relative;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% -10%, rgba(255, 255, 255, 0.2), transparent 42%);
            pointer-events: none;
        }
        .hero-content {
            max-width: 860px;
            padding: 0 18px;
            animation: fadeIn 1.2s ease-out;
            position: relative;
            z-index: 1;
        }
        .hero-tag {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #d4f7f6;
            letter-spacing: 2.4px;
            text-transform: uppercase;
            font-size: 0.76rem;
            margin-bottom: 12px;
            display: block;
        }
        .hero-content h1 {
            font-size: 3.1rem;
            font-weight: 900;
            margin-bottom: 15px;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.1;
            letter-spacing: -0.8px;
        }
        .hero-content p {
            font-size: 0.98rem;
            margin-bottom: 24px;
            color: rgba(255,255,255,0.85);
            font-weight: 400;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        .hero-metrics {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .hero-metric {
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            border-radius: 999px;
            padding: 8px 13px;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            backdrop-filter: blur(4px);
        }

        /* Modern Buttons */
        .btn-custom-primary {
            background-color: var(--ua-teal);
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 11px 24px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            box-shadow: 0 8px 15px rgba(14, 165, 164, 0.25);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-custom-primary:hover {
            background-color: var(--ua-teal-deep);
            color: #fff;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3);
        }
        .btn-custom-outline {
            background-color: transparent;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 10px 22px;
            border-radius: 50px;
            border: 2px solid rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        .btn-custom-outline:hover {
            border-color: #fff;
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-3px);
        }

        /* Sections General */
        section {
            padding: 68px 0;
            position: relative;
        }
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-header h2 {
            color: var(--ua-blue);
            font-weight: 800;
            font-size: 1.9rem;
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .section-header p {
            color: #6c757d;
            font-size: 0.95rem;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Features Section */
        .features-section {
            background-color: #ffffff;
        }
        .feature-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 26px 20px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(14, 165, 164, 0.07) 0%, rgba(255,255,255,0) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 16px 34px rgba(17, 38, 62, 0.1);
            border-color: rgba(14, 165, 164, 0.22);
        }
        .feature-card:hover::before {
            opacity: 1;
        }
        .feature-icon {
            font-size: 1.5rem;
            color: var(--ua-teal-deep);
            margin-bottom: 20px;
            background: #fff;
            width: 54px;
            height: 54px;
            line-height: 54px;
            border-radius: 14px;
            margin: 0 auto 20px auto;
            position: relative;
            box-shadow: 0 8px 15px rgba(14, 165, 164, 0.2);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            background: var(--ua-teal);
            color: var(--ua-blue);
        }
        .feature-card h3 {
            font-weight: 800;
            color: var(--ua-blue);
            margin-bottom: 12px;
            font-size: 1.08rem;
        }
        .feature-card p {
            color: #555;
            font-size: 0.86rem;
            line-height: 1.55;
            margin-bottom: 0;
        }

        /* Call to Action Container */
        .cta-container {
            background: linear-gradient(140deg, #173b63, #0d4f75);
            border-radius: 24px;
            padding: 42px 34px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(30, 58, 95, 0.28);
        }
        .cta-container::after {
            content: '';
            position: absolute;
            top: -50%; left: -10%;
            width: 50%; height: 200%;
            background: linear-gradient(to right, rgba(244, 211, 94, 0.08), transparent);
            transform: rotate(-15deg);
            pointer-events: none;
        }
        .cta-image {
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.24);
            width: 100%;
            height: 250px;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.05);
        }

        /* Footer */
        footer {
            background-color: #0f223b;
            color: #fff;
            padding: 50px 0 25px;
            border-top: 4px solid var(--ua-teal);
        }
        .footer-brand img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            filter: grayscale(100%) brightness(200%);
            transition: filter 0.3s ease;
        }
        .footer-brand:hover img {
            filter: none;
        }
        .footer-brand h2 {
            font-weight: 900;
            font-size: 1.4rem;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .social-link {
            display: inline-flex;
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.05);
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
            font-size: 1rem;
            margin: 0 8px;
            transition: all 0.3s ease;
        }
        .social-link:hover {
            background: var(--ua-teal);
            color: var(--ua-blue);
            transform: translateY(-3px);
        }
        .footer-bottom {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Custom Portal Button in Navbar */
        .nav-portal-btn {
            background-color: var(--ua-teal);
            color: #fff !important;
            padding: 8px 20px !important;
            border-radius: 50px;
            font-weight: 700 !important;
            font-size: 0.8rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: all 0.3s ease !important;
        }
        .nav-portal-btn:hover {
            background-color: var(--ua-teal-deep) !important;
            transform: translateY(-2px) !important;
        }
        
        @media (max-width: 991px) {
            .hero-section { min-height: 72vh; padding: 96px 0 52px; }
            .hero-content h1 { font-size: 2.2rem; }
            .cta-image { margin-top: 30px; }
            .section-header h2 { font-size: 1.55rem; }
            .footer-bottom { flex-direction: column; text-align: center; gap: 10px; }
        }
    </style>
</head>
<body>

    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <img src="assets/img/logo.png" alt="UA ACADEMY Logo">
                <span class="brand-text">UA <span>ACADEMY</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <i class="fas fa-bars" style="color: var(--ua-blue); font-size: 1.5rem;"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#excellence">Excellence</a></li>
                    <li class="nav-item"><a class="nav-link" href="#programs">Programs</a></li>
                </ul>
                <div class="d-flex mt-3 mt-lg-0">
                    <a href="login.php" class="nav-link nav-portal-btn">
                        <i class="fas fa-fingerprint me-2"></i>Portal Access
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="hero-content mx-auto">
                <span class="hero-tag">Enrollment Management System</span>
                <h1>Smarter Enrollment.<br>Faster School Operations.</h1>
                <p>Digitize admissions workflow, student records, enrollment processing, and payment tracking in one modern and reliable school platform.</p>
                
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-4">
                    <a href="login.php" class="btn-custom-primary">
                        <i class="fas fa-fingerprint me-2"></i> Open Staff Portal
                    </a>
                    <a href="#excellence" class="btn-custom-outline">
                        Explore Features <i class="fas fa-arrow-down ms-2"></i>
                    </a>
                </div>

                <div class="hero-metrics">
                    <span class="hero-metric"><i class="fas fa-users me-1"></i> Student Management</span>
                    <span class="hero-metric"><i class="fas fa-list-check me-1"></i> Enrollment Workflow</span>
                    <span class="hero-metric"><i class="fas fa-receipt me-1"></i> Payment Tracking</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Excellence / Features Section -->
    <section id="excellence" class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Built for Academic Operations</h2>
                <p>A modern digital workspace that helps school staff process student services with speed, accuracy, and confidence.</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3>Organized Student Records</h3>
                        <p>Access student profiles and academic information instantly with clear, structured, and searchable record views.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3>Fast Enrollment Flow</h3>
                        <p>Guide each enrollee from term setup to confirmation with a smooth multi-step process and built-in checks.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3>Accurate Collections</h3>
                        <p>Track assessed amounts, posted payments, and balances with printable ledgers and receipts for daily operations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="programs" class="bg-light" style="padding-top: 40px;">
        <div class="container">
            <div class="cta-container">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0 text-white position-relative" style="z-index: 2;">
                        <h2 class="mb-3" style="font-family: 'Montserrat', sans-serif; font-weight:800; font-size: 2rem; line-height: 1.2;">Everything Your Team Needs<br><span style="color:var(--ua-teal);">in One Portal</span></h2>
                        <p class="mb-4" style="color: rgba(255,255,255,0.8); font-size:0.92rem; max-width: 92%;">From admissions to payments, manage your enrollment lifecycle with a system designed for speed, clarity, and consistent school data.</p>
                        <a href="login.php" class="btn-custom-primary">
                            Access Login <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                    <div class="col-lg-6 position-relative" style="z-index: 2;">
                        <img src="assets/img/hero.png" alt="Campus Life" class="cta-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Premium Footer -->
    <footer>
        <div class="container">
            <div class="row text-center text-lg-start">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="footer-brand">
                        <img src="assets/img/logo.png" alt="UA ACADEMY">
                        <h2>UA Academy</h2>
                    </div>
                    <p style="color: rgba(255,255,255,0.6); max-width: 390px; margin: 0 auto 0 0; font-size: 0.85rem;">
                        UA Academy Enrollment Management System helps staff deliver faster enrollment service and better operational visibility.
                    </p>
                </div>
                <div class="col-lg-6 text-center text-lg-end d-flex flex-column justify-content-end">
                    <div class="mb-2">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom flex-column flex-md-row">
                <div>&copy; <?php echo date('Y'); ?> UA Academy. All Rights Reserved.</div>
                <div class="mt-2 mt-md-0 fw-bold" style="color: var(--ua-teal); letter-spacing: 1.2px; font-size: 0.8rem;">ENROLLMENT MADE SIMPLE</div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple script to add background to navbar on scroll for a more premium feel
        window.addEventListener('scroll', function() {
            var navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 10px 26px rgba(30,58,95,0.16)';
                navbar.style.padding = '8px 0';
            } else {
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.92)';
                navbar.style.boxShadow = '0 8px 24px rgba(30,58,95,0.1)';
                navbar.style.padding = '10px 0';
            }
        });
    </script>
</body>
</html>
