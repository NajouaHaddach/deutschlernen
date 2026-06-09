<?php
// index.php - Minimalist SaaS Dynamic Landing Page
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeutschLernen - Practice-Based Language Platform</title>
    
    <!-- Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="#" class="logo"><i class="fa-solid fa-circle"></i> DeutschLernen</a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#levels">Levels</a>
                <a href="#interface">Interface</a>
                <a href="#how-it-works">Method</a>
            </div>
            <div class="nav-actions">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-link">Login</a>
                    <a href="auth/register.php" class="btn btn-primary">Start Free</a>
                <?php endif; ?>
            </div>
            <button class="mobile-toggle" id="mobileToggle"><i class="fa-solid fa-bars"></i></button>
        </div>
    </nav>

    <!-- MOBILE MENU -->
    <div class="mobile-menu" id="mobileMenu">
        <button class="mobile-close" id="mobileClose"><i class="fa-solid fa-xmark"></i></button>
        <a href="#features" class="mobile-link">Features</a>
        <a href="#levels" class="mobile-link">Levels</a>
        <a href="#interface" class="mobile-link">Interface</a>
        <a href="#how-it-works" class="mobile-link">Method</a>
        <div class="mobile-actions" style="margin-top: 20px; display: flex; flex-direction: column; gap: 12px;">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-primary" style="text-align:center;">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-link" style="text-align:center;">Login</a>
                <a href="auth/register.php" class="btn btn-primary" style="text-align:center;">Start Free</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-bg-graphic"></div>
        <div class="container hero-inner animate-on-load">
            <span class="badge">Next-Gen Language System</span>
            <h1>Learn German Through<br>Real-Life Situations.</h1>
            <p>Practice realistic job scenarios, adaptive communication setups, and fluid vocabulary training built for professional environments.</p>
            <div class="hero-cta">
                <a href="auth/register.php" class="btn btn-primary btn-lg btn-pulse">Start Learning Now</a>
                <a href="#features" class="btn btn-link">Explore features <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i></a>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="section scroll-reveal">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Core Engine</span>
                <h2>Everything you need to master German</h2>
            </div>
            <div class="grid-3">
                <div class="card">
                    <i class="fa-solid fa-street-view"></i>
                    <h3>Real Scenarios</h3>
                    <p>Train inside actual operational context setups like corporate interviews and administrative registration loops.</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-comments"></i>
                    <h3>Interactive Speech</h3>
                    <p>Adaptable situational dialogue structures to build long-term reflex memory without repetitive syntax drills.</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-headphones"></i>
                    <h3>Focused Listening</h3>
                    <p>Audio tracking workflows crafted around standard conversational speeds and native structural tone variations.</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-book-open"></i>
                    <h3>Context Vocab</h3>
                    <p>Acquire complex technical and social vocabulary naturally inside functional dialogue branches.</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-users"></i>
                    <h3>Level Lounges</h3>
                    <p>Step away from isolated study models and engage with verified peers inside specialized communication spaces.</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-chart-line"></i>
                    <h3>Telemetry Analytics</h3>
                    <p>Clean indicators monitoring performance levels, sentence production tracking, and structural error analysis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- LEVELS -->
    <section id="levels" class="section bg-white scroll-reveal">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">CEFR Alignment</span>
                <h2>Proficiency Matrix</h2>
            </div>
            <div class="levels-grid">
                <div class="card-level"><span>A1</span><h4>Beginner</h4><p>Basic structural templates, daily routines, foundational vocabulary.</p></div>
                <div class="card-level"><span>A2</span><h4>Elementary</h4><p>Simple routine conversations regarding familiar workspace tasks.</p></div>
                <div class="card-level"><span>B1</span><h4>Intermediate</h4><p>Independent production on professional topics and practical execution.</p></div>
                <div class="card-level"><span>B2</span><h4>Upper Intermediate</h4><p>Technical arguments, complex situational debates, corporate processing.</p></div>
                <div class="card-level"><span>C1</span><h4>Advanced</h4><p>Spontaneous processing, high fluidity, implicit meaning comprehension.</p></div>
                <div class="card-level"><span>C2</span><h4>Mastery</h4><p>Effortless documentation structuring, complex precise articulation.</p></div>
            </div>
        </div>
    </section>

    <!-- INTERFACE / PLATFORM IMAGES -->
    <section id="interface" class="section scroll-reveal">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Product View</span>
                <h2>Inside the Platform Ecosystem</h2>
                <p>A clean, focused workspace tailored to eliminate cognitive fatigue and maximize input retention.</p>
            </div>
            <div class="interface-gallery">
                <div class="gallery-wrapper grid-2">
                    <div class="gallery-block">
                        <img src="https://images.unsplash.com/photo-1531403009284-440f080d1e12?auto=format&fit=crop&w=700&q=80" alt="SaaS Learning Dashboard UI">
                        <div class="block-info">
                            <h4>Centralized Performance Hub</h4>
                            <p>Track modules, dynamic metrics, and unlock custom scenarios.</p>
                        </div>
                    </div>
                    <div class="gallery-block">
                        <img src="https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=700&q=80" alt="Interactive Dialogue Workspace Layout">
                        <div class="block-info">
                            <h4>Adaptive Scenario Interface</h4>
                            <p>Engage in branching dialogue tracking with instant syntax evaluations.</p>
                        </div>
                    </div>
                    <div class="gallery-block">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=700&q=80" alt="Telemetry Tracking Panels UI">
                        <div class="block-info">
                            <h4>Precision Telemetry Charts</h4>
                            <p>Detailed performance diagnosis broken down by structural categories.</p>
                        </div>
                    </div>
                    <div class="gallery-block">
                        <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=700&q=80" alt="Lounge Workspace Hub UI">
                        <div class="block-info">
                            <h4>Interactive Room Module</h4>
                            <p>Peer communication lounges featuring audio active tracking environments.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- METHODOLOGY -->
    <section id="how-it-works" class="section bg-white scroll-reveal">
        <div class="container standard-width">
            <div class="section-header">
                <span class="section-tag">Operational Flow</span>
                <h2>The 4-Step Framework</h2>
            </div>
            <div class="flow-list">
                <div class="flow-step"><span>01</span><p>Select targeting matrix layer (A1 - C2).</p></div>
                <div class="flow-step"><span>02</span><p>Initialize real-life operational scenario.</p></div>
                <div class="flow-step"><span>03</span><p>Execute interactive situational tasks.</p></div>
                <div class="flow-step"><span>04</span><p>Analyze diagnostic feedback outputs.</p></div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="section scroll-reveal">
        <div class="container standard-width">
            <div class="quote-slide">
                <p class="quote-text">"DeutschLernen directly addressed situational vocabulary issues instead of repetitive drills. It entirely transformed my communication structures f corporate settings inside Berlin."</p>
                <span class="quote-author">Amine Mansouri — Software Engineer</span>
            </div>
        </div>
    </section>

    <!-- CONVERSION CTA -->
    <section class="section scroll-reveal" style="padding-bottom: 120px;">
        <div class="container">
            <div class="cta-box" id="ctaBox">
                <h2>Accelerate your fluency path.</h2>
                <p>Join structured language tracks designed to eliminate operational friction.</p>
                <a href="auth/register.php" class="btn btn-light btn-pulse">Create Free Account</a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container footer-minimal">
            <span>&copy; <?= date('Y') ?> DeutschLernen. Minimal Functional Architecture.</span>
            <div class="f-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="mailto:support@deutschlernen.io">Support</a>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle logic
        const toggle = document.getElementById('mobileToggle');
        const menu = document.getElementById('mobileMenu');
        const close = document.getElementById('mobileClose');
        
        toggle.onclick = () => menu.classList.add('active');
        close.onclick = () => menu.classList.remove('active');
        
        document.querySelectorAll('.mobile-link').forEach(link => {
            link.onclick = () => menu.classList.remove('active');
        });

        // Scroll Reveal Animation (Intersection Observer API)
        const revealElements = document.querySelectorAll('.scroll-reveal');
        const observerOptions = {
            root: null,
            threshold: 0.12,
            rootMargin: "0px 0px -40px 0px"
        };

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target); // Trigger once
                }
            });
        }, observerOptions);

        revealElements.forEach(element => {
            revealObserver.observe(element);
        });
    </script>
</body>
</html>